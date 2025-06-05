import pandas as pd
import numpy as np
import talib

def calculate(df, period=10, multiplier=3):
    """
    SuperTrend indikatörünü hesaplar - TA-Lib kullanarak ATR değerini alır
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (high, low, close sütunları içermeli)
    period (int): ATR periyodu (varsayılan=10)
    multiplier (int): ATR çarpanı (varsayılan=3)
    
    Dönen değer:
    dict: supertrend, trend_direction ve atr değerlerini içeren dict
    """
    if len(df) < period:
        return {
            'supertrend': pd.Series(np.nan, index=df.index),
            'trend_direction': pd.Series(np.nan, index=df.index),
            'atr': pd.Series(np.nan, index=df.index)
        }
    
    try:
        # TA-Lib ile ATR (Ortalama Gerçek Aralık) hesaplama
        atr = talib.ATR(df['high'].values, df['low'].values, df['close'].values, timeperiod=period)
        atr = pd.Series(atr, index=df.index)
        
        # HL2 (Yüksek + Düşük / 2)
        hl2 = (df['high'] + df['low']) / 2
        
        # Üst ve alt bantları hesapla
        upper_band = hl2 + (multiplier * atr)
        lower_band = hl2 - (multiplier * atr)
        
        # SuperTrend hesaplama
        supertrend = pd.Series(0.0, index=df.index)
        trend_direction = pd.Series(1, index=df.index)  # 1: yukarı trend, -1: aşağı trend
        
        # İlk değer
        supertrend.iloc[period] = lower_band.iloc[period]
        trend_direction.iloc[period] = 1
        
        # SuperTrend değerlerini hesapla
        for i in range(period + 1, len(df)):
            # Önceki SuperTrend değeri
            prev_supertrend = supertrend.iloc[i-1]
            
            # Mevcut değerleri al
            current_close = df['close'].iloc[i]
            current_upper = upper_band.iloc[i]
            current_lower = lower_band.iloc[i]
            
            # Trend yönü (1: yukarı, -1: aşağı)
            if prev_supertrend <= df['close'].iloc[i-1]:
                # Önceki trend yukarıydı
                current_trend = 1
            else:
                # Önceki trend aşağıydı
                current_trend = -1
            
            # SuperTrend değerini güncelle
            if current_trend == 1:
                # Yukarı trend - alt bantı kullan
                if current_close <= current_lower:
                    # Trend aşağı döndü
                    current_supertrend = current_upper
                    current_trend = -1
                else:
                    # Trend devam ediyor
                    current_supertrend = max(current_lower, prev_supertrend)
            else:
                # Aşağı trend - üst bantı kullan
                if current_close >= current_upper:
                    # Trend yukarı döndü
                    current_supertrend = current_lower
                    current_trend = 1
                else:
                    # Trend devam ediyor
                    current_supertrend = min(current_upper, prev_supertrend)
            
            # Değerleri kaydet
            supertrend.iloc[i] = current_supertrend
            trend_direction.iloc[i] = current_trend
        
        # İlk period değerlerini NaN yap
        supertrend.iloc[:period] = np.nan
        trend_direction.iloc[:period] = np.nan
        
        return {
            'supertrend': supertrend,
            'trend_direction': trend_direction,
            'atr': atr
        }
        
    except Exception as e:
        print(f"TA-Lib ile SuperTrend hesaplanırken hata: {str(e)}")
        
        # Manuel hesaplama (TA-Lib çalışmazsa)
        # True Range hesapla
        tr1 = df['high'] - df['low']
        tr2 = abs(df['high'] - df['close'].shift())
        tr3 = abs(df['low'] - df['close'].shift())
        tr = pd.concat([tr1, tr2, tr3], axis=1).max(axis=1)
        
        # ATR hesapla
        atr = tr.rolling(window=period).mean()
        
        # Üst ve alt bantları hesapla
        hl2 = (df['high'] + df['low']) / 2
        upper_band = hl2 + (multiplier * atr)
        lower_band = hl2 - (multiplier * atr)
        
        # SuperTrend hesaplama (yukarıdaki algoritmayı kullan)
        supertrend = pd.Series(0.0, index=df.index)
        trend_direction = pd.Series(1, index=df.index)
        
        # İlk değer
        supertrend.iloc[period] = lower_band.iloc[period]
        trend_direction.iloc[period] = 1
        
        # SuperTrend değerlerini hesapla
        for i in range(period + 1, len(df)):
            prev_supertrend = supertrend.iloc[i-1]
            current_close = df['close'].iloc[i]
            current_upper = upper_band.iloc[i]
            current_lower = lower_band.iloc[i]
            
            if prev_supertrend <= df['close'].iloc[i-1]:
                current_trend = 1
            else:
                current_trend = -1
            
            if current_trend == 1:
                if current_close <= current_lower:
                    current_supertrend = current_upper
                    current_trend = -1
                else:
                    current_supertrend = max(current_lower, prev_supertrend)
            else:
                if current_close >= current_upper:
                    current_supertrend = current_lower
                    current_trend = 1
                else:
                    current_supertrend = min(current_upper, prev_supertrend)
            
            supertrend.iloc[i] = current_supertrend
            trend_direction.iloc[i] = current_trend
        
        # İlk period değerlerini NaN yap
        supertrend.iloc[:period] = np.nan
        trend_direction.iloc[:period] = np.nan
        
        return {
            'supertrend': supertrend,
            'trend_direction': trend_direction,
            'atr': atr
        }

def get_signal(close, supertrend_value, trend_direction):
    """
    SuperTrend değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    close (float): Son kapanış fiyatı
    supertrend_value (float): SuperTrend değeri
    trend_direction (float): Trend yönü (1: yukarı, -1: aşağı)
    
    Dönen değer:
    str: 'BUY', 'SELL' veya 'NEUTRAL'
    """
    # Değerleri kontrol et
    if close is None or supertrend_value is None or trend_direction is None:
        return 'NEUTRAL'
    if np.isnan(close) or np.isnan(supertrend_value) or np.isnan(trend_direction):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    
    # Trend yönü ve fiyat ilişkisine göre sinyal belirle
    if trend_direction == 1 and close > supertrend_value:
        signal = 'BUY'
    elif trend_direction == -1 and close < supertrend_value:
        signal = 'SELL'
    
    return signal