import pandas as pd
import numpy as np
import talib
import logging
from datetime import datetime, timedelta

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

class AdaptiveParameters:
    """
    Piyasa koşullarına göre indikatör ve strateji parametrelerini dinamik olarak ayarlar.
    Volatilite durumuna göre parametreleri otomatik optimize eder.
    """
    
    def __init__(self, config=None):
        """
        AdaptiveParameters sınıfını başlatır.
        
        :param config: Bot yapılandırma ayarları
        """
        self.config = config or {}
        self.adaptive_settings = self.config.get('adaptive_settings', {})
        
        # Adaptif parametrelerin aktif olup olmadığını kontrol et
        self.is_enabled = self.adaptive_settings.get('enabled', True)
        
        # Volatilite değerlendirme periyodu
        self.volatility_period = self.adaptive_settings.get('volatility_period', 14)
        
        # Market durumu sınıflandırması için eşikler
        self.high_volatility_threshold = self.adaptive_settings.get('high_volatility_threshold', 0.04)  # %4
        self.low_volatility_threshold = self.adaptive_settings.get('low_volatility_threshold', 0.01)   # %1
        
        # İndikatör parametreleri için ayarlamalar
        self.adjustment_factor = self.adaptive_settings.get('adjustment_factor', 0.3)  # %30 ayarlama
        
        # Piyasa durumu takibi
        self.market_state = {
            'volatility': 'NORMAL',  # LOW, NORMAL, HIGH
            'trend': 'NEUTRAL',      # UPTREND, DOWNTREND, NEUTRAL
            'momentum': 'NEUTRAL',   # POSITIVE, NEGATIVE, NEUTRAL
            'last_update': datetime.now(),
            'metrics': {}
        }
        
        # Varsayılan indikatör parametreleri
        self.default_parameters = {
            'rsi': {'period': 14, 'oversold': 30, 'overbought': 70},
            'macd': {'fast_period': 12, 'slow_period': 26, 'signal_period': 9},
            'bollinger': {'window': 20, 'num_std': 2.0},
            'supertrend': {'period': 10, 'multiplier': 3.0},
            'adx': {'period': 14}
        }
        
        # Adaptif parametre geçmişi
        self.parameter_history = {}
        
        logger.info(f"Adaptif Parametre Yöneticisi başlatıldı. Aktif: {self.is_enabled}")
    
    def analyze_market_conditions(self, ohlcv_data):
        """
        Piyasa koşullarını analiz eder ve volatilite, trend ve momentum ölçümlerini günceller
        
        :param ohlcv_data: OHLCV veri çerçevesi
        :return: Güncellenmiş piyasa durum raporu
        """
        if not self.is_enabled or ohlcv_data is None or len(ohlcv_data) < self.volatility_period * 2:
            return self.market_state
        
        try:
            # Piyasa volatilitesi hesaplama
            volatility = self.calculate_volatility(ohlcv_data)
            
            # Trend durumu belirleme
            trend = self.determine_trend(ohlcv_data)
            
            # Momentum hesaplama
            momentum = self.calculate_momentum(ohlcv_data)
            
            # Piyasa durumu güncelleme
            self.market_state['volatility'] = volatility['state']
            self.market_state['trend'] = trend['state']
            self.market_state['momentum'] = momentum['state']
            self.market_state['last_update'] = datetime.now()
            
            # Metrikleri kaydet
            self.market_state['metrics'] = {
                'volatility_value': volatility['value'],
                'volatility_percentile': volatility['percentile'],
                'trend_strength': trend['strength'],
                'momentum_value': momentum['value']
            }
            
            # Güncel durumu logla
            logger.info(f"Piyasa durumu: Volatilite {volatility['state']} (%{volatility['value']*100:.1f}), "
                        f"Trend {trend['state']} (Güç: {trend['strength']:.1f}), "
                        f"Momentum {momentum['state']}")
            
            return self.market_state
            
        except Exception as e:
            logger.error(f"Piyasa koşulları analiz edilirken hata: {str(e)}")
            return self.market_state
    
    def calculate_volatility(self, ohlcv_data):
        """
        Piyasa volatilitesini hesaplar
        
        :param ohlcv_data: OHLCV veri çerçevesi
        :return: Volatilite durum raporu
        """
        try:
            # ATR hesapla
            atr = talib.ATR(ohlcv_data['high'].values, 
                            ohlcv_data['low'].values, 
                            ohlcv_data['close'].values, 
                            timeperiod=self.volatility_period)
            
            # Son değeri al
            current_atr = atr[-1]
            
            # Fiyata göre normalize et
            current_price = ohlcv_data['close'].iloc[-1]
            normalized_atr = current_atr / current_price
            
            # Son 100 günlük ATR istatistikleri (yüzdelik dilim belirlemek için)
            atr_history = atr[-100:]
            atr_history = atr_history[~np.isnan(atr_history)]
            
            if len(atr_history) > 0:
                # ATR'nin yüzdelik dilimini hesapla
                atr_percentile = sum(atr_history <= current_atr) / len(atr_history) * 100
            else:
                atr_percentile = 50  # Varsayılan
            
            # Volatilite durumunu belirle
            if normalized_atr >= self.high_volatility_threshold or atr_percentile >= 80:
                volatility_state = 'HIGH'
            elif normalized_atr <= self.low_volatility_threshold or atr_percentile <= 20:
                volatility_state = 'LOW'
            else:
                volatility_state = 'NORMAL'
            
            return {
                'state': volatility_state,
                'value': normalized_atr,
                'percentile': atr_percentile
            }
            
        except Exception as e:
            logger.error(f"Volatilite hesaplanırken hata: {str(e)}")
            return {'state': 'NORMAL', 'value': 0.02, 'percentile': 50}
    
    def determine_trend(self, ohlcv_data):
        """
        Piyasa trendini belirler
        
        :param ohlcv_data: OHLCV veri çerçevesi
        :return: Trend durum raporu
        """
        try:
            # Hareketli ortalamalar hesapla
            ma20 = talib.SMA(ohlcv_data['close'].values, timeperiod=20)
            ma50 = talib.SMA(ohlcv_data['close'].values, timeperiod=50)
            ma100 = talib.SMA(ohlcv_data['close'].values, timeperiod=100)
            
            # Son değerleri al
            last_ma20 = ma20[-1]
            last_ma50 = ma50[-1]
            last_ma100 = ma100[-1]
            
            # Eğimi hesapla
            ma20_slope = (ma20[-1] - ma20[-5]) / 5 if len(ma20) >= 5 else 0
            ma50_slope = (ma50[-1] - ma50[-5]) / 5 if len(ma50) >= 5 else 0
            
            close = ohlcv_data['close'].iloc[-1]
            
            # Trend durumunu belirle
            trend_strength = 0
            
            # Fiyat MA'ların üzerinde/altında
            if close > last_ma20:
                trend_strength += 1
            else:
                trend_strength -= 1
                
            if close > last_ma50:
                trend_strength += 1
            else:
                trend_strength -= 1
                
            if close > last_ma100:
                trend_strength += 1
            else:
                trend_strength -= 1
            
            # MA'ların sıralaması
            if last_ma20 > last_ma50 > last_ma100:
                trend_strength += 2  # Güçlü yukarı trend
            elif last_ma20 < last_ma50 < last_ma100:
                trend_strength -= 2  # Güçlü aşağı trend
            
            # MA eğimleri
            if ma20_slope > 0:
                trend_strength += 1
            elif ma20_slope < 0:
                trend_strength -= 1
                
            if ma50_slope > 0:
                trend_strength += 1
            elif ma50_slope < 0:
                trend_strength -= 1
            
            # Trend durumu
            if trend_strength >= 3:
                trend_state = 'UPTREND'
            elif trend_strength <= -3:
                trend_state = 'DOWNTREND'
            else:
                trend_state = 'NEUTRAL'
            
            return {
                'state': trend_state,
                'strength': trend_strength,
                'ma20': last_ma20,
                'ma50': last_ma50,
                'ma100': last_ma100
            }
            
        except Exception as e:
            logger.error(f"Trend belirlenirken hata: {str(e)}")
            return {'state': 'NEUTRAL', 'strength': 0}
    
    def calculate_momentum(self, ohlcv_data):
        """
        Piyasa momentumunu hesaplar
        
        :param ohlcv_data: OHLCV veri çerçevesi
        :return: Momentum durum raporu
        """
        try:
            # RSI hesapla
            rsi = talib.RSI(ohlcv_data['close'].values, timeperiod=14)
            current_rsi = rsi[-1]
            
            # MACD hesapla
            macd, signal, hist = talib.MACD(
                ohlcv_data['close'].values,
                fastperiod=12, 
                slowperiod=26, 
                signalperiod=9
            )
            current_macd = macd[-1]
            current_signal = signal[-1]
            current_hist = hist[-1]
            
            # Stochastic hesapla
            k, d = talib.STOCH(
                ohlcv_data['high'].values,
                ohlcv_data['low'].values,
                ohlcv_data['close'].values,
                fastk_period=14,
                slowk_period=3,
                slowk_matype=0,
                slowd_period=3,
                slowd_matype=0
            )
            current_k = k[-1]
            current_d = d[-1]
            
            # Momentum faktörleri
            momentum_factors = 0
            
            # RSI
            if current_rsi > 60:
                momentum_factors += 1
            elif current_rsi < 40:
                momentum_factors -= 1
            
            # MACD
            if current_macd > current_signal:
                momentum_factors += 1
            else:
                momentum_factors -= 1
                
            if current_hist > 0:
                momentum_factors += 1
            else:
                momentum_factors -= 1
            
            # Stochastic
            if current_k > current_d and current_k > 50:
                momentum_factors += 1
            elif current_k < current_d and current_k < 50:
                momentum_factors -= 1
            
            # Momentum durumu belirle
            if momentum_factors >= 2:
                momentum_state = 'POSITIVE'
            elif momentum_factors <= -2:
                momentum_state = 'NEGATIVE'
            else:
                momentum_state = 'NEUTRAL'
            
            return {
                'state': momentum_state,
                'value': momentum_factors,
                'rsi': current_rsi,
                'macd': current_macd,
                'macd_hist': current_hist
            }
            
        except Exception as e:
            logger.error(f"Momentum hesaplanırken hata: {str(e)}")
            return {'state': 'NEUTRAL', 'value': 0}
    
    def get_adjusted_parameters(self, indicator, symbol=None):
        """
        Piyasa koşullarına göre ayarlanmış indikatör parametrelerini döndürür
        
        :param indicator: İndikatör adı
        :param symbol: Coin sembolü (isteğe bağlı, her coin için özel ayarlar)
        :return: Ayarlanmış parametreler
        """
        if not self.is_enabled or indicator not in self.default_parameters:
            return self.default_parameters.get(indicator, {})
        
        # Son piyasa durumuna göre parametre ayarla
        try:
            adjusted_params = self.default_parameters[indicator].copy()
            
            # Volatilite durumuna göre ayarla
            if self.market_state['volatility'] == 'HIGH':
                self._adjust_for_high_volatility(adjusted_params, indicator)
            elif self.market_state['volatility'] == 'LOW':
                self._adjust_for_low_volatility(adjusted_params, indicator)
            
            # Trend durumuna göre ayarla
            if self.market_state['trend'] == 'UPTREND':
                self._adjust_for_uptrend(adjusted_params, indicator)
            elif self.market_state['trend'] == 'DOWNTREND':
                self._adjust_for_downtrend(adjusted_params, indicator)
            
            # Sembol bazlı özelleştirme (sembol parametresi verildiyse)
            if symbol:
                self._adjust_for_symbol(adjusted_params, indicator, symbol)
            
            # Ayarlanan parametreleri logla ve kaydet
            logger.debug(f"{indicator} için adaptif parametreler: {adjusted_params}")
            
            # Parametre geçmişine ekle
            if indicator not in self.parameter_history:
                self.parameter_history[indicator] = []
                
            self.parameter_history[indicator].append({
                'timestamp': datetime.now(),
                'parameters': adjusted_params,
                'market_state': self.market_state.copy()
            })
            
            # Geçmiş kayıtları temizle (sadece son 50 değişimi tut)
            if len(self.parameter_history[indicator]) > 50:
                self.parameter_history[indicator] = self.parameter_history[indicator][-50:]
            
            return adjusted_params
            
        except Exception as e:
            logger.error(f"{indicator} için adaptif parametreler hesaplanırken hata: {str(e)}")
            return self.default_parameters.get(indicator, {})
    
    def _adjust_for_high_volatility(self, params, indicator):
        """
        Yüksek volatilite durumunda parametreleri ayarlar
        """
        factor = self.adjustment_factor
        
        if indicator == 'rsi':
            # RSI aşırı alım/satım seviyelerini daha uçlara çek
            params['oversold'] = max(20, params['oversold'] - 5 * factor)
            params['overbought'] = min(80, params['overbought'] + 5 * factor)
            
        elif indicator == 'bollinger':
            # Bollinger bantlarını genişlet
            params['num_std'] = min(3.0, params['num_std'] * (1 + 0.5 * factor))
            
        elif indicator == 'supertrend':
            # SuperTrend çarpanını daha yüksek yap (daha geniş stop)
            params['multiplier'] = min(4.0, params['multiplier'] * (1 + 0.5 * factor))
            
        elif indicator == 'adx':
            # ADX periyodunu kısalt (daha hızlı tepki)
            params['period'] = max(10, int(params['period'] * (1 - 0.3 * factor)))
    
    def _adjust_for_low_volatility(self, params, indicator):
        """
        Düşük volatilite durumunda parametreleri ayarlar
        """
        factor = self.adjustment_factor
        
        if indicator == 'rsi':
            # RSI aşırı alım/satım seviyelerini daha içeri çek
            params['oversold'] = min(35, params['oversold'] + 5 * factor)
            params['overbought'] = max(65, params['overbought'] - 5 * factor)
            
        elif indicator == 'bollinger':
            # Bollinger bantlarını daralt
            params['num_std'] = max(1.5, params['num_std'] * (1 - 0.25 * factor))
            
        elif indicator == 'supertrend':
            # SuperTrend çarpanını daha düşük yap (daha yakın stop)
            params['multiplier'] = max(2.0, params['multiplier'] * (1 - 0.25 * factor))
            
        elif indicator == 'adx':
            # ADX periyodunu uzat (parazitleri filtrele)
            params['period'] = min(20, int(params['period'] * (1 + 0.2 * factor)))
    
    def _adjust_for_uptrend(self, params, indicator):
        """
        Yükselen trend durumunda parametreleri ayarlar
        """
        if indicator == 'rsi':
            # RSI aşırı alım seviyesini yükselt (trend takibi)
            params['overbought'] = min(75, params['overbought'] + 5)
            
        elif indicator == 'macd':
            # MACD'de hızlı EMA'yı daha da hızlandır
            params['fast_period'] = max(8, params['fast_period'] - 2)
    
    def _adjust_for_downtrend(self, params, indicator):
        """
        Düşen trend durumunda parametreleri ayarlar
        """
        if indicator == 'rsi':
            # RSI aşırı satım seviyesini düşür (trend takibi)
            params['oversold'] = max(25, params['oversold'] - 5)
            
        elif indicator == 'macd':
            # MACD'de yavaş EMA'yı daha da yavaşlat
            params['slow_period'] = min(30, params['slow_period'] + 2)
    
    def _adjust_for_symbol(self, params, indicator, symbol):
        """
        Sembol özelinde parametreler ayarlar (örn: bazı coinler daha volatil olabilir)
        
        Not: Bu fonksiyonu genişletmek için sembollerin karakteristiklerini analiz eden
        bir mekanizma geliştirilebilir.
        """
        # Şimdilik özel bir ayarlama yapmıyoruz
        pass
    
    def should_update_parameters(self):
        """
        Parametrelerin güncellenmesi gerekip gerekmediğini kontrol eder
        
        :return: Boolean
        """
        if not self.is_enabled:
            return False
        
        # Son güncelleme zamanını kontrol et
        last_update = self.market_state.get('last_update', datetime.now() - timedelta(hours=1))
        time_since_update = datetime.now() - last_update
        
        # Saatlik güncelleme (veya daha uzun süre geçmişse)
        return time_since_update.total_seconds() > 3600
    
    def get_parameter_change_history(self, indicator=None, limit=10):
        """
        Parametre değişim geçmişini döndürür
        
        :param indicator: İndikatör adı (None ise tüm indikatörler)
        :param limit: Kaç kayıt döndürüleceği
        :return: Parametre geçmişi
        """
        if indicator:
            history = self.parameter_history.get(indicator, [])
            return history[-limit:] if history else []
        else:
            # Tüm indikatörlerin geçmişi
            all_history = {}
            for ind, history in self.parameter_history.items():
                all_history[ind] = history[-limit:] if history else []
            return all_history
