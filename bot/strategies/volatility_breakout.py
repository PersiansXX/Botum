"""
Volatilite Kırılma Stratejisi

Bu strateji, fiyatın aniden bir hareket başlattığı ve bu hareketin devam etme potansiyeline 
sahip olduğu durumları tespit etmeye çalışır. Düşük volatiliteden yüksek volatiliteye geçiş anları,
büyük fiyat hareketlerinin başlangıcı olabilir.

Kullanılan İndikatörler:
- Bollinger Bant Genişliği (volatilite göstergesi)
- Fiyat hareketi (birkaç periyot içinde olan önemli hareketler)
- İşlem hacmi (artan hacim, kırılmanın güçlü olduğuna işarettir)
"""

import pandas as pd
import numpy as np
import logging

# Logger ayarları
logger = logging.getLogger("trading_bot")

def analyze(df, indicators):
    """
    Yeni TA-Lib tabanlı indikatörleri kullanan gelişmiş volatilite kırılma stratejisi
    
    :param df: OHLCV veri çerçevesi
    :param indicators: İndikatör sonuçlarını içeren sözlük
    :return: tuple (sinyal tipi, sinyal nedeni)
    """
    try:
        signal = 'NEUTRAL'
        reason = ''
        
        # Fiyat ve indikatörleri kontrol et
        if df.empty or not indicators:
            return signal, "Veri yetersiz"
            
        # Son kapanış fiyatı
        close = df['close'].iloc[-1]
        
        # Bollinger Bands kontrolü - Sıkışan volatilite sonrası kırılma
        if 'bollinger' in indicators:
            upper = indicators['bollinger']['upper']
            lower = indicators['bollinger']['lower']
            middle = indicators['bollinger']['middle']
            
            # Bollinger bantlarının genişliği (volatilite göstergesi)
            band_width = (upper - lower) / middle
            
            # Önceki 10 mumun bollinger genişliğini kontrol et (eğer mümkünse)
            if len(df) > 10:
                previous_width = []
                for i in range(2, 12):  # Son 10 mum (en son mum hariç)
                    if i < len(df):
                        prev_upper = df['bollinger_upper'].iloc[-i] if 'bollinger_upper' in df else None
                        prev_lower = df['bollinger_lower'].iloc[-i] if 'bollinger_lower' in df else None
                        prev_middle = df['bollinger_middle'].iloc[-i] if 'bollinger_middle' in df else None
                        
                        if prev_upper is not None and prev_lower is not None and prev_middle is not None:
                            prev_width = (prev_upper - prev_lower) / prev_middle
                            previous_width.append(prev_width)
                
                # Eğer önceki genişlikler hesaplandıysa
                if previous_width:
                    avg_prev_width = sum(previous_width) / len(previous_width)
                    
                    # Bollinger sıkışması sonrası kırılma kontrolü
                    if band_width > avg_prev_width * 1.5:  # Genişleme olduysa
                        # Fiyat yönünü kontrol et
                        if close > upper:
                            signal = 'BUY'
                            reason = "Bollinger sıkışması sonrası yukarı kırılma"
                        elif close < lower:
                            signal = 'SELL'
                            reason = "Bollinger sıkışması sonrası aşağı kırılma"
        
        # ADX kontrolü - Trend gücü
        if 'adx' in indicators and signal == 'NEUTRAL':
            adx_value = indicators['adx']['adx']
            plus_di = indicators['adx']['plus_di']
            minus_di = indicators['adx']['minus_di']
            
            # Güçlü trend varsa (ADX > 25)
            if adx_value > 25:
                if plus_di > minus_di and plus_di > plus_di * 1.1:  # %10 fark
                    signal = 'BUY'
                    reason = f"Güçlü yükselen trend (ADX: {adx_value:.2f}, +DI: {plus_di:.2f})"
                elif minus_di > plus_di and minus_di > plus_di * 1.1:  # %10 fark
                    signal = 'SELL'
                    reason = f"Güçlü düşen trend (ADX: {adx_value:.2f}, -DI: {minus_di:.2f})"
        
        # Stochastic Oscillator kontrolü - Aşırı alım/satım durumları
        if 'stochastic' in indicators and signal == 'NEUTRAL':
            k_line = indicators['stochastic']['k_line']
            d_line = indicators['stochastic']['d_line']
            
            # Aşırı satım bölgesinden çıkış sinyali
            if k_line < 20 and d_line < 20 and k_line > d_line:
                signal = 'BUY'
                reason = f"Stochastic aşırı satım bölgesinden dönüş (%K: {k_line:.2f}, %D: {d_line:.2f})"
            
            # Aşırı alım bölgesinden çıkış sinyali
            elif k_line > 80 and d_line > 80 and k_line < d_line:
                signal = 'SELL'
                reason = f"Stochastic aşırı alım bölgesinden dönüş (%K: {k_line:.2f}, %D: {d_line:.2f})"
        
        # Parabolic SAR kontrolü - Trend değişimi
        if 'parabolic_sar' in indicators and signal == 'NEUTRAL':
            sar_value = indicators['parabolic_sar']['value']
            
            # SAR değeri fiyatın altındaysa ALIŞ, üstündeyse SATIŞ sinyali
            if close > sar_value * 1.005:  # %0.5 fark
                signal = 'BUY'
                reason = f"Parabolic SAR trend değişimi yukarı (fiyat: {close:.4f}, SAR: {sar_value:.4f})"
            elif close < sar_value * 0.995:  # %0.5 fark
                signal = 'SELL'
                reason = f"Parabolic SAR trend değişimi aşağı (fiyat: {close:.4f}, SAR: {sar_value:.4f})"
        
        # Ichimoku Cloud kontrolü - Bulut pozisyonu
        if 'ichimoku' in indicators and signal == 'NEUTRAL':
            tenkan_sen = indicators['ichimoku'].get('tenkan_sen')
            kijun_sen = indicators['ichimoku'].get('kijun_sen')
            senkou_span_a = indicators['ichimoku'].get('senkou_span_a')
            senkou_span_b = indicators['ichimoku'].get('senkou_span_b')
            
            if all(x is not None for x in [tenkan_sen, kijun_sen, senkou_span_a, senkou_span_b]):
                # Bulut rengi (yeşil veya kırmızı)
                cloud_is_green = senkou_span_a > senkou_span_b
                
                # Fiyat bulutun üstündeyse ve bulut yeşilse güçlü alım sinyali
                if close > max(senkou_span_a, senkou_span_b) and cloud_is_green and tenkan_sen > kijun_sen:
                    signal = 'BUY'
                    reason = "Ichimoku: Fiyat yeşil bulutun üstünde, TK çaprazı yukarı"
                
                # Fiyat bulutun altındaysa ve bulut kırmızıysa güçlü satım sinyali
                elif close < min(senkou_span_a, senkou_span_b) and not cloud_is_green and tenkan_sen < kijun_sen:
                    signal = 'SELL'
                    reason = "Ichimoku: Fiyat kırmızı bulutun altında, TK çaprazı aşağı"

        # RSI ile birleşik kontrol - Trend doğrulama
        if 'rsi' in indicators and signal != 'NEUTRAL':
            rsi_value = indicators['rsi']['value']
            
            # Eğer BUY sinyali varsa ve RSI 70'in üstündeyse satım baskısı var demektir
            if signal == 'BUY' and rsi_value > 70:
                signal = 'NEUTRAL'
                reason = "RSI aşırı alım bölgesinde, BUY sinyali iptal edildi"
            
            # Eğer SELL sinyali varsa ve RSI 30'un altındaysa alım baskısı var demektir
            elif signal == 'SELL' and rsi_value < 30:
                signal = 'NEUTRAL'
                reason = "RSI aşırı satım bölgesinde, SELL sinyali iptal edildi"
        
        # Yukarıdaki faktörlerin hiçbiri güçlü bir sinyal vermediyse, nötr kalırız
        return signal, reason
        
    except Exception as e:
        logger.error(f"Volatilite kırılma stratejisinde hata: {str(e)}")
        return 'NEUTRAL', f"Hata: {str(e)}"