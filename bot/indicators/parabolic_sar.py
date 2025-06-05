import pandas as pd
import numpy as np
import talib

def calculate(df, acceleration=0.02, maximum=0.2):
    """
    Parabolic SAR hesaplar - TA-Lib ile optimize edilmiştir
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (high, low sütunları içermeli)
    acceleration (float): Hızlanma faktörü (varsayılan=0.02)
    maximum (float): Maksimum hızlanma değeri (varsayılan=0.2)
    
    Dönen değer:
    Series: Parabolic SAR değerleri
    """
    if len(df) < 5:  # En az 5 veri noktası olmalı
        return pd.Series(np.nan, index=df.index)
    
    try:
        # TA-Lib Parabolic SAR hesaplama
        sar_values = talib.SAR(
            df['high'].values,
            df['low'].values,
            acceleration=acceleration,
            maximum=maximum
        )
        
        return pd.Series(sar_values, index=df.index)
        
    except Exception as e:
        print(f"TA-Lib ile Parabolic SAR hesaplanırken hata: {str(e)}")
        
        # Manuel hesaplama çok karmaşık, burada basit bir yaklaşım kullanılacak
        # Gerçek SAR hesaplaması için TA-Lib kullanılmalıdır
        return pd.Series(np.nan, index=df.index)

def get_signal(price, sar):
    """
    Parabolic SAR değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    price (float): Şu anki fiyat (genellikle kapanış fiyatı)
    sar (float): Parabolic SAR değeri
    
    Dönen değer:
    str: 'BUY', 'SELL' veya 'NEUTRAL'
    """
    # Değerleri kontrol et
    if price is None or sar is None:
        return 'NEUTRAL'
    if np.isnan(price) or np.isnan(sar):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    
    # SAR değeri fiyatın altındaysa, yükselen trend
    if price > sar:
        signal = 'BUY'
    # SAR değeri fiyatın üstündeyse, düşen trend
    elif price < sar:
        signal = 'SELL'
    
    return signal