import pandas as pd
import numpy as np
import talib

def calculate(df, fast_period=12, slow_period=26, signal_period=9):
    """
    MACD (Hareketli Ortalama Yakınsama/Iraksama) hesapla - TA-Lib ile optimize edildi
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    fast_period (int): Hızlı EMA periyodu
    slow_period (int): Yavaş EMA periyodu
    signal_period (int): Sinyal EMA periyodu
    
    Dönen değer:
    DataFrame: macd, signal, histogram sütunlarını içeren df
    """
    if len(df) < max(fast_period, slow_period, signal_period):
        return None
    
    try:
        # TA-Lib MACD hesaplama
        macd_line, signal_line, macd_histogram = talib.MACD(
            df['close'].values,
            fastperiod=fast_period,
            slowperiod=slow_period,
            signalperiod=signal_period
        )
        
        # Verileri birleştir
        df_result = pd.DataFrame(index=df.index)
        df_result['macd'] = macd_line
        df_result['signal'] = signal_line
        df_result['histogram'] = macd_histogram
        
        return df_result
        
    except Exception as e:
        # TA-Lib başarısız olursa, geleneksel yönteme geri dön
        # Hızlı ve yavaş üssel hareketli ortalamalar
        ema_fast = df['close'].ewm(span=fast_period, adjust=False).mean()
        ema_slow = df['close'].ewm(span=slow_period, adjust=False).mean()
        
        # MACD çizgisi
        macd_line = ema_fast - ema_slow
        
        # Sinyal çizgisi (MACD'nin EMA'sı)
        signal_line = macd_line.ewm(span=signal_period, adjust=False).mean()
        
        # Histogram (MACD - Sinyal)
        macd_histogram = macd_line - signal_line
        
        # Verileri birleştir
        df_result = pd.DataFrame(index=df.index)
        df_result['macd'] = macd_line
        df_result['signal'] = signal_line
        df_result['histogram'] = macd_histogram
        
        return df_result