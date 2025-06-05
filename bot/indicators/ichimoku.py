import pandas as pd
import numpy as np
import talib

def calculate(df, tenkan_period=9, kijun_period=26, senkou_span_b_period=52, displacement=26):
    """
    Ichimoku Cloud (Ichimoku Kinko Hyo) hesaplar
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi
    tenkan_period (int): Tenkan-sen (dönüş çizgisi) periyodu (varsayılan=9)
    kijun_period (int): Kijun-sen (temel çizgi) periyodu (varsayılan=26)
    senkou_span_b_period (int): Senkou Span B periyodu (varsayılan=52)
    displacement (int): Senkou Span A ve B için öteleme (varsayılan=26)
    
    Dönen değer:
    DataFrame: tenkan_sen, kijun_sen, senkou_span_a, senkou_span_b, chikou_span sütunlarını içeren DataFrame
    """
    if len(df) < max(tenkan_period, kijun_period, senkou_span_b_period, displacement):
        return pd.DataFrame(index=df.index, columns=['tenkan_sen', 'kijun_sen', 'senkou_span_a', 'senkou_span_b', 'chikou_span'])
    
    try:
        # TA-Lib'de doğrudan Ichimoku fonksiyonu olmadığından, her bileşeni ayrı hesaplıyoruz
        
        # 1. Tenkan-sen (Dönüş Çizgisi): (n-periyot yüksek + n-periyot düşük) / 2
        high_tenkan = df['high'].rolling(window=tenkan_period).max()
        low_tenkan = df['low'].rolling(window=tenkan_period).min()
        tenkan_sen = (high_tenkan + low_tenkan) / 2
        
        # 2. Kijun-sen (Temel Çizgi): (n-periyot yüksek + n-periyot düşük) / 2
        high_kijun = df['high'].rolling(window=kijun_period).max()
        low_kijun = df['low'].rolling(window=kijun_period).min()
        kijun_sen = (high_kijun + low_kijun) / 2
        
        # 3. Senkou Span A (Öncü İşaret A): (Tenkan-sen + Kijun-sen) / 2, 26 periyot ileri
        senkou_span_a = ((tenkan_sen + kijun_sen) / 2).shift(displacement)
        
        # 4. Senkou Span B (Öncü İşaret B): (n-periyot yüksek + n-periyot düşük) / 2, 26 periyot ileri
        high_senkou = df['high'].rolling(window=senkou_span_b_period).max()
        low_senkou = df['low'].rolling(window=senkou_span_b_period).min()
        senkou_span_b = ((high_senkou + low_senkou) / 2).shift(displacement)
        
        # 5. Chikou Span (Gecikmeli İşaret): Kapanış fiyatı, 26 periyot geriye
        chikou_span = df['close'].shift(-displacement)
        
        # Verileri birleştir
        result = pd.DataFrame(index=df.index)
        result['tenkan_sen'] = tenkan_sen
        result['kijun_sen'] = kijun_sen
        result['senkou_span_a'] = senkou_span_a
        result['senkou_span_b'] = senkou_span_b
        result['chikou_span'] = chikou_span
        
        return result
    
    except Exception as e:
        print(f"Ichimoku Cloud hesaplanırken hata: {str(e)}")
        return pd.DataFrame(index=df.index, columns=['tenkan_sen', 'kijun_sen', 'senkou_span_a', 'senkou_span_b', 'chikou_span'])

def get_signal(close_price, ichimoku_data):
    """
    Ichimoku Cloud değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    close_price (float): Son kapanış fiyatı
    ichimoku_data (dict): Ichimoku bileşenlerini içeren sözlük
    
    Dönen değer:
    str: 'BUY', 'SELL' veya 'NEUTRAL'
    """
    # Değerleri kontrol et
    if close_price is None or ichimoku_data is None:
        return 'NEUTRAL'
    
    tenkan_sen = ichimoku_data.get('tenkan_sen')
    kijun_sen = ichimoku_data.get('kijun_sen')
    senkou_span_a = ichimoku_data.get('senkou_span_a')
    senkou_span_b = ichimoku_data.get('senkou_span_b')
    
    # Eksik değerler varsa, nötr sinyal döndür
    if any(x is None or np.isnan(x) for x in [tenkan_sen, kijun_sen, senkou_span_a, senkou_span_b]):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    
    # Bulut bölgesinin durumu
    cloud_is_green = senkou_span_a > senkou_span_b
    price_above_cloud = close_price > max(senkou_span_a, senkou_span_b)
    price_below_cloud = close_price < min(senkou_span_a, senkou_span_b)
    
    # TK Çapraz (Tenkan-Kijun çaprazı)
    tk_cross_bullish = tenkan_sen > kijun_sen
    
    # ALIM SİNYALLERİ
    if price_above_cloud and cloud_is_green and tk_cross_bullish:
        # Güçlü alım sinyali: Fiyat yeşil bulutun üstünde ve TK çaprazı yukarı
        signal = 'BUY'
    elif price_below_cloud and not cloud_is_green and not tk_cross_bullish:
        # Güçlü satım sinyali: Fiyat kırmızı bulutun altında ve TK çaprazı aşağı
        signal = 'SELL'
    
    return signal