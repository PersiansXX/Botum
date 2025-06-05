import pandas as pd
import numpy as np
import logging

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

class RiskManager:
    """
    Risk yönetimi parametrelerini hesaplayan ve yöneten sınıf.
    """
    
    def __init__(self, config=None):
        """
        RiskManager sınıfını başlatır.
        
        :param config: Bot yapılandırma ayarları
        """
        self.config = config or {}
        self.risk_settings = self.config.get('risk_settings', {})
        
        # Geliştirilmiş risk ayarları - DAHA GÜVENLİ RİSK YÖNETİMİ
        self.max_risk_per_trade = self.risk_settings.get('max_risk_per_trade', 0.03)  # %3 maksimum risk
        self.max_open_trades = self.risk_settings.get('max_open_trades', 5)  # 5 açık işlem limiti
        self.max_daily_trades = self.risk_settings.get('max_daily_trades', 30)  # Günlük 30 işlem limiti
        self.max_risk_per_coin = self.risk_settings.get('max_risk_per_coin', 0.1)  # Coin başına %10 maksimum risk
        
        # Stop-Loss ve Take-Profit ayarları - DAHA SIKI ZARAR YÖNETİMİ
        self.fixed_stop_loss = self.risk_settings.get('fixed_stop_loss', 0.008)  # %0.8 sabit stop-loss
        self.fixed_take_profit = self.risk_settings.get('fixed_take_profit', 0.024)  # %2.4 sabit take-profit (risk-reward=3)
        self.use_atr_for_stop_loss = self.risk_settings.get('use_atr_for_stop_loss', True)  # ATR bazlı stop-loss aktif
        self.atr_multiplier_for_stop = self.risk_settings.get('atr_multiplier_for_stop', 1.0)  # ATR çarpanı 1.0'a düşürüldü
        
        # Trailing stop ayarları - DAHA ETKİN KAR KORUMA
        self.use_trailing_stop = self.risk_settings.get('use_trailing_stop', True)
        self.trailing_stop_activation = self.risk_settings.get('trailing_stop_activation', 0.005)  # %0.5'te aktivasyon
        self.trailing_stop_distance = self.risk_settings.get('trailing_stop_distance', 0.003)  # %0.3 trailing mesafesi
        
        # Volatilite bazlı ayarlar
        self.volatility_factor = self.risk_settings.get('volatility_factor', 0.5)  # 0-1 arası, volatilite etkisi
        self.max_volatility_threshold = self.risk_settings.get('max_volatility_threshold', 0.05)  # %5 üzeri volatilite tehlikeli
        
        # ZARAR KORUMA SİSTEMİ - YENİ
        self.max_loss_threshold = self.risk_settings.get('max_loss_threshold', 0.08)  # %8 maksimum zarar eşiği
        self.emergency_exit_threshold = self.risk_settings.get('emergency_exit_threshold', 0.12)  # %12 acil çıkış eşiği
        
        # 🚀 OTOMATİK KALDIRAÇ SİSTEMİ
        self.auto_leverage_settings = self.config.get('auto_leverage_settings', {
            'enabled': True,
            'min_leverage': 1,  # Minimum kaldıraç 1x'e düşürüldü (kaldıraçsız)
            'max_leverage': 3,  # Maksimum kaldıraç 3x'e düşürüldü
            'risk_levels': {
                'LOW_RISK': {'leverage': 3, 'volatility_threshold': 0.02, 'rsi_range': [30, 70]},
                'MEDIUM_RISK': {'leverage': 2, 'volatility_threshold': 0.05, 'rsi_range': [25, 75]},
                'HIGH_RISK': {'leverage': 1, 'volatility_threshold': 0.08, 'rsi_range': [20, 80]}
            },
            'market_conditions': {
                'trending': {'leverage_multiplier': 1.2},
                'sideways': {'leverage_multiplier': 0.8},
                'volatile': {'leverage_multiplier': 0.6}
            },
            'safety_checks': {
                'max_daily_trades': 10,
                'cool_down_period': 300,  # 5 dakika
                'emergency_stop_loss': 0.05
            }
        })
        
        logger.info(f"Risk Yöneticisi başlatıldı. Max risk/işlem: %{self.max_risk_per_trade*100}")
        logger.info(f"⚠️ Gelişmiş stop-loss sistemi aktif: %{self.fixed_stop_loss*100:.1f} sabit / ATR x{self.atr_multiplier_for_stop}")
        logger.info(f"🛑 Maksimum zarar koruma sistemi: %{self.max_loss_threshold*100:.1f}")
        if self.auto_leverage_settings.get('enabled'):
            logger.info("🚀 Otomatik Kaldıraç Sistemi aktif!")
    
    def calculate_position_size(self, balance, price, symbol=None, ohlcv_data=None):
        """
        İşlem büyüklüğünü hesapla
        
        :param balance: Toplam bakiye
        :param price: Giriş fiyatı
        :param symbol: Coin sembolü (isteğe bağlı)
        :param ohlcv_data: OHLCV verisi (volatilite hesabı için)
        :return: İşlem büyüklüğü ve risk değerlendirmesi
        """
        try:
            # Varsayılan pozisyon büyüklüğü
            risk_amount = balance * self.max_risk_per_trade
            position_size = risk_amount / price
            
            # Risk düzeyini başlat
            risk_level = "NORMAL"
            risk_factors = []
            
            # Volatilite bazlı ayarlama
            if ohlcv_data is not None and len(ohlcv_data) > 14:
                # ATR hesapla (14 gün)
                try:
                    import talib
                    atr = talib.ATR(ohlcv_data['high'].values, ohlcv_data['low'].values, 
                                    ohlcv_data['close'].values, timeperiod=14)[-1]
                    
                    # Fiyata göre normalize edilmiş volatilite
                    volatility = atr / price
                    
                    # Volatilite seviyesine göre risk düzeyini belirle
                    if volatility > self.max_volatility_threshold:
                        # Yüksek volatilite - riski azalt
                        position_size = position_size * (1 - self.volatility_factor)
                        risk_level = "HIGH"
                        risk_factors.append(f"Yüksek volatilite (%{volatility*100:.1f})")
                    elif volatility < self.max_volatility_threshold * 0.3:
                        # Düşük volatilite - riski artırabilirsin (opsiyonel)
                        position_size = position_size * (1 + self.volatility_factor * 0.5)
                        risk_factors.append(f"Düşük volatilite (%{volatility*100:.1f})")
                        
                    logger.debug(f"{symbol} volatilite: %{volatility*100:.2f}, risk seviyesi: {risk_level}")
                    
                except Exception as e:
                    logger.warning(f"ATR hesaplanırken hata: {str(e)}")
            
            # Maksimum işlem büyüklüğünü kontrol et
            max_position = balance * self.max_risk_per_coin / price
            if position_size > max_position:
                position_size = max_position
                risk_factors.append(f"Maksimum pozisyon limiti ({self.max_risk_per_coin*100:.1f}%)")
            
            return {
                'position_size': position_size,
                'risk_level': risk_level,
                'risk_factors': risk_factors,
                'risk_amount': risk_amount
            }
            
        except Exception as e:
            logger.error(f"Pozisyon büyüklüğü hesaplanırken hata: {str(e)}")
            # Güvenli varsayılan değer
            return {
                'position_size': balance * 0.01 / price,  # %1 risk
                'risk_level': "ERROR",
                'risk_factors': [f"Hesaplama hatası: {str(e)}"],
                'risk_amount': balance * 0.01
            }
    
    def calculate_stop_loss(self, entry_price, side, ohlcv_data=None):
        """
        Stop-Loss seviyesini hesapla
        
        :param entry_price: Giriş fiyatı
        :param side: İşlem yönü ('BUY' veya 'SELL')
        :param ohlcv_data: OHLCV verisi (ATR için)
        :return: Stop-Loss seviyesi
        """
        try:
            # ATR bazlı dinamik Stop-Loss (daha güvenilir)
            if self.use_atr_for_stop_loss and ohlcv_data is not None and len(ohlcv_data) > 14:
                try:
                    import talib
                    atr = talib.ATR(ohlcv_data['high'].values, ohlcv_data['low'].values, 
                                  ohlcv_data['close'].values, timeperiod=14)[-1]
                    
                    # ATR bazlı stop loss hesapla
                    stop_distance = atr * self.atr_multiplier_for_stop
                    
                    if side == 'BUY':
                        stop_loss = entry_price - stop_distance
                    else:  # SELL
                        stop_loss = entry_price + stop_distance
                        
                    # Stop-Loss yüzdesi loglama için
                    stop_percentage = abs(stop_loss - entry_price) / entry_price
                    
                    # Maximum stop loss sınırını kontrol et
                    max_stop_percentage = 0.08  # %8 maksimum stop loss
                    if stop_percentage > max_stop_percentage:
                        # Stop loss çok geniş, sınırla
                        if side == 'BUY':
                            stop_loss = entry_price * (1 - max_stop_percentage)
                        else:  # SELL
                            stop_loss = entry_price * (1 + max_stop_percentage)
                        logger.warning(f"ATR stop-loss çok geniş (%{stop_percentage*100:.2f}), %{max_stop_percentage*100} ile sınırlandı")
                    else:
                        logger.debug(f"ATR bazlı Stop-Loss: {stop_loss:.6f} (%{stop_percentage*100:.2f})")
                    
                    return stop_loss
                    
                except Exception as e:
                    logger.warning(f"ATR bazlı Stop-Loss hesaplanırken hata: {str(e)}, sabit değer kullanılıyor")
            
            # Sabit yüzdelik Stop-Loss (varsayılan) - DAHA SIKI STOP
            if side == 'BUY':
                stop_loss = entry_price * (1 - self.fixed_stop_loss)
            else:  # SELL
                stop_loss = entry_price * (1 + self.fixed_stop_loss)
                
            logger.debug(f"Sabit Stop-Loss: {stop_loss:.6f} (%{self.fixed_stop_loss*100:.2f})")
            return stop_loss
            
        except Exception as e:
            logger.error(f"Stop-Loss hesaplanırken hata: {str(e)}")
            # Güvenli varsayılan değer
            if side == 'BUY':
                return entry_price * 0.975  # %2.5 stop loss
            else:
                return entry_price * 1.025  # %2.5 stop loss
    
    def calculate_take_profit(self, entry_price, side, ohlcv_data=None, risk_reward_ratio=3.0):
        """
        Take-Profit seviyesini hesapla - DAHA İYİ RISK/REWARD ORANI (3.0)
        
        :param entry_price: Giriş fiyatı
        :param side: İşlem yönü ('BUY' veya 'SELL')
        :param ohlcv_data: OHLCV verisi (isteğe bağlı)
        :param risk_reward_ratio: Risk/Ödül oranı (varsayılan=3.0)
        :return: Take-Profit seviyesi
        """
        try:
            # Stop-Loss hesapla (bunu referans olarak kullanalım)
            stop_loss = self.calculate_stop_loss(entry_price, side, ohlcv_data)
            stop_distance = abs(entry_price - stop_loss)
            
            # Risk-Ödül oranına göre Take-Profit hesapla
            take_profit_distance = stop_distance * risk_reward_ratio
            
            if side == 'BUY':
                take_profit = entry_price + take_profit_distance
            else:  # SELL
                take_profit = entry_price - take_profit_distance
            
            # Take-Profit yüzdesi
            tp_percentage = abs(take_profit - entry_price) / entry_price
            logger.debug(f"Take-Profit: {take_profit:.6f} (%{tp_percentage*100:.2f}, R/R: {risk_reward_ratio})")
            
            return take_profit
            
        except Exception as e:
            logger.error(f"Take-Profit hesaplanırken hata: {str(e)}")
            # Güvenli varsayılan değer
            if side == 'BUY':
                return entry_price * (1 + self.fixed_take_profit)
            else:
                return entry_price * (1 - self.fixed_take_profit)
    
    def update_trailing_stop(self, entry_price, current_price, current_stop, side):
        """
        Trailing Stop seviyesini güncelle - DAHA ERKEN VE DAHA SIKI TRAILING STOP
        
        :param entry_price: Giriş fiyatı
        :param current_price: Mevcut fiyat
        :param current_stop: Mevcut stop seviyesi
        :param side: İşlem yönü ('BUY' veya 'SELL')
        :return: Güncellenmiş stop seviyesi
        """
        if not self.use_trailing_stop:
            return current_stop
        
        try:
            # Kâr yüzdesini hesapla
            if side == 'BUY':
                profit_percentage = (current_price - entry_price) / entry_price
                
                # Trailing Stop aktivasyon eşiğini geçtik mi?
                if profit_percentage >= self.trailing_stop_activation:
                    # Yeni trail stop hesapla
                    new_stop = current_price * (1 - self.trailing_stop_distance)
                    
                    # Mevcut stoptan daha yukarıda mı?
                    if new_stop > current_stop:
                        logger.debug(f"Trailing Stop güncellendi: {current_stop:.6f} -> {new_stop:.6f}")
                        return new_stop
            
            else:  # SELL
                profit_percentage = (entry_price - current_price) / entry_price
                
                # Trailing Stop aktivasyon eşiğini geçtik mi?
                if profit_percentage >= self.trailing_stop_activation:
                    # Yeni trail stop hesapla
                    new_stop = current_price * (1 + self.trailing_stop_distance)
                    
                    # Mevcut stoptan daha aşağıda mı?
                    if new_stop < current_stop:
                        logger.debug(f"Trailing Stop güncellendi: {current_stop:.6f} -> {new_stop:.6f}")
                        return new_stop
            
            return current_stop
            
        except Exception as e:
            logger.error(f"Trailing Stop güncellenirken hata: {str(e)}")
            return current_stop
    
    def should_close_on_max_loss(self, entry_price, current_price, position_type):
        """
        Maksimum zarar eşiğine ulaşılıp ulaşılmadığını kontrol eder
        
        :param entry_price: Giriş fiyatı
        :param current_price: Mevcut fiyat
        :param position_type: Pozisyon türü ('LONG' veya 'SHORT')
        :return: (Kapatılmalı mı, Sebep)
        """
        try:
            # Zarar yüzdesini hesapla
            if position_type == 'LONG':
                loss_pct = ((entry_price / current_price) - 1) * 100
            else:  # SHORT
                loss_pct = ((current_price / entry_price) - 1) * 100
            
            # Eğer zarar, maksimum zarar eşiğini aştıysa
            if loss_pct > self.max_loss_threshold * 100:
                return (True, f"Maksimum zarar eşiği aşıldı (%{loss_pct:.2f} > %{self.max_loss_threshold*100})")
            
            # Acil çıkış eşiği kontrolü
            if loss_pct > self.emergency_exit_threshold * 100:
                return (True, f"Acil durum zarar eşiği aşıldı (%{loss_pct:.2f} > %{self.emergency_exit_threshold*100})")
                
            return (False, None)
            
        except Exception as e:
            logger.error(f"Maksimum zarar kontrolü sırasında hata: {str(e)}")
            # Hata durumunda güvenli tarafta kalmak için True döndür
            return (True, f"Hata nedeniyle güvenlik çıkışı: {str(e)}")
    
    def adjust_parameters_for_market_conditions(self, volatility_data):
        """
        Piyasa koşullarına göre risk parametrelerini dinamik olarak ayarla
        
        :param volatility_data: Volatilite verileri (örn. ATR/Fiyat oranları)
        """
        try:
            if not volatility_data or len(volatility_data) < 5:
                return  # Yeterli veri yok
            
            # Son 5 periyotluk ortalama volatilite
            avg_volatility = sum(volatility_data[-5:]) / 5
            
            # Volatilite durumuna göre parametreleri ayarla
            if avg_volatility > self.max_volatility_threshold:
                # Yüksek volatilite: Riski azalt, stop'ları daha yakın tut
                new_risk = max(self.max_risk_per_trade * 0.6, 0.005)  # En az %0.5
                new_atr_multiplier = min(self.atr_multiplier_for_stop * 0.8, 1.5)  # En fazla 1.5x (daha yakın stop)
                
                logger.info(f"Yüksek volatilite tespit edildi (%{avg_volatility*100:.1f}), risk azaltıldı: %{new_risk*100:.1f}, stop daha yakın")
                
                # Parametreleri güncelle
                self.max_risk_per_trade = new_risk
                self.atr_multiplier_for_stop = new_atr_multiplier
                
            elif avg_volatility < self.max_volatility_threshold * 0.4:
                # Düşük volatilite: Riski artır, stop'ları daha yakın tut
                new_risk = min(self.max_risk_per_trade * 1.2, self.max_risk_per_coin)  # max_risk_per_coin'i geçme
                new_atr_multiplier = max(self.atr_multiplier_for_stop * 0.9, 1.0)  # En az 1.0
                
                logger.info(f"Düşük volatilite tespit edildi (%{avg_volatility*100:.1f}), risk artırıldı: %{new_risk*100:.1f}")
                
                # Parametreleri güncelle
                self.max_risk_per_trade = new_risk
                self.atr_multiplier_for_stop = new_atr_multiplier
            
        except Exception as e:
            logger.error(f"Piyasa koşullarına göre parametre ayarlanırken hata: {str(e)}")
    
    def calculate_dynamic_leverage(self, symbol, ohlcv_data, indicators=None, market_conditions=None):
        """
        🎯 Dinamik kaldıraç hesaplama - AKILLI SİSTEM
        
        :param symbol: Coin sembolü
        :param ohlcv_data: OHLCV verisi
        :param indicators: Teknik indikatörler (RSI, MACD, vs.)
        :param market_conditions: Piyasa durumu
        :return: Hesaplanan kaldıraç ve risk seviyesi
        """
        try:
            if not self.auto_leverage_settings.get('enabled', False):
                return {'leverage': 1, 'risk_level': 'MANUAL', 'reason': 'Otomatik kaldıraç devre dışı'}
            
            # Başlangıç değerleri - DAHA DÜŞÜK KALDIRAÇ İLE BAŞLA
            base_leverage = 1
            risk_level = "MEDIUM_RISK"
            risk_factors = []
            
            # 📊 1. VOLATİLİTE ANALİZİ
            volatility_score = self._calculate_volatility_score(ohlcv_data)
            
            # 📈 2. TEKNİK İNDİKATÖR ANALİZİ
            indicator_score = self._analyze_technical_indicators(indicators) if indicators else 0.5
            
            # 🌊 3. PİYASA DURUMU ANALİZİ
            market_score = self._analyze_market_conditions(market_conditions) if market_conditions else 0.5
            
            # 🧮 4. GENEL RİSK SKORU HESAPLAMA (0-1 arası)
            overall_risk_score = (volatility_score * 0.4 + indicator_score * 0.4 + market_score * 0.2)
            
            # 🎯 5. RİSK SEVİYESİ BELİRLEME
            if overall_risk_score <= 0.3:
                risk_level = "LOW_RISK"
                risk_factors.append("Düşük risk ortamı")
            elif overall_risk_score <= 0.7:
                risk_level = "MEDIUM_RISK"
                risk_factors.append("Orta risk ortamı")
            else:
                risk_level = "HIGH_RISK"
                risk_factors.append("Yüksek risk ortamı")
            
            # 🚀 6. KALDIRAÇ HESAPLAMA
            risk_config = self.auto_leverage_settings['risk_levels'][risk_level]
            calculated_leverage = risk_config['leverage']
            
            # Market koşullarına göre çarpan uygula
            if market_conditions:
                market_type = market_conditions.get('trend_type', 'sideways')
                if market_type in self.auto_leverage_settings['market_conditions']:
                    multiplier = self.auto_leverage_settings['market_conditions'][market_type]['leverage_multiplier']
                    calculated_leverage = int(calculated_leverage * multiplier)
                    risk_factors.append(f"Piyasa durumu: {market_type} (x{multiplier})")
            
            # Min/Max sınırları uygula
            min_lev = self.auto_leverage_settings['min_leverage']
            max_lev = self.auto_leverage_settings['max_leverage']
            calculated_leverage = max(min_lev, min(max_lev, calculated_leverage))
            
            # 📊 7. GÜVENLİK KONTROL
            safety_warnings = self._perform_safety_checks(symbol, calculated_leverage)
            if safety_warnings:
                calculated_leverage = min(calculated_leverage, 1)  # Güvenlik için düşür (1x = kaldıraçsız)
                risk_factors.extend(safety_warnings)
            
            logger.info(f"🎯 {symbol} için hesaplanan kaldıraç: {calculated_leverage}x")
            logger.info(f"📊 Risk seviyesi: {risk_level} (skor: {overall_risk_score:.2f})")
            logger.debug(f"📋 Risk faktörleri: {', '.join(risk_factors)}")
            
            return {
                'leverage': calculated_leverage,
                'risk_level': risk_level,
                'risk_score': overall_risk_score,
                'risk_factors': risk_factors,
                'volatility_score': volatility_score,
                'indicator_score': indicator_score,
                'market_score': market_score,
                'recommended_stop_loss': self._calculate_dynamic_stop_loss(calculated_leverage, volatility_score),
                'safety_warnings': safety_warnings
            }
            
        except Exception as e:
            logger.error(f"Kaldıraç hesaplama hatası ({symbol}): {str(e)}")
            return {
                'leverage': 1,  # Güvenli varsayılan (kaldıraçsız)
                'risk_level': 'ERROR',
                'risk_score': 1.0,
                'risk_factors': [f"Hesaplama hatası: {str(e)}"],
                'safety_warnings': ['Hata nedeniyle güvenli mod']
            }
    
    def _calculate_volatility_score(self, ohlcv_data):
        """Volatilite skoru hesaplama (0-1 arası, 1 = yüksek risk)"""
        try:
            if len(ohlcv_data) < 14:
                return 0.5  # Yetersiz veri
            
            # ATR ile volatilite hesaplama
            import talib
            atr = talib.ATR(ohlcv_data['high'].values, ohlcv_data['low'].values, 
                          ohlcv_data['close'].values, timeperiod=14)[-1]
            
            current_price = ohlcv_data['close'].iloc[-1]
            volatility_pct = atr / current_price
            
            # Volatilite skoruna dönüştür (0-1)
            if volatility_pct <= 0.02:  # %2 altı düşük volatilite
                return 0.2
            elif volatility_pct <= 0.05:  # %2-5 arası normal
                return 0.5
            elif volatility_pct <= 0.08:  # %5-8 arası yüksek
                return 0.8
            else:  # %8 üzeri çok yüksek
                return 1.0
                
        except Exception as e:
            logger.warning(f"Volatilite hesaplama hatası: {str(e)}")
            return 0.5
    
    def _analyze_technical_indicators(self, indicators):
        """Teknik indikatör analizi (0-1 arası, 1 = yüksek risk)"""
        try:
            risk_score = 0.5  # Varsayılan
            
            if not indicators:
                return risk_score
            
            # RSI analizi
            rsi = indicators.get('rsi', {}).get('value')
            if rsi:
                if 30 <= rsi <= 70:  # Normal aralık
                    risk_score -= 0.1
                elif rsi < 20 or rsi > 80:  # Aşırı bölgeler
                    risk_score += 0.2
            
            # MACD analizi
            macd = indicators.get('macd', {})
            if macd:
                macd_value = macd.get('value')
                macd_signal = macd.get('signal_line')
                
                if macd_value is not None and macd_signal is not None:
                    # MACD histogramı hesapla
                    histogram = macd_value - macd_signal
                    signal_strength = abs(histogram)
                    
                    if signal_strength > 0.002:  # Güçlü sinyal
                        risk_score -= 0.1
                    elif signal_strength < 0.0005:  # Zayıf sinyal
                        risk_score += 0.1
            
            # Bollinger Bands analizi
            bb = indicators.get('bollinger', {})
            if bb:
                upper = bb.get('upper')
                lower = bb.get('lower')
                middle = bb.get('middle')
                close = indicators.get('close', 0)
                
                if upper and lower and middle and close:
                    # Fiyatın BB içindeki pozisyonu (0-1)
                    bb_range = upper - lower
                    if bb_range > 0:
                        bb_position = (close - lower) / bb_range
                        
                        if 0.2 <= bb_position <= 0.8:  # Orta bölge
                            risk_score -= 0.1
                        elif bb_position > 0.95 or bb_position < 0.05:  # Ekstrem bölgeler
                            risk_score += 0.2
            
            return max(0.0, min(1.0, risk_score))
            
        except Exception as e:
            logger.warning(f"İndikatör analizi hatası: {str(e)}")
            return 0.5
    
    def _analyze_market_conditions(self, market_conditions):
        """Piyasa koşulları analizi (0-1 arası, 1 = yüksek risk)"""
        try:
            if not market_conditions:
                return 0.5
            
            trend_strength = market_conditions.get('trend_strength', 0.5)
            trend_type = market_conditions.get('trend_type', 'sideways')
            
            # Trend gücüne göre risk
            if trend_type == 'trending' and trend_strength > 0.7:
                return 0.3  # Güçlü trend = düşük risk
            elif trend_type == 'volatile':
                return 0.8  # Volatil piyasa = yüksek risk
            else:
                return 0.5  # Orta risk
                
        except Exception as e:
            logger.warning(f"Piyasa analizi hatası: {str(e)}")
            return 0.5
    
    def _perform_safety_checks(self, symbol, leverage):
        """Güvenlik kontrolleri"""
        warnings = []
        
        try:
            # Günlük işlem limiti kontrolü (veritabanından)
            # Bu kısım veritabanı bağlantısı gerektirir
            
            # Acil durum stop loss kontrolü
            emergency_sl = self.auto_leverage_settings['safety_checks']['emergency_stop_loss']
            if leverage > 3 and emergency_sl < 0.03:
                warnings.append(f"Yüksek kaldıraç için stop loss çok düşük ({emergency_sl*100:.1f}%)")
            
            # Cool-down period kontrolü
            # Bu kısım son işlem zamanı kontrolü gerektirir
            
        except Exception as e:
            logger.warning(f"Güvenlik kontrolü hatası: {str(e)}")
            warnings.append("Güvenlik kontrolü yapılamadı")
        
        return warnings
    
    def _calculate_dynamic_stop_loss(self, leverage, volatility_score):
        """Kaldıraç ve volatiliteye göre dinamik stop loss"""
        base_stop_loss = 0.01  # %1 temel stop-loss (düşürüldü)
        
        # Kaldıraç faktörü
        leverage_factor = 1.0 + (leverage - 1) * 0.1
        
        # Volatilite faktörü
        volatility_factor = 1.0 + volatility_score * 0.3
        
        dynamic_stop_loss = base_stop_loss * leverage_factor * volatility_factor
        
        # Min/Max sınırları
        return max(0.008, min(0.04, dynamic_stop_loss))  # %0.8-%4 arası
    
    def should_use_auto_leverage(self):
        """
        Otomatik kaldıraç sisteminin aktif olup olmadığını kontrol eder
        
        :return: Auto leverage aktif mi
        """
        leverage_mode = self.config.get('leverage_mode', 'manual')
        return leverage_mode.lower() == 'auto'
