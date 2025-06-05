import pandas as pd
import numpy as np
import talib
import logging

# Logger yapılandırması
logger = logging.getLogger("trading_bot")

def calculate(df, period=14):
    """
    ADX (Average Directional Index) hesaplar - TA-Lib ile optimize edilmiştir
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (high, low, close sütunları içermeli)
    period (int): ADX hesaplama periyodu (varsayılan=14)
    
    Dönen değer:
    DataFrame: adx, plus_di, minus_di sütunlarını içeren DataFrame
    """
    if df is None or len(df) < period * 2:  # ADX için en az 2*period mum gereklidir
        logger.warning(f"ADX için yeterli veri yok (gerekli: {period*2}, mevcut: {len(df) if df is not None else 0})")
        return pd.DataFrame(index=df.index if df is not None else [], columns=['adx', 'plus_di', 'minus_di'], data=np.nan)
    
    try:
        # TA-Lib ile optimize edilmiş ADX hesaplama
        high_array = df['high'].values
        low_array = df['low'].values
        close_array = df['close'].values
        
        # TA-Lib ADX, +DI, -DI hesaplama (ayrı fonksiyonlar)
        adx = talib.ADX(high_array, low_array, close_array, timeperiod=period)
        plus_di = talib.PLUS_DI(high_array, low_array, close_array, timeperiod=period)
        minus_di = talib.MINUS_DI(high_array, low_array, close_array, timeperiod=period)
        
        # NaN kontrolü
        if np.isnan(adx[-1]) or np.isnan(plus_di[-1]) or np.isnan(minus_di[-1]):
            logger.warning(f"TA-Lib ADX hesaplaması NaN değerler içeriyor, veri kalitesini kontrol edin")
        
        # Verileri birleştir
        result = pd.DataFrame(index=df.index)
        result['adx'] = adx
        result['plus_di'] = plus_di
        result['minus_di'] = minus_di
        
        # Debuglama için son değerleri yazdır
        logger.debug(f"ADX son değerler: ADX={adx[-1]:.2f}, +DI={plus_di[-1]:.2f}, -DI={minus_di[-1]:.2f}")
        
        return result
        
    except Exception as e:
        logger.error(f"TA-Lib ile ADX hesaplanırken hata: {str(e)}, basitleştirilmiş hesaplamaya geçiliyor")
        
        # Basitleştirilmiş ADX hesaplama
        try:
            # True Range hesapla
            tr1 = df['high'] - df['low']
            tr2 = abs(df['high'] - df['close'].shift(1))
            tr3 = abs(df['low'] - df['close'].shift(1))
            tr = pd.concat([tr1, tr2, tr3], axis=1).max(axis=1)
            
            # ATR hesapla (period günlük TR ortalaması)
            atr = tr.rolling(window=period).mean()
            
            # +DM ve -DM hesapla
            plus_dm = df['high'].diff()
            minus_dm = df['low'].shift(1) - df['low']
            
            # Koşullar
            plus_dm = plus_dm.where(
                (plus_dm > 0) & (plus_dm > minus_dm), 0.0
            )
            minus_dm = minus_dm.where(
                (minus_dm > 0) & (minus_dm > plus_dm), 0.0
            )
            
            # +DI14 ve -DI14 hesapla (basitleştirilmiş)
            plus_di = 100 * (plus_dm.rolling(window=period).mean() / atr)
            minus_di = 100 * (minus_dm.rolling(window=period).mean() / atr)
            
            # DX hesapla: DX = |+DI14 - -DI14| / |+DI14 + -DI14| * 100
            dx = 100 * abs(plus_di - minus_di) / (plus_di + minus_di + 0.0001)  # sıfıra bölünme koruması
            
            # ADX = DX'in period günlük ortalaması
            adx = dx.rolling(window=period).mean()
            
            # Sonuç
            result = pd.DataFrame(index=df.index)
            result['adx'] = adx
            result['plus_di'] = plus_di
            result['minus_di'] = minus_di
            
            logger.info(f"Manuel ADX hesaplaması tamamlandı")
            return result
            
        except Exception as inner_e:
            logger.error(f"Manuel ADX hesaplamada da hata: {str(inner_e)}")
            return pd.DataFrame(index=df.index, columns=['adx', 'plus_di', 'minus_di'], data=np.nan)

def get_signal(adx, plus_di, minus_di, adx_threshold=25, di_separation=5):
    """
    ADX değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    adx (float): ADX değeri
    plus_di (float): +DI değeri
    minus_di (float): -DI değeri
    adx_threshold (int): ADX eşik değeri (varsayılan=25)
    di_separation (int): DI ayrım eşiği (varsayılan=5)
    
    Dönen değer:
    str: 'BUY', 'SELL', 'NEUTRAL' sinyali
    dict: Sinyal bilgisi ve ek detayları içeren sözlük
    """
    # Değerleri kontrol et
    if adx is None or plus_di is None or minus_di is None:
        return 'NEUTRAL'
    if np.isnan(adx) or np.isnan(plus_di) or np.isnan(minus_di):
        return 'NEUTRAL'
    
    signal = 'NEUTRAL'
    reason = ''
    trend_strength = 0
    
    # DI çizgileri arasındaki fark
    di_diff = abs(plus_di - minus_di)
    
    # ADX değeri eşik değerinden büyükse, güçlü trend vardır
    if adx > adx_threshold:
        # Trend gücü yüzdelik olarak hesapla (25-50 arası değerlere göre)
        trend_strength = min(((adx - adx_threshold) / 25) * 100, 100)
        
        # +DI -DI'dan büyükse, yükselen trend
        if plus_di > minus_di and di_diff > di_separation:
            signal = 'BUY'
            reason = f"Güçlü yükselen trend (ADX: {adx:.1f}, +DI: {plus_di:.1f}, -DI: {minus_di:.1f})"
        
        # -DI +DI'dan büyükse, düşen trend
        elif minus_di > plus_di and di_diff > di_separation:
            signal = 'SELL'
            reason = f"Güçlü düşen trend (ADX: {adx:.1f}, +DI: {plus_di:.1f}, -DI: {minus_di:.1f})"
    
    # Çok yüksek ADX değerleri (>40) trendin sonuna yaklaşıldığını gösterebilir
    elif adx > 40 and adx < 50:
        if plus_di < minus_di and plus_di > plus_di - 5:  # +DI yükselmeye başlamış
            signal = 'BUY'
            reason = f"Trend dönüşü olabilir, +DI yükselmeye başlıyor (ADX: {adx:.1f})"
            trend_strength = 30  # Daha düşük güven
        
        elif minus_di < plus_di and minus_di > minus_di - 5:  # -DI yükselmeye başlamış
            signal = 'SELL'
            reason = f"Trend dönüşü olabilir, -DI yükselmeye başlıyor (ADX: {adx:.1f})"
            trend_strength = 30  # Daha düşük güven
    
    # Düşünce: Basit string dönüş yerine daha fazla bilgi içeren bir sözlük döndürebiliriz
    # Şimdilik geriye doğru uyumluluğu korumak için string dönüyoruz
    
    # Detaylı bir sözlük dönmek için:
    # return {
    #     'signal': signal,
    #     'reason': reason,
    #     'strength': trend_strength,
    #     'adx': adx,
    #     'plus_di': plus_di,
    #     'minus_di': minus_di
    # }
    
    return signal