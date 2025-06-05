import pandas as pd
import numpy as np

def calculate(df, method='standard'):
    """
    Pivot Points (Pivot Noktaları) hesaplar
    
    Parametreler:
    df (DataFrame): OHLCV veri çerçevesi (high, low, close sütunları içermeli)
    method (str): Pivot hesaplama metodu ('standard', 'fibonacci', 'camarilla', 'woodie')
    
    Dönen değer:
    dict: pivot değerlerini içeren dict
    """
    if df.empty:
        return None
    
    try:
        # Son periyot için high, low, close değerlerini al
        high = df['high'].iloc[-1]
        low = df['low'].iloc[-1]
        close = df['close'].iloc[-1]
        
        result = {}
        
        # Standart Pivot Points
        if method == 'standard':
            pivot = (high + low + close) / 3
            r1 = (2 * pivot) - low
            s1 = (2 * pivot) - high
            r2 = pivot + (high - low)
            s2 = pivot - (high - low)
            r3 = high + 2 * (pivot - low)
            s3 = low - 2 * (high - pivot)
            
            result = {
                'pivot': pivot,
                'r1': r1,
                'r2': r2,
                'r3': r3,
                's1': s1,
                's2': s2,
                's3': s3
            }
            
        # Fibonacci Pivot Points
        elif method == 'fibonacci':
            pivot = (high + low + close) / 3
            r1 = pivot + 0.382 * (high - low)
            s1 = pivot - 0.382 * (high - low)
            r2 = pivot + 0.618 * (high - low)
            s2 = pivot - 0.618 * (high - low)
            r3 = pivot + 1.0 * (high - low)
            s3 = pivot - 1.0 * (high - low)
            
            result = {
                'pivot': pivot,
                'r1': r1,
                'r2': r2,
                'r3': r3,
                's1': s1,
                's2': s2,
                's3': s3
            }
            
        # Camarilla Pivot Points
        elif method == 'camarilla':
            pivot = (high + low + close) / 3
            r1 = close + 1.1 * (high - low) / 12
            s1 = close - 1.1 * (high - low) / 12
            r2 = close + 1.1 * (high - low) / 6
            s2 = close - 1.1 * (high - low) / 6
            r3 = close + 1.1 * (high - low) / 4
            s3 = close - 1.1 * (high - low) / 4
            r4 = close + 1.1 * (high - low) / 2
            s4 = close - 1.1 * (high - low) / 2
            
            result = {
                'pivot': pivot,
                'r1': r1,
                'r2': r2,
                'r3': r3,
                'r4': r4,
                's1': s1,
                's2': s2,
                's3': s3,
                's4': s4
            }
            
        # Woodie's Pivot Points
        elif method == 'woodie':
            pivot = (high + low + 2 * close) / 4
            r1 = (2 * pivot) - low
            s1 = (2 * pivot) - high
            r2 = pivot + (high - low)
            s2 = pivot - (high - low)
            
            result = {
                'pivot': pivot,
                'r1': r1,
                'r2': r2,
                's1': s1,
                's2': s2
            }
            
        return result
        
    except Exception as e:
        print(f"Pivot Points hesaplanırken hata: {str(e)}")
        return None

def get_signal(current_price, pivots, threshold_pct=0.003):
    """
    Pivot Points değerlerine göre alım-satım sinyali oluştur
    
    Parametreler:
    current_price (float): Güncel fiyat
    pivots (dict): Pivot noktaları 
    threshold_pct (float): Pivot noktalarına yakınlık eşiği (varsayılan=0.003, %0.3)
    
    Dönen değer:
    dict: 'signal' ve 'reason' içeren dict
    """
    if current_price is None or pivots is None:
        return {'signal': 'NEUTRAL', 'reason': 'Veri yetersiz'}
    
    try:
        pivot = pivots.get('pivot')
        
        if pivot is None:
            return {'signal': 'NEUTRAL', 'reason': 'Pivot değeri hesaplanamadı'}
        
        # Pivot noktalarını kontrol et
        for level_name in ['s3', 's2', 's1', 'r1', 'r2', 'r3']:
            if level_name not in pivots:
                continue
                
            level_value = pivots[level_name]
            
            # Fiyatın pivot seviyesine yakınlığını kontrol et
            price_diff_pct = abs(current_price - level_value) / current_price
            
            if price_diff_pct <= threshold_pct:  # Eşiğe yakın
                if level_name.startswith('s'):  # Destek seviyesi
                    return {
                        'signal': 'BUY', 
                        'reason': f"Fiyat {level_name.upper()} destek seviyesine yakın ({level_value:.4f})"
                    }
                elif level_name.startswith('r'):  # Direnç seviyesi
                    return {
                        'signal': 'SELL', 
                        'reason': f"Fiyat {level_name.upper()} direnç seviyesine yakın ({level_value:.4f})"
                    }
        
        # Pivot noktasının üstünde veya altında olma durumu
        if current_price > pivot * (1 + threshold_pct):
            return {
                'signal': 'BUY', 
                'reason': f"Fiyat pivot seviyesinin üstünde (Pivot: {pivot:.4f}, Fiyat: {current_price:.4f})"
            }
        elif current_price < pivot * (1 - threshold_pct):
            return {
                'signal': 'SELL', 
                'reason': f"Fiyat pivot seviyesinin altında (Pivot: {pivot:.4f}, Fiyat: {current_price:.4f})"
            }
        
        return {'signal': 'NEUTRAL', 'reason': 'Fiyat pivot bölgesinde'}
        
    except Exception as e:
        print(f"Pivot Points sinyal hesaplanırken hata: {str(e)}")
        return {'signal': 'NEUTRAL', 'reason': f"Hata: {str(e)}"}