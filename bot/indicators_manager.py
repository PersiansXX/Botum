import pandas as pd
import numpy as np
import logging
import time
import random
import os
import json
import ccxt
from datetime import datetime

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

class IndicatorsManager:
    """
    Teknik indikatörleri hesaplayan ve yöneten sınıf.
    TradingBot sınıfından ayrıştırılarak kodun daha modüler olması sağlanmıştır.
    """
    
    def __init__(self, config=None):
        """
        IndicatorsManager sınıfını başlatır.
        
        :param config: Bot yapılandırma ayarları
        """
        self.config = config or {}
        self.indicators_data = {}  # İndikatör hesaplama sonuçlarını önbelleğe alma
        
        # Gelişmiş sinyal ayarlarını yükle
        self.signal_settings = self.config.get('signal_settings', {})
        self.indicator_weights = self.signal_settings.get('weights', {
            'rsi': 15,
            'macd': 15,
            'bollinger': 15,
            'moving_average': 15,
            'stochastic': 10,
            'supertrend': 15,
            'adx': 10,
            'ichimoku': 5
        })
        
        # Sinyal konsensüs metodu
        self.consensus_method = self.signal_settings.get('consensus_method', 'weighted')
        self.signal_strength_threshold = self.signal_settings.get('strength_threshold', 65)
        self.conflicting_signals_handling = self.signal_settings.get('conflicting_signals', 'stronger_wins')
        
        logger.info(f"İndikatör Yöneticisi başlatıldı. Konsensüs metodu: {self.consensus_method}, Sinyal eşiği: {self.signal_strength_threshold}%")
    
    def calculate_indicators(self, df, symbol, timeframe='1h', use_cache=True):
        """
        OHLCV verileri için teknik göstergeleri hesaplar
        
        :param df: OHLCV verileri DataFrame
        :param symbol: Coin sembolü
        :param timeframe: Zaman dilimi (varsayılan: 1h)
        :param use_cache: Önbellek kullanılsın mı (varsayılan: True)
        :return: Hesaplanan göstergeleri içeren dict
        """
        try:
            # Önbellek kontrolü
            if use_cache:
                cached_data = self.get_cached_indicators(symbol, timeframe)
                if cached_data:
                    return cached_data
                
            logger.debug(f"{symbol} için teknik göstergeler hesaplanıyor...")
            indicators = {}
            
            if df.empty:
                logger.warning(f"{symbol} için veri olmadığından göstergeler hesaplanamıyor")
                return indicators
                
            # 1. RSI hesapla
            if self.config.get('indicators', {}).get('rsi', {}).get('enabled', True):
                rsi_period = self.config.get('indicators', {}).get('rsi', {}).get('period', 14)
                # RSI, hem 'window' hem 'period' parametrelerini kabul eder
                from indicators import rsi
                rsi_values = rsi.calculate(df, period=rsi_period)
                rsi_value = rsi_values.iloc[-1]
                
                # Gelişmiş RSI sinyal ayarları
                rsi_oversold = self.signal_settings.get('rsi', {}).get('oversold', 30)
                rsi_overbought = self.signal_settings.get('rsi', {}).get('overbought', 70)
                rsi_buy_cross_up = self.signal_settings.get('rsi', {}).get('buy_cross_up', True)
                rsi_sell_cross_down = self.signal_settings.get('rsi', {}).get('sell_cross_down', True)
                
                # RSI sinyali belirle
                rsi_signal = 'NEUTRAL'
                rsi_prev = rsi_values.iloc[-2] if len(rsi_values) > 1 else rsi_value
                
                if rsi_buy_cross_up and rsi_value > rsi_oversold and rsi_prev <= rsi_oversold:
                    rsi_signal = 'BUY'  # RSI aşırı satım bölgesinden yukarı kırıyor
                elif rsi_value < rsi_oversold:
                    rsi_signal = 'BUY'  # RSI aşırı satım bölgesinde
                elif rsi_sell_cross_down and rsi_value < rsi_overbought and rsi_prev >= rsi_overbought:
                    rsi_signal = 'SELL'  # RSI aşırı alım bölgesinden aşağı kırıyor
                elif rsi_value > rsi_overbought:
                    rsi_signal = 'SELL'  # RSI aşırı alım bölgesinde
                
                indicators['rsi'] = {
                    'value': float(rsi_value),
                    'period': rsi_period,
                    'signal': rsi_signal,
                    'prev': float(rsi_prev),
                    'oversold': rsi_oversold,
                    'overbought': rsi_overbought
                }
            
            # 2. MACD hesapla
            if self.config.get('indicators', {}).get('macd', {}).get('enabled', True):
                fast_period = self.config.get('indicators', {}).get('macd', {}).get('fast_period', 12)
                slow_period = self.config.get('indicators', {}).get('macd', {}).get('slow_period', 26)
                signal_period = self.config.get('indicators', {}).get('macd', {}).get('signal_period', 9)
                
                # MACD kendi özel parametreleriyle çağrılıyor
                from indicators import macd
                macd_data = macd.calculate(df, fast_period=fast_period, slow_period=slow_period, signal_period=signal_period)
                
                # Son değerleri al
                macd_line = macd_data['macd'].iloc[-1]
                signal_line = macd_data['signal'].iloc[-1]
                histogram = macd_data['histogram'].iloc[-1]
                
                # Önceki değerler
                macd_line_prev = macd_data['macd'].iloc[-2] if len(macd_data) > 1 else macd_line
                signal_line_prev = macd_data['signal'].iloc[-2] if len(macd_data) > 1 else signal_line
                histogram_prev = macd_data['histogram'].iloc[-2] if len(macd_data) > 1 else histogram
                
                # Gelişmiş MACD sinyal ayarları
                macd_buy_cross = self.signal_settings.get('macd', {}).get('buy_cross', True)
                macd_sell_cross = self.signal_settings.get('macd', {}).get('sell_cross', True)
                macd_zero_cross_up = self.signal_settings.get('macd', {}).get('zero_cross_up', False)
                macd_zero_cross_down = self.signal_settings.get('macd', {}).get('zero_cross_down', False)
                macd_histogram_reversal = self.signal_settings.get('macd', {}).get('histogram_reversal', False)
                
                # MACD sinyalini belirle
                macd_signal = 'NEUTRAL'
                
                # Sinyal çizgisi kesişiminden sinyal üretme
                if macd_buy_cross and macd_line > signal_line and macd_line_prev <= signal_line_prev:
                    macd_signal = 'BUY'  # MACD sinyal çizgisini yukarı kesti
                elif macd_sell_cross and macd_line < signal_line and macd_line_prev >= signal_line_prev:
                    macd_signal = 'SELL'  # MACD sinyal çizgisini aşağı kesti
                    
                # Sıfır çizgisi kesişiminden sinyal üretme
                elif macd_zero_cross_up and macd_line > 0 and macd_line_prev <= 0:
                    macd_signal = 'BUY'  # MACD sıfır çizgisini yukarı kesti
                elif macd_zero_cross_down and macd_line < 0 and macd_line_prev >= 0:
                    macd_signal = 'SELL'  # MACD sıfır çizgisini aşağı kesti
                    
                # Histogram tersine dönüşünden sinyal üretme
                elif macd_histogram_reversal:
                    if histogram > 0 and histogram > histogram_prev and histogram_prev < 0:
                        macd_signal = 'BUY'  # Histogram negatiften pozitife dönüyor
                    elif histogram < 0 and histogram < histogram_prev and histogram_prev > 0:
                        macd_signal = 'SELL'  # Histogram pozitiften negatife dönüyor
                
                indicators['macd'] = {
                    'value': float(macd_line),
                    'signal_line': float(signal_line),
                    'histogram': float(histogram),
                    'fast_period': fast_period,
                    'slow_period': slow_period,
                    'signal_period': signal_period,
                    'signal': macd_signal,
                    'prev_value': float(macd_line_prev),
                    'prev_signal': float(signal_line_prev),
                    'prev_histogram': float(histogram_prev)
                }
                
            # 3. Bollinger Bands hesapla
            if self.config.get('indicators', {}).get('bollinger_bands', {}).get('enabled', True):
                bb_window = self.config.get('indicators', {}).get('bollinger_bands', {}).get('window', 20)
                num_std = self.config.get('indicators', {}).get('bollinger_bands', {}).get('num_std', 2)
                
                # Bollinger Bands 'window' parametresini kabul ediyor
                from indicators import bollinger_bands
                bb_data = bollinger_bands.calculate(df, window=bb_window, num_std=num_std)
                
                # Son değerleri al
                upper_band = bb_data['upper_band'].iloc[-1]
                middle_band = bb_data['middle_band'].iloc[-1]
                lower_band = bb_data['lower_band'].iloc[-1]
                close = df['close'].iloc[-1]
                prev_close = df['close'].iloc[-2] if len(df) > 1 else close
                
                # Bollinger Bant genişliğini hesapla
                band_width = (upper_band - lower_band) / middle_band
                prev_band_width = (bb_data['upper_band'].iloc[-2] - bb_data['lower_band'].iloc[-2]) / bb_data['middle_band'].iloc[-2] if len(bb_data) > 1 else band_width
                
                # Gelişmiş Bollinger Bands sinyal ayarları
                bb_buy_touch = self.signal_settings.get('bollinger', {}).get('buy_touch', True)
                bb_sell_touch = self.signal_settings.get('bollinger', {}).get('sell_touch', True)
                bb_buy_bounce = self.signal_settings.get('bollinger', {}).get('buy_bounce', False)
                bb_bandwidth_squeeze = self.signal_settings.get('bollinger', {}).get('bandwidth_squeeze', False)
                
                # Bollinger Band sinyalini belirle
                bb_signal = 'NEUTRAL'
                
                # Bantlara dokunma/kırma sinyalleri
                if bb_buy_touch and close <= lower_band:
                    bb_signal = 'BUY'  # Fiyat alt banda dokundu veya kırdı
                elif bb_sell_touch and close >= upper_band:
                    bb_signal = 'SELL'  # Fiyat üst banda dokundu veya kırdı
                    
                # Alt banttan yukarı dönüş (bounce) sinyali
                elif bb_buy_bounce and prev_close <= lower_band and close > lower_band:
                    bb_signal = 'BUY'  # Fiyat alt banddan yukarı dönüyor
                
                # Bant sıkışması algılama
                if bb_bandwidth_squeeze and band_width < prev_band_width and band_width < 0.1:
                    # Sıkışma durumu - potansiyel breakout bekleniyor
                    if 'signal' in indicators.get('macd', {}) and indicators['macd']['signal'] == 'BUY':
                        bb_signal = 'BUY'  # MACD yükseliş gösteriyorsa
                    elif 'signal' in indicators.get('macd', {}) and indicators['macd']['signal'] == 'SELL':
                        bb_signal = 'SELL'  # MACD düşüş gösteriyorsa
                
                indicators['bollinger'] = {
                    'upper': float(upper_band),
                    'middle': float(middle_band),
                    'lower': float(lower_band),
                    'width': float(band_width),
                    'window': bb_window,
                    'num_std': num_std,
                    'signal': bb_signal
                }
            
            # 4. Hareketli Ortalamalar hesapla - TA-Lib ile optimize edildi
            if self.config.get('indicators', {}).get('moving_average', {}).get('enabled', True):
                from indicators import moving_average
                import talib
                
                # TA-Lib ile MA değerleri hesaplama - çok daha hızlı ve optimize
                try:
                    # TA-Lib ile SMA (Simple Moving Average) hesaplama
                    ma20 = talib.SMA(df['close'].values, timeperiod=20)[-1]
                    ma50 = talib.SMA(df['close'].values, timeperiod=50)[-1]
                    ma100 = talib.SMA(df['close'].values, timeperiod=100)[-1]
                    ma200 = talib.SMA(df['close'].values, timeperiod=200)[-1]
                except Exception as e:
                    # TA-Lib hatası durumunda pandas ile hesapla (yedek olarak)
                    logger.warning(f"TA-Lib ile MA hesaplarken hata, pandas'a dönülüyor: {str(e)}")
                    ma20 = df['close'].rolling(window=20, min_periods=1).mean().iloc[-1]
                    ma50 = df['close'].rolling(window=50, min_periods=1).mean().iloc[-1]
                    ma100 = df['close'].rolling(window=100, min_periods=1).mean().iloc[-1]
                    ma200 = df['close'].rolling(window=200, min_periods=1).mean().iloc[-1]
                
                close = df['close'].iloc[-1]
                
                # Geliştirilmiş hareketli ortalama sinyali belirleme
                ma_signal = 'NEUTRAL'
                
                # Ana sinyal kuralları
                if close > ma20 > ma50:
                    ma_signal = 'BUY'  # Kısa vadeli yükseliş
                elif close < ma20 < ma50:
                    ma_signal = 'SELL'  # Kısa vadeli düşüş
                
                # Güçlü trend tespiti (Opsiyonel)
                strong_uptrend = ma20 > ma50 > ma100 > ma200
                strong_downtrend = ma20 < ma50 < ma100 < ma200
                
                indicators['moving_averages'] = {
                    'ma20': float(ma20),
                    'ma50': float(ma50),
                    'ma100': float(ma100),
                    'ma200': float(ma200),
                    'close': float(close),
                    'signal': ma_signal,
                    'strong_uptrend': strong_uptrend,
                    'strong_downtrend': strong_downtrend
                }
                
            # 5. Stochastic Oscillator hesapla
            if self.config.get('indicators', {}).get('stochastic', {}).get('enabled', True):
                from indicators import stochastic
                k_period = self.config.get('indicators', {}).get('stochastic', {}).get('k_period', 14)
                d_period = self.config.get('indicators', {}).get('d_period', 3)
                slowing = self.config.get('indicators', {}).get('stochastic', {}).get('slowing', 3)
                
                try:
                    # Verilerin güncel olmasını zorlamak için önbelleği atla
                    stoch_data = stochastic.calculate(df, k_period=k_period, d_period=d_period, slowing=slowing)
                    
                    if not stoch_data.empty:
                        # Son değerleri al
                        k_line = stoch_data['k_line'].iloc[-1]
                        d_line = stoch_data['d_line'].iloc[-1]
                        
                        # Önceki değerler
                        k_line_prev = stoch_data['k_line'].iloc[-2] if len(stoch_data) > 1 else k_line
                        d_line_prev = stoch_data['d_line'].iloc[-2] if len(stoch_data) > 1 else d_line
                        
                        # Stochastic sinyalini belirle
                        stoch_signal = stochastic.get_signal(k_line, d_line)
                        
                        # Değerleri 6 haneye sınırla
                        k_line_formatted = round(float(k_line), 6) if not np.isnan(k_line) else None
                        d_line_formatted = round(float(d_line), 6) if not np.isnan(d_line) else None
                        k_prev_formatted = round(float(k_line_prev), 6) if not np.isnan(k_line_prev) else None
                        d_prev_formatted = round(float(d_line_prev), 6) if not np.isnan(d_line_prev) else None
                        
                        indicators['stochastic'] = {
                            'k_line': k_line_formatted,
                            'd_line': d_line_formatted,
                            'k_prev': k_prev_formatted,
                            'd_prev': d_prev_formatted,
                            'k_period': k_period,
                            'd_period': d_period,
                            'slowing': slowing,
                            'signal': stoch_signal,
                            'timestamp': pd.Timestamp.now().timestamp()  # Zaman damgası ekleyelim
                        }
                        logger.debug(f"Stochastic hesaplandı: K={k_line_formatted:.6f}, D={d_line_formatted:.6f}, Sinyal={stoch_signal}")
                    else:
                        logger.warning(f"Stochastic verisi boş, hesaplanamadı")
                except Exception as stoch_error:
                    logger.error(f"Stochastic hesaplanırken hata: {str(stoch_error)}")
            
            # 6. ADX (Average Directional Index) hesapla
            if self.config.get('indicators', {}).get('adx', {}).get('enabled', True):
                from indicators import adx
                adx_period = self.config.get('indicators', {}).get('adx', {}).get('period', 14)
                
                # Verilerin güncel olmasını zorlamak için önbelleği atla
                adx_data = adx.calculate(df, period=adx_period)
                
                if not adx_data.empty and not adx_data['adx'].isna().all():
                    # Son değerleri al
                    adx_value = adx_data['adx'].iloc[-1]
                    plus_di = adx_data['plus_di'].iloc[-1]
                    minus_di = adx_data['minus_di'].iloc[-1]
                    
                    # ADX sinyalini belirle
                    adx_signal = adx.get_signal(adx_value, plus_di, minus_di)
                    
                    # Değerleri 6 haneye sınırla
                    adx_value_formatted = round(float(adx_value), 6)
                    plus_di_formatted = round(float(plus_di), 6)
                    minus_di_formatted = round(float(minus_di), 6)
                    
                    indicators['adx'] = {
                        'adx': adx_value_formatted,
                        'plus_di': plus_di_formatted,
                        'minus_di': minus_di_formatted,
                        'period': adx_period,
                        'signal': adx_signal,
                        'timestamp': pd.Timestamp.now().timestamp()  # Zaman damgası ekleyelim
                    }
                    logger.debug(f"ADX hesaplandı: ADX={adx_value_formatted:.6f}, +DI={plus_di_formatted:.6f}, -DI={minus_di_formatted:.6f}, Sinyal={adx_signal}")
                else:
                    logger.warning(f"ADX verisi boş veya NaN değerler içeriyor, hesaplanamadı")
            
            # 7. Parabolic SAR hesapla
            if self.config.get('indicators', {}).get('parabolic_sar', {}).get('enabled', True):
                from indicators import parabolic_sar
                acceleration = self.config.get('indicators', {}).get('parabolic_sar', {}).get('acceleration', 0.02)
                maximum = self.config.get('indicators', {}).get('parabolic_sar', {}).get('maximum', 0.2)
                
                try:
                    # Verilerin güncel olmasını zorlamak için önbelleği atla
                    sar_values = parabolic_sar.calculate(df, acceleration=acceleration, maximum=maximum)
                    
                    if not sar_values.isna().all():
                        # Son değeri al
                        sar_value = sar_values.iloc[-1]
                        close = df['close'].iloc[-1]
                        
                        # SAR sinyalini belirle
                        sar_signal = parabolic_sar.get_signal(close, sar_value)
                        
                        # Önceki değerleri al ve trend belirle
                        if len(sar_values) > 1:
                            sar_prev = sar_values.iloc[-2]
                            price_prev = df['close'].iloc[-2]
                            # Trend değişimi kontrol et
                            trend_changed = (close > sar_value and price_prev <= sar_prev) or (close < sar_value and price_prev >= sar_prev)
                        else:
                            sar_prev = sar_value
                            trend_changed = False
                        
                        # Değerleri 6 haneye sınırla
                        sar_value_formatted = round(float(sar_value), 6) if not np.isnan(sar_value) else None
                        sar_prev_formatted = round(float(sar_prev), 6) if not np.isnan(sar_prev) else None
                        
                        indicators['parabolic_sar'] = {
                            'value': sar_value_formatted,
                            'prev_value': sar_prev_formatted,
                            'acceleration': acceleration,
                            'maximum': maximum,
                            'signal': sar_signal,
                            'trend_changed': trend_changed,
                            'timestamp': pd.Timestamp.now().timestamp()  # Zaman damgası ekleyelim
                        }
                        logger.debug(f"PSAR hesaplandı: Değer={sar_value_formatted:.6f}, Sinyal={sar_signal}, Trend değişimi={trend_changed}")
                    else:
                        logger.warning(f"PSAR verisi NaN değerler içeriyor, hesaplanamadı")
                except Exception as sar_error:
                    logger.error(f"PSAR hesaplanırken hata: {str(sar_error)}")
            
            # 8. Ichimoku Cloud hesapla
            if self.config.get('indicators', {}).get('ichimoku', {}).get('enabled', True):
                from indicators import ichimoku
                tenkan_period = self.config.get('indicators', {}).get('ichimoku', {}).get('tenkan_period', 9)
                kijun_period = self.config.get('indicators', {}).get('ichimoku', {}).get('kijun_period', 26)
                senkou_span_b_period = self.config.get('indicators', {}).get('ichimoku', {}).get('senkou_span_b_period', 52)
                displacement = self.config.get('indicators', {}).get('ichimoku', {}).get('displacement', 26)
                
                ichimoku_data = ichimoku.calculate(df, tenkan_period=tenkan_period, 
                                                  kijun_period=kijun_period, 
                                                  senkou_span_b_period=senkou_span_b_period, 
                                                  displacement=displacement)
                
                if not ichimoku_data.empty:
                    # Son değerleri al
                    ichimoku_values = {
                        'tenkan_sen': ichimoku_data['tenkan_sen'].iloc[-1] if 'tenkan_sen' in ichimoku_data else None,
                        'kijun_sen': ichimoku_data['kijun_sen'].iloc[-1] if 'kijun_sen' in ichimoku_data else None,
                        'senkou_span_a': ichimoku_data['senkou_span_a'].iloc[-1] if 'senkou_span_a' in ichimoku_data else None,
                        'senkou_span_b': ichimoku_data['senkou_span_b'].iloc[-1] if 'senkou_span_b' in ichimoku_data else None,
                        'chikou_span': ichimoku_data['chikou_span'].iloc[-1] if 'chikou_span' in ichimoku_data else None
                    }
                    
                    close = df['close'].iloc[-1]
                    
                    # Ichimoku sinyalini belirle
                    ichimoku_signal = ichimoku.get_signal(close, ichimoku_values)
                    
                    indicators['ichimoku'] = {
                        'tenkan_sen': float(ichimoku_values['tenkan_sen']) if ichimoku_values['tenkan_sen'] is not None and not np.isnan(ichimoku_values['tenkan_sen']) else None,
                        'kijun_sen': float(ichimoku_values['kijun_sen']) if ichimoku_values['kijun_sen'] is not None and not np.isnan(ichimoku_values['kijun_sen']) else None,
                        'senkou_span_a': float(ichimoku_values['senkou_span_a']) if ichimoku_values['senkou_span_a'] is not None and not np.isnan(ichimoku_values['senkou_span_a']) else None,
                        'senkou_span_b': float(ichimoku_values['senkou_span_b']) if ichimoku_values['senkou_span_b'] is not None and not np.isnan(ichimoku_values['senkou_span_b']) else None,
                        'chikou_span': float(ichimoku_values['chikou_span']) if ichimoku_values['chikou_span'] is not None and not np.isnan(ichimoku_values['chikou_span']) else None,
                        'signal': ichimoku_signal
                    }
                
            # 9. SuperTrend hesapla
            if self.config.get('indicators', {}).get('supertrend', {}).get('enabled', True):
                from indicators import supertrend
                period = self.config.get('indicators', {}).get('supertrend', {}).get('period', 10)
                multiplier = self.config.get('indicators', {}).get('supertrend', {}).get('multiplier', 3)
                
                st_data = supertrend.calculate(df, period=period, multiplier=multiplier)
                
                if st_data is not None:
                    # Son değerleri al
                    st_value = st_data['supertrend'].iloc[-1]
                    trend_direction = st_data['trend_direction'].iloc[-1]
                    atr = st_data['atr'].iloc[-1]
                    close = df['close'].iloc[-1]
                    
                    # Son birkaç değeri al ve trend değişimi kontrol et
                    if len(st_data) >= 3:
                        prev_trend_direction = st_data['trend_direction'].iloc[-2]
                    else:
                        prev_trend_direction = trend_direction
                    
                    # Gelişmiş SuperTrend sinyal ayarları
                    st_flip_to_buy = self.signal_settings.get('supertrend', {}).get('flip_to_buy', True)
                    st_flip_to_sell = self.signal_settings.get('supertrend', {}).get('flip_to_sell', True)
                    st_require_confirmation = self.signal_settings.get('supertrend', {}).get('require_confirmation', False)
                    st_min_trend_duration = self.signal_settings.get('supertrend', {}).get('min_trend_duration', 3)
                    
                    # Trend devam sayısı
                    trend_duration = 1
                    for i in range(2, min(st_min_trend_duration + 2, len(st_data))):
                        if st_data['trend_direction'].iloc[-i] == trend_direction:
                            trend_duration += 1
                        else:
                            break
                    
                    # SuperTrend sinyalini belirle
                    st_signal = 'NEUTRAL'
                    
                    # Trend yönü değişiminden sinyal üretme (flip)
                    if st_flip_to_buy and trend_direction > 0 and prev_trend_direction <= 0:
                        if not st_require_confirmation or trend_duration >= st_min_trend_duration:
                            st_signal = 'BUY'  # Yukarı trend başlangıcı
                    elif st_flip_to_sell and trend_direction < 0 and prev_trend_direction >= 0:
                        if not st_require_confirmation or trend_duration >= st_min_trend_duration:
                            st_signal = 'SELL'  # Aşağı trend başlangıcı
                    # Mevcut trend doğrultusunda sinyal
                    elif trend_direction > 0 and trend_duration >= st_min_trend_duration:
                        st_signal = 'BUY'  # Devam eden yukarı trend
                    elif trend_direction < 0 and trend_duration >= st_min_trend_duration:
                        st_signal = 'SELL'  # Devam eden aşağı trend
                    
                    indicators['supertrend'] = {
                        'value': float(st_value),
                        'trend_direction': float(trend_direction),
                        'atr': float(atr),
                        'period': period,
                        'multiplier': multiplier,
                        'trend_duration': trend_duration,
                        'signal': st_signal
                    }
            
            # 10. VWAP (Volume Weighted Average Price) hesapla
            if self.config.get('indicators', {}).get('vwap', {}).get('enabled', True):
                from indicators import vwap
                vwap_period = self.config.get('indicators', {}).get('vwap', {}).get('period')
                
                vwap_values = vwap.calculate(df, period=vwap_period)
                
                if not vwap_values.isna().all():
                    # Son değeri al
                    vwap_value = vwap_values.iloc[-1]
                    close = df['close'].iloc[-1]
                    
                    # VWAP sinyalini belirle
                    vwap_signal = vwap.get_signal(close, vwap_value)
                    
                    indicators['vwap'] = {
                        'value': float(vwap_value),
                        'period': vwap_period,
                        'signal': vwap_signal
                    }
            
            # 11. Pivot Points (Pivot Noktaları) hesapla
            if self.config.get('indicators', {}).get('pivot_points', {}).get('enabled', True):
                from indicators import pivot_points
                method = self.config.get('indicators', {}).get('pivot_points', {}).get('method', 'standard')
                
                pivot_data = pivot_points.calculate(df, method=method)
                
                if pivot_data is not None:
                    close = df['close'].iloc[-1]
                    
                    # Pivot Points sinyalini belirle
                    pivot_signal_data = pivot_points.get_signal(close, pivot_data)
                    
                    # Pivot değerlerini al
                    indicators['pivot_points'] = {
                        'pivot': float(pivot_data['pivot']),
                        'method': method,
                        'signal': pivot_signal_data['signal'],
                        'reason': pivot_signal_data['reason']
                    }
                    
                    # R1, R2, R3, S1, S2, S3 değerlerini ekle (varsa)
                    for level in ['r1', 'r2', 'r3', 's1', 's2', 's3', 'r4', 's4']:
                        if level in pivot_data:
                            indicators['pivot_points'][level] = float(pivot_data[level])

            # Fibonacci Retracement ve Genişleme Seviyeleri için
            if self.config.get('indicators', {}).get('fibonacci', {}).get('enabled', True):
                try:
                    # Son 100 mum için yüksek ve düşük
                    high_point = df['high'].iloc[-100:].max()
                    low_point = df['low'].iloc[-100:].min()
                    
                    # Fibonacci seviyeleri
                    fibonacci_levels = {
                        '0.0': low_point,
                        '0.236': low_point + 0.236 * (high_point - low_point),
                        '0.382': low_point + 0.382 * (high_point - low_point),
                        '0.5': low_point + 0.5 * (high_point - low_point),
                        '0.618': low_point + 0.618 * (high_point - low_point),
                        '0.786': low_point + 0.786 * (high_point - low_point),
                        '1.0': high_point
                    }
                    
                    close = df['close'].iloc[-1]
                    
                    # Fibonacci seviyelerine göre sinyal
                    fib_signal = 'NEUTRAL'
                            
                    # Destek bölgesi (0.236-0.382)
                    if fibonacci_levels['0.236'] <= close <= fibonacci_levels['0.382']:
                        # Trend yukarı ise
                        if df['close'].iloc[-1] > df['close'].iloc[-10]:
                            fib_signal = 'BUY'
                                        
                    # Direnç bölgesi (0.618-0.786)
                    elif fibonacci_levels['0.618'] <= close <= fibonacci_levels['0.786']:
                        # Trend aşağı ise
                        if df['close'].iloc[-1] < df['close'].iloc[-10]:
                            fib_signal = 'SELL'
                             
                    indicators['fibonacci'] = {
                        'levels': fibonacci_levels,
                        'signal': fib_signal
                    }
                except Exception as e:
                    logger.error(f"Fibonacci hesaplama hatası: {str(e)}")
            
            # İndikatör sinyallerini değerlendirerek bütünleşik bir sinyal üret
            indicators['consolidated_signal'] = self.evaluate_indicator_signals(indicators, symbol)
            
            # Göstergeleri geliştirilmiş önbellek mekanizmasıyla kaydet
            self.update_cache(symbol, indicators, timeframe)
            
            return indicators
            
        except Exception as e:
            logger.error(f"{symbol} için göstergeler hesaplanırken hata: {str(e)}")
            return {}
    
    def evaluate_indicator_signals(self, indicators, symbol):
        """
        Farklı indikatörlerden gelen sinyalleri değerlendirir ve birleşik bir sinyal üretir.
        
        :param indicators: Hesaplanmış indikatörler sözlüğü
        :param symbol: Coin sembolü
        :return: Birleşik sinyal sonucu
        """
        try:
            if not indicators:
                return {'signal': 'NEUTRAL', 'strength': 0, 'reason': 'İndikatör verisi bulunamadı'}
            
            # Alım ve satım sinyali sayaçları
            buy_count = 0
            sell_count = 0
            neutral_count = 0
            
            # Ağırlıklı sinyal puanı
            weighted_buy_score = 0
            weighted_sell_score = 0
            total_weight = 0
            
            # Sinyal üreten indikatörler
            buy_indicators = []
            sell_indicators = []
            
            # Her bir indikatörü değerlendir
            for indicator_name, indicator_data in indicators.items():
                # Bazı özel alanları atla
                if indicator_name in ['consolidated_signal', 'fibonacci']:
                    continue
                    
                if isinstance(indicator_data, dict) and 'signal' in indicator_data:
                    # İndikatörün ağırlığını belirle
                    weight = self.indicator_weights.get(indicator_name.replace('_', '').lower(), 10)  # Varsayılan ağırlık: 10
                    total_weight += weight
                    
                    if indicator_data['signal'] == 'BUY':
                        buy_count += 1
                        weighted_buy_score += weight
                        buy_indicators.append(indicator_name)
                    elif indicator_data['signal'] == 'SELL':
                        sell_count += 1
                        weighted_sell_score += weight
                        sell_indicators.append(indicator_name)
                    else:
                        neutral_count += 1
            
            # Toplam sinyal sayısı
            total_signals = buy_count + sell_count + neutral_count
            
            if total_signals == 0 or total_weight == 0:
                return {'signal': 'NEUTRAL', 'strength': 0, 'reason': 'Sinyal üreten indikatör yok'}
            
            # Hangi sinyalin daha güçlü olduğunu belirle
            buy_strength = (weighted_buy_score / total_weight) * 100
            sell_strength = (weighted_sell_score / total_weight) * 100
            
            # Sinyallerin çatışma durumu
            signals_conflict = buy_count > 0 and sell_count > 0
            
            # Final sinyal kararı
            final_signal = 'NEUTRAL'
            signal_strength = 0
            signal_reason = ""
            
            # Konsensüs metoduna göre karar ver
            if self.consensus_method == 'weighted':
                # Ağırlıklı ortalama
                if buy_strength > sell_strength and buy_strength >= self.signal_strength_threshold:
                    final_signal = 'BUY'
                    signal_strength = buy_strength
                    signal_reason = f"Ağırlıklı ALIM sinyali (%{buy_strength:.1f}): {', '.join(buy_indicators)}"
                elif sell_strength > buy_strength and sell_strength >= self.signal_strength_threshold:
                    final_signal = 'SELL'
                    signal_strength = sell_strength
                    signal_reason = f"Ağırlıklı SATIM sinyali (%{sell_strength:.1f}): {', '.join(sell_indicators)}"
                else:
                    if buy_strength > sell_strength:
                        signal_reason = f"ALIM sinyali yetersiz güçte (%{buy_strength:.1f} < %{self.signal_strength_threshold})"
                    elif sell_strength > buy_strength:
                        signal_reason = f"SATIM sinyali yetersiz güçte (%{sell_strength:.1f} < %{self.signal_strength_threshold})"
                    else:
                        signal_reason = "Sinyal dengede veya yeterli değil"
                    
                    # NEUTRAL durumunda güç değerini dinamik olarak belirle
                    signal_strength = max(0, min(50, max(buy_strength, sell_strength)))
            
            elif self.consensus_method == 'majority':
                # Basit çoğunluk
                if buy_count > total_signals / 2:
                    final_signal = 'BUY'
                    signal_strength = (buy_count / total_signals) * 100
                    signal_reason = f"Çoğunluk ALIM sinyali ({buy_count}/{total_signals}): {', '.join(buy_indicators)}"
                elif sell_count > total_signals / 2:
                    final_signal = 'SELL'
                    signal_strength = (sell_count / total_signals) * 100
                    signal_reason = f"Çoğunluk SATIM sinyali ({sell_count}/{total_signals}): {', '.join(sell_indicators)}"
                else:
                    signal_reason = "Açık çoğunluk yok"
                    # NEUTRAL durumunda güç değerini dinamik olarak belirle
                    signal_strength = max(20, min(50, (neutral_count / total_signals) * 100))
            
            elif self.consensus_method == 'strict':
                # Sıkı konsensus (tüm indikatörler aynı sinyali vermeli)
                if buy_count > 0 and sell_count == 0:
                    final_signal = 'BUY'
                    signal_strength = (buy_count / total_signals) * 100
                    signal_reason = f"Tüm aktif indikatörler ALIM sinyali veriyor: {', '.join(buy_indicators)}"
                elif sell_count > 0 and buy_count == 0:
                    final_signal = 'SELL'
                    signal_strength = (sell_count / total_signals) * 100
                    signal_reason = f"Tüm aktif indikatörler SATIM sinyali veriyor: {', '.join(sell_indicators)}"
                else:
                    signal_reason = "İndikatörler arasında uyumsuzluk var"
                    # NEUTRAL durumunda güç değerini dinamik olarak belirle
                    signal_strength = max(10, min(40, (neutral_count / total_signals) * 70))
            
            # Çelişkili sinyaller durumunda ne yapılacak
            if signals_conflict and self.conflicting_signals_handling == 'no_action':
                final_signal = 'NEUTRAL'
                signal_reason = "Çelişkili sinyaller, işlem yapılmıyor"
                # Çelişkili sinyallerde güç değerini hesapla
                signal_strength = 30 + max(0, min(30, (max(buy_strength, sell_strength) - 50)))
            elif signals_conflict and self.conflicting_signals_handling == 'follow_trend':
                # Trend yönünde hareket et
                # Bu kısmı genişletebilirsiniz, şu an sadece moving average'a bakıyor
                if 'moving_averages' in indicators and indicators['moving_averages']['signal'] != 'NEUTRAL':
                    final_signal = indicators['moving_averages']['signal']
                    signal_reason = f"Çelişkili sinyaller, trend takip ediliyor ({final_signal})"
                    signal_strength = max(buy_strength, sell_strength)
            
            # Supertrend'e özel: Güçlü bir sinyal verici olarak ayrıca değerlendir
            if 'supertrend' in indicators and indicators['supertrend']['signal'] != 'NEUTRAL':
                supertrend_signal = indicators['supertrend']['signal']
                supertrend_duration = indicators['supertrend'].get('trend_duration', 1)
                
                # Uzun süreli Supertrend sinyalleri daha güvenilirdir
                if supertrend_duration >= 5 and final_signal == 'NEUTRAL':
                    final_signal = supertrend_signal
                    signal_strength = 60 + min(supertrend_duration * 2, 30)  # Max 90%
                    signal_reason = f"Uzun süreli ({supertrend_duration} bar) SuperTrend {supertrend_signal} sinyali"
            
            # Sonucu logla ve döndür
            logger.info(f"{symbol} için birleştirilmiş sinyal: {final_signal} (Güç: %{signal_strength:.1f}) - {signal_reason}")
            
            return {
                'signal': final_signal,
                'strength': signal_strength,
                'buy_count': buy_count,
                'sell_count': sell_count,
                'neutral_count': neutral_count,
                'total_indicators': total_signals,
                'buy_strength': buy_strength,
                'sell_strength': sell_strength,
                'reason': signal_reason,
                'timestamp': pd.Timestamp.now().timestamp()  # Zaman damgası ekleyelim
            }
            
        except Exception as e:
            logger.error(f"{symbol} için indikatör sinyalleri değerlendirilirken hata: {str(e)}")
            return {'signal': 'NEUTRAL', 'strength': 0, 'reason': f'Hata: {str(e)}'}
            
    def calculate_multi_timeframe_indicators(self, multi_tf_data, symbol):
        """
        Birden fazla zaman aralığındaki verileri kullanarak teknik göstergeleri hesaplar
        
        :param multi_tf_data: Farklı zaman aralıklarındaki OHLCV verilerini içeren sözlük
        :param symbol: Coin sembolü
        :return: Farklı zaman aralıklarındaki göstergeleri içeren sözlük
        """
        try:
            logger.info(f"{symbol} için çoklu zaman aralığında ({len(multi_tf_data)} timeframe) göstergeler hesaplanıyor...")
            multi_tf_indicators = {}
            
            # Her bir zaman aralığı için indikatörleri hesapla
            for timeframe, ohlcv_data in multi_tf_data.items():
                # Her bir zaman aralığı için indikatörleri hesapla (önbellek kullanarak)
                indicators = self.calculate_indicators(ohlcv_data, symbol, timeframe=timeframe)
                
                # Sonuçları kaydet
                multi_tf_indicators[timeframe] = indicators
                
                # Zaman aralıkları arasında küçük bir gecikme ekle (API rate limit koruması)
                time.sleep(0.1)
            
            # Çoklu zaman aralığı analizi yap
            combined_signal = self.combine_timeframe_signals(multi_tf_indicators, symbol)
            multi_tf_indicators['combined'] = combined_signal
            
            return multi_tf_indicators
            
        except Exception as e:
            logger.error(f"{symbol} için çoklu zaman aralığında göstergeler hesaplanırken hata: {str(e)}")
            return {}
    
    def combine_timeframe_signals(self, multi_tf_indicators, symbol):
        """
        Farklı zaman aralıklarındaki sinyal sonuçlarını birleştirir ve bir sonuç üretir
        
        :param multi_tf_indicators: Farklı zaman aralıklarındaki indikatör sonuçları
        :param symbol: Coin sembolü
        :return: Birleştirilmiş sinyal sonuçları
        """
        try:
            if not multi_tf_indicators:
                logger.warning(f"{symbol} için birleştirilecek indikatör bulunamadı")
                return {
                    'trade_signal': 'NEUTRAL', 
                    'weight_sum': 0, 
                    'buy_count': 0, 
                    'sell_count': 0,
                    'neutral_count': 0,
                    'total_timeframes': 0
                }
                
            logger.info(f"{symbol} için {len(multi_tf_indicators)} farklı zaman aralığındaki sinyaller birleştiriliyor")
            
            # Timeframe sınıflandırma ağırlıkları
            short_tf_weight = self.config.get('timeframe_weight_short', 25) / 100  # Kısa vadeli (1m-15m)
            medium_tf_weight = self.config.get('timeframe_weight_medium', 50) / 100  # Orta vadeli (30m-4h)
            long_tf_weight = self.config.get('timeframe_weight_long', 25) / 100  # Uzun vadeli (6h-1w)
            
            # Onaylama gerektiren zaman dilimi sayısı
            confirmation_timeframes = self.signal_settings.get('confirmation_timeframes', 2)
            
            # Her bir zaman aralığının oylarını ve ağırlıklarını hesapla
            buy_votes = 0
            sell_votes = 0
            neutral_votes = 0
            weighted_sum = 0
            total_weight = 0
            
            # Her bir timeframe için ağırlık belirle ve consolidated_signal'ı kullan
            for timeframe, indicators in multi_tf_indicators.items():
                # Timeframe'in ağırlığını belirle
                if timeframe in ['1m', '3m', '5m', '15m']:
                    weight = short_tf_weight
                elif timeframe in ['30m', '1h', '2h', '4h']:
                    weight = medium_tf_weight
                else:  # '6h', '12h', '1d', '3d', '1w'
                    weight = long_tf_weight
                
                # Bu timeframe için consolidated_signal değerlendir
                if 'consolidated_signal' in indicators:
                    signal_info = indicators['consolidated_signal']
                    if signal_info['signal'] == 'BUY':
                        buy_votes += 1
                        weighted_sum += weight
                    elif signal_info['signal'] == 'SELL':
                        sell_votes += 1
                        weighted_sum -= weight
                    else:
                        neutral_votes += 1
                    
                    total_weight += weight
                else:
                    # Eski yöntem - her indikatörü ayrı ayrı say
                    buy_signals = 0
                    sell_signals = 0
                    neutral_signals = 0
                    
                    for indicator_name, indicator_data in indicators.items():
                        if isinstance(indicator_data, dict) and 'signal' in indicator_data:
                            if indicator_data['signal'] == 'BUY':
                                buy_signals += 1
                            elif indicator_data['signal'] == 'SELL':
                                sell_signals += 1
                            else:
                                neutral_signals += 1
                    
                    # Bu timeframe için ALIM veya SATIM eğilimi belirle
                    timeframe_sentiment = 0  # -1: SELL, 0: NEUTRAL, 1: BUY
                    if buy_signals > sell_signals and buy_signals > neutral_signals:
                        timeframe_sentiment = 1  # BUY
                        buy_votes += 1
                    elif sell_signals > buy_signals and sell_signals > neutral_signals:
                        timeframe_sentiment = -1  # SELL
                        sell_votes += 1
                    else:
                        neutral_votes += 1
                    
                    # Ağırlıklı oyları topla
                    weighted_sum += timeframe_sentiment * weight
                    total_weight += weight
            
            # Ağırlık yoksa nötr döndür
            if total_weight == 0:
                logger.warning(f"{symbol} için toplam ağırlık 0, NÖTR sinyal döndürülüyor")
                return {
                    'trade_signal': 'NEUTRAL', 
                    'weight_sum': 0, 
                    'buy_count': 0, 
                    'sell_count': 0,
                    'neutral_count': 0,
                    'total_timeframes': len(multi_tf_indicators)
                }
            
            # Ağırlıklı ortalamalı sinyal hesapla
            weighted_avg = weighted_sum / total_weight
            
            # Konsensus gerekliliğini kontrol et
            consensus_requirement = self.config.get('timeframe_consensus', 'majority')
            
            # Konsensus türüne göre ticaret sinyali belirle
            trade_signal = 'NEUTRAL'
            
            if consensus_requirement == 'any':
                # Herhangi bir timeframe sinyali yeterli
                if buy_votes > 0 and weighted_avg > 0:
                    trade_signal = 'BUY'
                elif sell_votes > 0 and weighted_avg < 0:
                    trade_signal = 'SELL'
            elif consensus_requirement == 'majority':
                # Çoğunluk (>50%)
                total_votes = buy_votes + sell_votes + neutral_votes
                
                if buy_votes > total_votes / 2 and weighted_avg > 0:
                    trade_signal = 'BUY'
                elif sell_votes > total_votes / 2 and weighted_avg < 0:
                    trade_signal = 'SELL'
            elif consensus_requirement == 'strong':
                # Güçlü çoğunluk (>75%)
                total_votes = buy_votes + sell_votes + neutral_votes
                
                if buy_votes > total_votes * 0.75 and weighted_avg > 0:
                    trade_signal = 'BUY'
                elif sell_votes > total_votes * 0.75 and weighted_avg < 0:
                    trade_signal = 'SELL'
            elif consensus_requirement == 'all':
                # Tüm zaman aralıkları
                total_votes = buy_votes + sell_votes + neutral_votes
                
                if buy_votes == total_votes and weighted_avg > 0:
                    trade_signal = 'BUY'
                elif sell_votes == total_votes and weighted_avg < 0:
                    trade_signal = 'SELL'
            
            # Birincil timeframe'e ayrıca bak (çakışma durumlarında)
            primary_tf = self.config.get('primary_timeframe', '1h')
            if primary_tf in multi_tf_indicators and 'consolidated_signal' in multi_tf_indicators[primary_tf]:
                primary_signal_info = multi_tf_indicators[primary_tf]['consolidated_signal']
                primary_signal = primary_signal_info['signal']
                primary_strength = primary_signal_info.get('strength', 0)
                
                # Birincil timeframe'de güçlü sinyal varsa ve onaylama sayısı yeterliyse
                if primary_signal != 'NEUTRAL' and primary_strength >= 70 and (
                    (primary_signal == 'BUY' and buy_votes >= confirmation_timeframes) or 
                    (primary_signal == 'SELL' and sell_votes >= confirmation_timeframes)
                ):
                    trade_signal = primary_signal
                    logger.info(f"{symbol} için birincil timeframe ({primary_tf}) güçlü {primary_signal} sinyali üretiyor")
            
            result = {
                'trade_signal': trade_signal,
                'weight_sum': weighted_avg,
                'buy_count': buy_votes,
                'sell_count': sell_votes,
                'neutral_count': neutral_votes,
                'total_timeframes': len(multi_tf_indicators)
            }
            
            logger.info(f"{symbol} için birleşik sinyal: {trade_signal} (Ağırlıklı Ort: {weighted_avg:.2f}, Alım: {buy_votes}, Satım: {sell_votes}, Nötr: {neutral_votes})")
            return result
            
        except Exception as e:
            logger.error(f"{symbol} için zaman aralığı sinyalleri birleştirilirken hata: {str(e)}")
            return {
                'trade_signal': 'NEUTRAL', 
                'weight_sum': 0, 
                'buy_count': 0, 
                'sell_count': 0,
                'neutral_count': 0,
                'total_timeframes': 0
            }
    
    def get_cached_indicators(self, symbol, timeframe='1h', max_age_seconds=60):
        """
        Belirtilen sembol ve zaman dilimi için önbellekteki indikatörleri getirir.
        Eğer önbellekteki veri belirtilen süreden daha eskiyse veya yoksa None döndürür.
        
        :param symbol: Coin sembolü
        :param timeframe: Zaman dilimi (varsayılan: 1h)
        :param max_age_seconds: Önbelleğin maksimum yaşı (saniye cinsinden, varsayılan: 60)
        :return: Önbellekteki indikatör verisi veya None
        """
        cache_key = f"{symbol}_{timeframe}"
        
        if cache_key in self.indicators_data:
            cached_data = self.indicators_data[cache_key]
            timestamp = cached_data.get('timestamp')
            
            if timestamp:
                # Zaman farkını hesapla
                now = pd.Timestamp.now()
                age_seconds = (now - timestamp).total_seconds()
                
                # Önbellek hala taze mi?
                if age_seconds <= max_age_seconds:
                    logger.debug(f"{symbol} için önbellekteki veri kullanılıyor (yaş: {age_seconds:.1f} sn)")
                    return cached_data.get('data')
                else:
                    logger.debug(f"{symbol} için önbellekteki veri çok eski (yaş: {age_seconds:.1f} sn > {max_age_seconds} sn)")
        
        return None
        
    def update_cache(self, symbol, data, timeframe='1h'):
        """
        İndikatör verilerini önbelleğe kaydeder
        
        :param symbol: Coin sembolü
        :param data: Kaydedilecek indikatör verisi
        :param timeframe: Zaman dilimi
        """
        cache_key = f"{symbol}_{timeframe}"
        
        self.indicators_data[cache_key] = {
            'timestamp': pd.Timestamp.now(),
            'data': data
        }
        
        # Önbellek boyutunu kontrol et ve gerekirse temizle
        if len(self.indicators_data) > 100:  # Maksimum 100 sembol önbellekte tutulur
            # En eski veriyi bul ve sil
            oldest_key = None
            oldest_time = pd.Timestamp.now()
            
            for key, item in self.indicators_data.items():
                if item['timestamp'] < oldest_time:
                    oldest_time = item['timestamp']
                    oldest_key = key
            
            # En eskiyi sil
            if oldest_key:
                del self.indicators_data[oldest_key]
                logger.debug(f"Önbellek temizleniyor, en eski veri silindi: {oldest_key}")

    def optimize_api_calls(self, symbols, timeframes):
        """
        API çağrılarını optimize etmek için bir zamanlama ve gruplama mekanizması
        
        :param symbols: İşlem yapılacak semboller listesi
        :param timeframes: Kullanılacak zaman dilimleri listesi
        :return: Optimize edilmiş çağrı planı
        """
        try:
            # Sembollerin önceliğini belirle
            priority_symbols = []
            normal_symbols = []
            
            # Bazı semboller daha yüksek önceliğe sahip olabilir (örn. aktif işlemdeki semboller)
            for symbol in symbols:
                if hasattr(self, 'active_trades') and symbol in self.active_trades:
                    priority_symbols.append(symbol)
                else:
                    normal_symbols.append(symbol)
            
            # Öncelikli zaman aralıkları
            priority_timeframes = ['1h', '4h', '1d']  # Ana zaman aralıkları
            other_timeframes = [tf for tf in timeframes if tf not in priority_timeframes]
            
            # Önbellek durumunu kontrol et
            cache_status = {}
            for symbol in symbols:
                cache_status[symbol] = {}
                for tf in timeframes:
                    cache_key = f"{symbol}_{tf}"
                    is_cached = cache_key in self.indicators_data
                    is_fresh = False
                    
                    if is_cached:
                        timestamp = self.indicators_data[cache_key].get('timestamp')
                        if timestamp:
                            age_seconds = (pd.Timestamp.now() - timestamp).total_seconds()
                            
                            # Zaman aralığına göre önbellek tazeliğini belirle
                            max_age = 300  # 5 dakika (varsayılan)
                            
                            if tf == '1m':
                                max_age = 60  # 1 dakika
                            elif tf == '5m':
                                max_age = 180  # 3 dakika
                            elif tf == '15m':
                                max_age = 300  # 5 dakika
                            elif tf == '30m':
                                max_age = 900  # 15 dakika
                            elif tf == '1h':
                                max_age = 1800  # 30 dakika
                            elif tf in ['4h', '1d']:
                                max_age = 3600  # 1 saat
                            
                            is_fresh = age_seconds <= max_age
                    
                    cache_status[symbol][tf] = is_fresh
            
            # Çağrı planı oluştur
            call_plan = []
            
            # 1. Önce öncelikli semboller ve zaman aralıkları (önbelleğe alınmamış)
            for symbol in priority_symbols:
                for tf in priority_timeframes:
                    if not cache_status[symbol][tf]:
                        call_plan.append((symbol, tf))
            
            # 2. Sonra normal semboller ve öncelikli zaman aralıkları (önbelleğe alınmamış)
            for symbol in normal_symbols:
                for tf in priority_timeframes:
                    if not cache_status[symbol][tf]:
                        call_plan.append((symbol, tf))
            
            # 3. Öncelikli semboller ve diğer zaman aralıkları (önbelleğe alınmamış)
            for symbol in priority_symbols:
                for tf in other_timeframes:
                    if not cache_status[symbol][tf]:
                        call_plan.append((symbol, tf))
            
            # 4. Son olarak normal semboller ve diğer zaman aralıkları (önbelleğe alınmamış)
            for symbol in normal_symbols:
                for tf in other_timeframes:
                    if not cache_status[symbol][tf]:
                        call_plan.append((symbol, tf))
            
            # 5. Rate limiting koruması için grup boyutunu belirle
            max_batch_size = min(len(call_plan), 10)  # En fazla 10 çağrı gruplanabilir
            
            batched_plan = []
            for i in range(0, len(call_plan), max_batch_size):
                batch = call_plan[i:i+max_batch_size]
                batched_plan.append(batch)
            
            logger.info(f"API çağrı planı oluşturuldu: {len(call_plan)} çağrı, {len(batched_plan)} grup")
            return batched_plan
        
        except Exception as e:
            logger.error(f"API çağrı planı oluşturulurken hata: {str(e)}")
            # Hata durumunda basit plan döndür
            return [[(symbol, tf) for tf in timeframes] for symbol in symbols]
    
    def should_recalculate_indicators(self, symbol, timeframe, max_age_seconds=None):
        """
        Belirli bir sembol ve zaman dilimi için indikatörlerin yeniden hesaplanması gerekip
        gerekmediğini kontrol eder.
        
        :param symbol: Sembol adı
        :param timeframe: Zaman dilimi
        :param max_age_seconds: Maximum önbellek yaşı (saniye), None ise otomatik belirlenir
        :return: Boolean - Yeniden hesaplanmalı mı?
        """
        cache_key = f"{symbol}_{timeframe}"
        
        # Önbellekte var mı?
        if cache_key not in self.indicators_data:
            return True
        
        # Önbellek zaman damgası
        timestamp = self.indicators_data[cache_key].get('timestamp')
        if not timestamp:
            return True
        
        # Zaman aralığına göre önbellek maksimum yaşını belirle
        if max_age_seconds is None:
            if timeframe == '1m':
                max_age_seconds = 60  # 1 dakika
            elif timeframe == '5m':
                max_age_seconds = 180  # 3 dakika
            elif timeframe == '15m':
                max_age_seconds = 300  # 5 dakika
            elif timeframe == '30m':
                max_age_seconds = 900  # 15 dakika
            elif timeframe == '1h':
                max_age_seconds = 1800  # 30 dakika
            elif timeframe in ['4h', '1d']:
                max_age_seconds = 3600  # 1 saat
            else:
                max_age_seconds = 600  # Varsayılan: 10 dakika
        
        # Yaş kontrolü
        now = pd.Timestamp.now()
        age_seconds = (now - timestamp).total_seconds()
        
        return age_seconds > max_age_seconds
    
    def calculate_multi_timeframe_indicators_optimized(self, multi_tf_data, symbol, use_cache=True):
        """
        Birden fazla zaman aralığındaki verileri kullanarak teknik göstergeleri optimize edilmiş
        şekilde hesaplar.
        
        :param multi_tf_data: Farklı zaman aralıklarındaki OHLCV verilerini içeren sözlük
        :param symbol: Coin sembolü
        :param use_cache: Önbellek kullanılsın mı
        :return: Farklı zaman aralıklarındaki göstergeleri içeren sözlük
        """
        try:
            logger.info(f"{symbol} için çoklu zaman aralığında ({len(multi_tf_data)} timeframe) göstergeler hesaplanıyor...")
            multi_tf_indicators = {}
            calculation_order = []
            
            # Önce hangi timeframe'lerin hesaplanması gerektiğini belirle
            for timeframe, ohlcv_data in multi_tf_data.items():
                if not use_cache or self.should_recalculate_indicators(symbol, timeframe):
                    calculation_order.append(timeframe)
                else:
                    # Önbellekten al
                    cached_data = self.get_cached_indicators(symbol, timeframe)
                    if cached_data:
                        multi_tf_indicators[timeframe] = cached_data
                        logger.debug(f"{symbol} {timeframe} indikatörleri önbellekten alındı")
            
            # Hesaplanacak timeframe'leri optimize et (küçükten büyüğe sırala)
            time_order = {
                '1m': 1, '3m': 2, '5m': 3, '15m': 4, '30m': 5, 
                '1h': 6, '2h': 7, '4h': 8, '6h': 9, '8h': 10,
                '12h': 11, '1d': 12, '3d': 13, '1w': 14, '1M': 15
            }
            calculation_order.sort(key=lambda tf: time_order.get(tf, 99))
            
            # Her bir timeframe için indikatörleri hesapla
            for timeframe in calculation_order:
                ohlcv_data = multi_tf_data.get(timeframe)
                if ohlcv_data is None or ohlcv_data.empty:
                    continue
                
                # Belirli bir zaman aralığı için indikatörleri hesapla (önbellek kullan)
                indicators = self.calculate_indicators(ohlcv_data, symbol, timeframe=timeframe)
                multi_tf_indicators[timeframe] = indicators
                
                # Zaman aralıkları arasında küçük bir gecikme ekle (API rate limit koruması)
                if len(calculation_order) > 3:
                    import time
                    time.sleep(0.1)
            
            # Tüm timeframe'ler hesaplanmışsa, birleşik analiz yap
            if len(multi_tf_indicators.keys()) == len(multi_tf_data.keys()):
                combined_signal = self.combine_timeframe_signals(multi_tf_indicators, symbol)
                multi_tf_indicators['combined'] = combined_signal
            else:
                logger.warning(f"{symbol} için çoklu timeframe analizinde eksik veriler var")
            
            return multi_tf_indicators
            
        except Exception as e:
            logger.error(f"{symbol} için çoklu zaman aralığında göstergeler hesaplanırken hata: {str(e)}")
            return {}