import logging
import pandas as pd
import numpy as np
import time
from datetime import datetime

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

class IntegrationHandler:
    """
    Bot bileşenleri arasındaki entegrasyonu sağlayan sınıf.
    Indicators Manager, Risk Manager ve Adaptive Parameters gibi bileşenlerin
    birlikte çalışmasını koordine eder.
    """
    
    def __init__(self, config=None, indicators_manager=None, risk_manager=None, adaptive_parameters=None):
        """
        IntegrationHandler sınıfını başlatır.
        
        :param config: Bot yapılandırma ayarları
        :param indicators_manager: İndikatör yöneticisi referansı
        :param risk_manager: Risk yöneticisi referansı
        :param adaptive_parameters: Adaptif parametre yöneticisi referansı
        """
        self.config = config or {}
        self.indicators_manager = indicators_manager
        self.risk_manager = risk_manager
        self.adaptive_parameters = adaptive_parameters
        
        # Entegrasyon ayarları
        self.integration_settings = self.config.get('integration_settings', {})
        self.api_call_optimization = self.integration_settings.get('api_call_optimization', True)
        self.use_smart_trend = self.integration_settings.get('use_smart_trend', True)
        
        # API çağrı optimizasyonu için sayaç ve limitler
        self.api_call_counter = 0
        self.api_call_reset_time = datetime.now()
        self.api_call_limit_per_minute = self.integration_settings.get('api_call_limit_per_minute', 60)
        
        logger.info("Entegrasyon Yöneticisi başlatıldı")
    
    def generate_trading_signals(self, symbol, multi_tf_data):
        """
        Tüm bileşenleri entegre ederek ticaret sinyalleri üretir
        
        :param symbol: Coin sembolü
        :param multi_tf_data: Farklı zaman aralıklarındaki OHLCV verileri
        :return: Ticaret sinyal paketi
        """
        try:
            # 1. API çağrılarını kontrol et ve optimize et
            if self.api_call_optimization:
                self._check_api_rate_limits()
            
            # 2. Piyasa koşullarını analiz et (volatilite, trend, momentum)
            primary_timeframe = self.config.get('primary_timeframe', '1h')
            primary_data = multi_tf_data.get(primary_timeframe)
            
            if primary_data is None or primary_data.empty:
                logger.warning(f"{symbol} için {primary_timeframe} verisi bulunamadı")
                primary_timeframe = next(iter(multi_tf_data.keys()), None)
                primary_data = multi_tf_data.get(primary_timeframe)
                
                if primary_data is None or primary_data.empty:
                    logger.error(f"{symbol} için hiç veri bulunamadı")
                    return {'signal': 'NEUTRAL', 'reason': 'Veri yok', 'strength': 0}
            
            # 3. Adaptif parametreleri güncelle
            if self.adaptive_parameters:
                market_conditions = self.adaptive_parameters.analyze_market_conditions(primary_data)
                
                # Adaptif parametrelerle indikatörleri hesapla
                if self.indicators_manager:
                    adjusted_indicators = {}
                    
                    # Her bir indikatör için adaptif parametreler al
                    for indicator in ['rsi', 'macd', 'bollinger', 'supertrend', 'adx']:
                        adjusted_params = self.adaptive_parameters.get_adjusted_parameters(indicator, symbol)
                        adjusted_indicators[indicator] = adjusted_params
                    
                    # Güncellenmiş parametreleri IndicatorsManager'a gönder
                    # (Burada varsayımsal bir fonksiyon kullanıyoruz, gerçek implementasyona göre değişebilir)
                    if hasattr(self.indicators_manager, 'update_indicator_params'):
                        self.indicators_manager.update_indicator_params(adjusted_indicators)
            
            # 4. İndikatörleri hesapla (optimizasyon ile)
            multi_tf_indicators = {}
            if self.indicators_manager:
                multi_tf_indicators = self.indicators_manager.calculate_multi_timeframe_indicators_optimized(
                    multi_tf_data, symbol, use_cache=True
                )
            
            # 5. Strateji sinyallerini hesapla ve filtrele
            primary_indicators = multi_tf_indicators.get(primary_timeframe, {})
            combined_signal = multi_tf_indicators.get('combined', {})
            
            # SuperTrend ve ADX kombinasyonu ile akıllı sinyal üretimi
            smart_signal = None
            smart_reason = ""
            
            if self.use_smart_trend and 'supertrend' in primary_indicators and 'adx' in primary_indicators:
                from strategies import smart_trend
                smart_signal, smart_reason = smart_trend.analyze(primary_data, primary_indicators)
                
                # Diğer sinyalleri filtrele
                if 'consolidated_signal' in primary_indicators:
                    orig_signal = primary_indicators['consolidated_signal'].get('signal')
                    orig_reason = primary_indicators['consolidated_signal'].get('reason', '')
                    
                    filtered_signal, filtered_reason = smart_trend.filter_signal(
                        orig_signal, orig_reason, primary_indicators
                    )
                    
                    # Filtrelenen sinyalleri güncelle
                    primary_indicators['consolidated_signal']['filtered_signal'] = filtered_signal
                    primary_indicators['consolidated_signal']['filtered_reason'] = filtered_reason
            
            # 6. Risk parametrelerini hesapla
            risk_params = {}
            if self.risk_manager and smart_signal in ['BUY', 'SELL']:
                # Pozisyon büyüklüğü, Stop-Loss ve Take-Profit hesapla
                balance = 1000  # Burada gerçek bakiyeyi kullanmalıyız
                current_price = primary_data['close'].iloc[-1]
                
                position_data = self.risk_manager.calculate_position_size(
                    balance, current_price, symbol, primary_data
                )
                
                stop_loss = self.risk_manager.calculate_stop_loss(
                    current_price, smart_signal, primary_data
                )
                
                take_profit = self.risk_manager.calculate_take_profit(
                    current_price, smart_signal, primary_data
                )
                
                risk_params = {
                    'position_size': position_data['position_size'],
                    'entry_price': current_price,
                    'stop_loss': stop_loss,
                    'take_profit': take_profit,
                    'risk_level': position_data['risk_level'],
                    'risk_factors': position_data['risk_factors']
                }
                
                # Volatiliteye göre risk parametrelerini ayarla
                if self.adaptive_parameters:
                    volatility_data = [
                        self.adaptive_parameters.market_state['metrics'].get('volatility_value', 0.02)
                    ]
                    self.risk_manager.adjust_parameters_for_market_conditions(volatility_data)
            
            # 7. Final sinyal paketi oluştur
            signal_package = {
                'symbol': symbol,
                'timestamp': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                'primary_timeframe': primary_timeframe,
                'smart_signal': smart_signal,
                'smart_reason': smart_reason,
                'combined_signal': combined_signal.get('trade_signal', 'NEUTRAL'),
                'market_conditions': self.adaptive_parameters.market_state if self.adaptive_parameters else {},
                'risk_params': risk_params
            }
            
            # Ayrıntılı sonuçları logla
            logger.info(f"{symbol} sinyal paketi oluşturuldu: {smart_signal} ({primary_timeframe})")
            
            return signal_package
            
        except Exception as e:
            logger.error(f"{symbol} için sinyal paketi oluşturulurken hata: {str(e)}")
            return {'signal': 'ERROR', 'reason': str(e), 'strength': 0}
    
    def _check_api_rate_limits(self):
        """
        API çağrı limitlerine uygunluğu kontrol eder ve gerekirse bekler
        """
        # Sayacı artır
        self.api_call_counter += 1
        
        # Zaman kontrolü (Her dakika sıfırlama)
        now = datetime.now()
        seconds_passed = (now - self.api_call_reset_time).total_seconds()
        
        if seconds_passed > 60:
            # Bir dakika geçmişse sayacı sıfırla
            self.api_call_counter = 1
            self.api_call_reset_time = now
        
        # Limit kontrolü
        if self.api_call_counter > self.api_call_limit_per_minute:
            # Limit aşıldı, bir sonraki dakikaya kadar bekle
            wait_seconds = 60 - seconds_passed + 1
            logger.warning(f"API çağrı limiti aşıldı ({self.api_call_counter}/{self.api_call_limit_per_minute}), {wait_seconds:.1f} saniye bekleniyor")
            time.sleep(wait_seconds)
            
            # Sayacı sıfırla
            self.api_call_counter = 1
            self.api_call_reset_time = datetime.now()
    
    def optimize_multi_timeframe_analysis(self, symbol, available_timeframes):
        """
        Çoklu zaman dilimi analizini optimize etmek için hangi zaman dilimlerinin
        kullanılması gerektiğini belirler
        
        :param symbol: Coin sembolü
        :param available_timeframes: Mevcut tüm zaman dilimleri
        :return: Kullanılacak zaman dilimleri listesi
        """
        try:
            # Performans ve sonuçlar arasındaki dengeyi optimize etmek için
            # hangi zaman dilimlerinin kullanılacağına karar ver
            
            # Ana zaman dilimleri
            primary_tf = self.config.get('primary_timeframe', '1h')
            
            # Farklı zaman dilimi türleri
            short_timeframes = ['1m', '3m', '5m', '15m']
            medium_timeframes = ['30m', '1h', '2h', '4h']
            long_timeframes = ['6h', '8h', '12h', '1d', '3d']
            
            # Aktif trade durumuna göre zaman dilimlerini seç
            is_active_trade = False  # Aktif trade kontrol edilmeli
            
            selected_timeframes = [primary_tf]  # Her zaman ana zaman dilimini ekle
            
            if is_active_trade:
                # Aktif işlem varsa daha fazla kısa vadeli zaman dilimi ekle
                short_tf_count = 2
                medium_tf_count = 2
                long_tf_count = 1
            else:
                # Normal tarama modunda daha dengeli dağıtım
                short_tf_count = 1
                medium_tf_count = 2
                long_tf_count = 1
            
            # Filtreleri mevcut zaman dilimlerine uygula
            available_short = [tf for tf in short_timeframes if tf in available_timeframes]
            available_medium = [tf for tf in medium_timeframes if tf in available_timeframes]
            available_long = [tf for tf in long_timeframes if tf in available_timeframes]
            
            # İstenen miktarda zaman dilimini seç
            selected_short = available_short[:short_tf_count]
            selected_medium = available_medium[:medium_tf_count]
            selected_long = available_long[:long_tf_count]
            
            # Tüm seçimleri birleştir
            all_selected = selected_short + selected_medium + selected_long
            
            # Tekrarı önle ve ana zaman dilimini ekle
            final_timeframes = list(set(all_selected))
            if primary_tf not in final_timeframes:
                final_timeframes.append(primary_tf)
            
            logger.info(f"{symbol} için optimize edilmiş zaman dilimleri: {final_timeframes}")
            return final_timeframes
            
        except Exception as e:
            logger.error(f"{symbol} için zaman dilimleri optimize edilirken hata: {str(e)}")
            # Hata durumunda birkaç önemli zaman dilimini döndür
            return ['15m', '1h', '4h']
