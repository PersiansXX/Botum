import pandas as pd
import numpy as np

def calculate(df, period=None):
    """
    VWAP (Volume Weighted Average Price) hesaplar
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (high, low, close, volume sütunları içermeli)
    period (int): VWAP için gün sayısı, None ise tüm veri kullanılır (varsayılan=None)
    
    Dönen değer:
    Series: VWAP değerleri
    """
    if df.empty:
        return pd.Series(np.nan, index=df.index)
    
    try:
        # Tipik fiyat: (high + low + close) / 3
        typical_price = (df['high'] + df['low'] + df['close']) / 3
        
        if period is None:
            # Tüm veri boyunca VWAP hesapla
            vwap = (typical_price * df['volume']).cumsum() / df['volume'].cumsum()
        else:
            # Belirtilen periyot boyunca VWAP hesapla
            tp_vol = typical_price * df['volume']
            vwap = tp_vol.rolling(window=period).sum() / df['volume'].rolling(window=period).sum()
        
        return vwap
        
    except Exception as e:
        print(f"VWAP hesaplanırken hata: {str(e)}")
        return pd.Series(np.nan, index=df.index)

def get_signal(close, vwap):
    """
    VWAP değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    close (float): Son kapanış fiyatı
    vwap (float): VWAP değeri
    
    Dönen değer:
    str: 'BUY', 'SELL' veya 'NEUTRAL'
    """
    # Değerleri kontrol et
    if close is None or vwap is None:
        return 'NEUTRAL'
    if np.isnan(close) or np.isnan(vwap):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    
    # Fiyatın VWAP'e göre durumuna bakarak sinyal belirle
    if close > vwap * 1.005:  # %0.5 fark (filtre)
        signal = 'BUY'  # Fiyat VWAP'in üzerindeyse alım sinyali
    elif close < vwap * 0.995:  # %0.5 fark (filtre)
        signal = 'SELL'  # Fiyat VWAP'in altındaysa satım sinyali
    
    return signal