def analyze(df, indicators):
    """
    Kısa vadeli strateji analizi
    """
    # RSI kontrolü
    if 'rsi' not in indicators:
        return None, "RSI hesaplanamadı"
        
    # RSI değeri liste veya tek değer olabilir, buna uygun şekilde işlem yapılıyor
    rsi = indicators['rsi']
    
    # RSI değeri doğrudan kullanılıyor
    if rsi > 70:
        return "SELL", f"RSI yüksek seviyede: {rsi:.2f}"
    elif rsi < 30:
        return "BUY", f"RSI düşük seviyede: {rsi:.2f}"
        
    return None, ""
