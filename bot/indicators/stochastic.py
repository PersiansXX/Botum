import pandas as pd
import numpy as np
import talib
import logging

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

def calculate(df, k_period=14, d_period=3, slowing=3):
    """
    Stochastic Oscillator hesaplar - TA-Lib ile optimize edilmiştir
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (en azından high, low, close sütunları içermeli)
    k_period (int): %K periyodu (varsayılan=14)
    d_period (int): %D periyodu (varsayılan=3)
    slowing (int): Yavaşlatma periyodu (varsayılan=3)
    
    Dönen değer:
    DataFrame: k_line, d_line sütunlarını içeren DataFrame
    """
    # Parametreleri sayısal değerlere dönüştür
    try:
        k_period = int(k_period)
        d_period = int(d_period)
        slowing = int(slowing)
    except (ValueError, TypeError):
        logger.error(f"Stochastic parametreleri sayısal değer değil: k_period={k_period}, d_period={d_period}, slowing={slowing}")
        k_period = 14  # Varsayılan değer
        d_period = 3   # Varsayılan değer
        slowing = 3    # Varsayılan değer
        
    if df is None or len(df) < k_period:
        logger.warning(f"Stochastic için yeterli veri yok (gerekli: {k_period}, mevcut: {len(df) if df is not None else 0})")
        return pd.DataFrame(index=df.index if df is not None else [], columns=['k_line', 'd_line'], data=np.nan)
    
    try:
        # TA-Lib ile optimize edilmiş Stochastic hesaplama
        high_array = df['high'].values
        low_array = df['low'].values
        close_array = df['close'].values
        
        # TA-Lib Stochastic hesaplama
        k_line, d_line = talib.STOCH(
            high_array,
            low_array, 
            close_array,
            fastk_period=k_period,
            slowk_period=slowing,
            slowk_matype=0,  # 0=SMA, 1=EMA, 2=WMA, 3=DEMA, 4=TEMA, 5=TRIMA, 6=KAMA, 7=MAMA, 8=T3
            slowd_period=d_period,
            slowd_matype=0
        )
        
        # NaN kontrolü
        if np.isnan(k_line[-1]) or np.isnan(d_line[-1]):
            logger.warning(f"TA-Lib Stochastic hesaplaması NaN değerler içeriyor, veri kalitesini kontrol edin")
            # Ama yine de devam et, NaN olmayan değerler hala kullanılabilir
            
        # Verileri birleştir
        result = pd.DataFrame(index=df.index)
        result['k_line'] = k_line
        result['d_line'] = d_line
        
        return result
        
    except Exception as e:
        logger.error(f"TA-Lib ile Stochastic hesaplanırken hata: {str(e)}, manuel hesaplamaya geçiliyor")
        
        # Manuel hesaplama (fallback)
        try:
            # En düşük-en yüksek değerleri bul
            lowest_low = df['low'].rolling(window=k_period).min()
            highest_high = df['high'].rolling(window=k_period).max()
            
            # %K hesapla (HPLC - Highest Price Lowest Close)
            # (close - lowest_low) / (highest_high - lowest_low) * 100
            
            # Sıfıra bölme durumunu önle
            price_range = highest_high - lowest_low
            price_range = price_range.replace(0, np.nan)  # Sıfırı NaN ile değiştir
            
            k_raw = 100 * ((df['close'] - lowest_low) / price_range)
            
            # Yavaşlatma uygula (smoothing)
            k_line = k_raw.rolling(window=slowing, min_periods=1).mean() if slowing > 1 else k_raw
            
            # %D hesapla (K'nın SMA'sı)
            d_line = k_line.rolling(window=d_period, min_periods=1).mean()
            
            # Verileri birleştir
            result = pd.DataFrame(index=df.index)
            result['k_line'] = k_line
            result['d_line'] = d_line
            
            return result
            
        except Exception as inner_e:
            logger.error(f"Manuel Stochastic hesaplamada da hata: {str(inner_e)}")
            return pd.DataFrame(index=df.index, columns=['k_line', 'd_line'], data=np.nan)

def get_signal(k_line, d_line, oversold_level=20, overbought_level=80):
    """
    Stochastic değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    k_line (float): %K değeri
    d_line (float): %D değeri
    oversold_level (int): Aşırı satım seviyesi (varsayılan=20)
    overbought_level (int): Aşırı alım seviyesi (varsayılan=80)
    
    Dönen değer:
    str: 'BUY', 'SELL' veya 'NEUTRAL'
    dict: Sinyal bilgisi ve ek detayları içeren sözlük
    """
    # Her iki değeri kontrol et
    if k_line is None or d_line is None or np.isnan(k_line) or np.isnan(d_line):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    reason = ''
    signal_strength = 0
    
    # Aşırı alım/satım bölgeleri
    if k_line < oversold_level and d_line < oversold_level:
        # Aşırı satım bölgesi
        if k_line > d_line:  # %K %D'yi yukarı kesiyor
            signal = 'BUY'
            reason = 'Stochastik aşırı satım bölgesinde ve %K %D\'yi yukarı kesiyor'
            # K ve D'nin birbirine göre durumu ne kadar yukarı ise o kadar güçlü
            signal_strength = min((k_line - d_line) * 5, 100)  # Farkın 5 katını al, max 100
        else:
            # Aşırı satımdayız ama henüz kesişme yok
            signal = 'NEUTRAL'
            reason = 'Stochastik aşırı satım bölgesinde ama %K %D\'yi henüz yukarı kesmiyor'
    
    elif k_line > overbought_level and d_line > overbought_level:
        # Aşırı alım bölgesi
        if k_line < d_line:  # %K %D'yi aşağı kesiyor
            signal = 'SELL'
            reason = 'Stochastik aşırı alım bölgesinde ve %K %D\'yi aşağı kesiyor'
            # K ve D'nin birbirine göre durumu ne kadar aşağı ise o kadar güçlü
            signal_strength = min((d_line - k_line) * 5, 100)  # Farkın 5 katını al, max 100
        else:
            # Aşırı alımdayız ama henüz kesişme yok
            signal = 'NEUTRAL'
            reason = 'Stochastik aşırı alım bölgesinde ama %K %D\'yi henüz aşağı kesmiyor'
    
    # Ortalama bölgeden çıkma (20-80 arası)
    elif 40 < k_line < 60 and 40 < d_line < 60:
        # Nötr bölgedeyiz, bir trendin başlangıcında olabiliriz
        if k_line > d_line and k_line - d_line > 3:  # Yukarı gidiş
            signal = 'BUY'
            reason = 'Stochastik nötr bölgede ama yukarı hareket var'
            signal_strength = 40  # Nötr bölgedeki bir sinyal daha zayıftır
        elif k_line < d_line and d_line - k_line > 3:  # Aşağı gidiş
            signal = 'SELL'
            reason = 'Stochastik nötr bölgede ama aşağı hareket var'
            signal_strength = 40  # Nötr bölgedeki bir sinyal daha zayıftır
    
    # Düşünce: Basit string dönüş yerine daha fazla bilgi içeren bir sözlük döndürebiliriz
    # Şimdilik geriye doğru uyumluluğu korumak için string dönüyoruz
    return signal