import pandas as pd
import numpy as np
import talib

def calculate(df, window=14, period=None):
    """
    Göreceli Güç İndeksi (RSI) hesapla - TA-Lib ile daha hassas ve hızlı hesaplama
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    window (int): RSI periyot uzunluğu (varsayılan=14)
    period (int): window ile aynı, geriye dönük uyumluluk için (varsayılan=None)
    
    Dönen değer:
    Series: RSI değerleri
    """
    # period parametresi geldiyse onu kullan (geriye dönük uyumluluk için)
    if period is not None:
        window = period
        
    if len(df) <= window:
        return pd.Series([50] * len(df), index=df.index)  # Yeterli veri yoksa nötr değer döndür
    
    try:
        # TA-Lib RSI hesaplama - çok daha hassas ve hızlı
        rsi_values = talib.RSI(df['close'].values, timeperiod=window)
        return pd.Series(rsi_values, index=df.index)
    except Exception as e:
        # TA-Lib başarısız olursa, geleneksel yönteme geri dön
        
        # Fiyat değişimlerini hesapla
        close_delta = df['close'].diff()
        
        # Pozitif ve negatif fiyat değişimleri
        up = close_delta.clip(lower=0)
        down = -1 * close_delta.clip(upper=0)
        
        # EMA tabanlı ortalama hesapla
        ma_up = up.ewm(com=window-1, adjust=True, min_periods=window).mean()
        ma_down = down.ewm(com=window-1, adjust=True, min_periods=window).mean()
        
        # Göreceli güç hesapla
        rs = ma_up / ma_down
        
        # RSI hesapla
        rsi = 100 - (100 / (1 + rs))
        
        return rsi