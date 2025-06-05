import pandas as pd
import numpy as np
import talib

def calculate(df, window=20, num_std=2):
    """
    Bollinger Bands hesapla - TA-Lib ile daha hassas ve hızlı hesaplama
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    window (int): Periyot uzunluğu
    num_std (float): Standart sapma çarpanı
    
    Dönen değer:
    DataFrame: upper_band, middle_band, lower_band sütunlarını içeren df
    """
    if len(df) < window:
        return None
    
    try:
        # TA-Lib Bollinger Bands hesaplama
        upper_band, middle_band, lower_band = talib.BBANDS(
            df['close'].values, 
            timeperiod=window, 
            nbdevup=num_std,
            nbdevdn=num_std,
            matype=0  # 0 = Simple Moving Average
        )
        
        # Verileri birleştir
        df_result = pd.DataFrame(index=df.index)
        df_result['upper_band'] = upper_band
        df_result['middle_band'] = middle_band
        df_result['lower_band'] = lower_band
        
        return df_result
        
    except Exception as e:
        # TA-Lib başarısız olursa, geleneksel yönteme geri dön
        # Orta band (basit hareketli ortalama)
        middle_band = df['close'].rolling(window=window).mean()
        
        # Standart sapma
        rolling_std = df['close'].rolling(window=window).std()
        
        # Üst ve alt bandlar
        upper_band = middle_band + (rolling_std * num_std)
        lower_band = middle_band - (rolling_std * num_std)
        
        # Verileri birleştir
        df_result = pd.DataFrame(index=df.index)
        df_result['upper_band'] = upper_band
        df_result['middle_band'] = middle_band
        df_result['lower_band'] = lower_band
        
        return df_result