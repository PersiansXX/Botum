<?php
/**
 * Gerçek zamanlı indikatör hesaplama API'si
 * Binance'den anlık fiyat verilerini çeker ve bunlara göre indikatörleri hesaplar
 */
define('TRADING_BOT', true);
header('Content-Type: application/json');
session_start();

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Gerekli dosyaları ekle
require_once 'bot_api.php';
require_once 'binance_api.php';

// API ve Binance API nesnelerini oluştur
$bot_api = new BotAPI();
$binance_api = new BinanceAPI();

// Sonuç dizisi
$result = [
    'success' => false,
    'message' => '',
    'data' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    // Aktif coinleri al
    $active_coins = $bot_api->getActiveCoins();
    
    if (empty($active_coins)) {
        $result['message'] = 'Aktif coin bulunamadı';
        echo json_encode($result);
        exit;
    }
    
    // Binance'den tüm verileri çek
    $binance_data = $binance_api->get24hStats();
    
    if (empty($binance_data)) {
        $result['message'] = 'Binance\'den veri alınamadı';
        echo json_encode($result);
        exit;
    }
    
    // Her coini güncelle ve indikatörleri hesapla
    foreach ($active_coins as $key => $coin) {
        $symbol = $coin['symbol'];
        
        // Binance'den bu coin için veri var mı kontrol et
        if (isset($binance_data[$symbol])) {
            // Fiyat ve değişim verilerini güncelle
            $coin['price'] = floatval($binance_data[$symbol]['lastPrice']);
            $coin['change_24h'] = floatval($binance_data[$symbol]['priceChangePercent']);
            
            // İndikatörleri hesapla
            $coin = calculateIndicators($coin, $binance_api);
            
            // Güncellenmiş coin'i ata
            $active_coins[$key] = $coin;
        }
    }
    
    $result['success'] = true;
    $result['data'] = $active_coins;
    
} catch (Exception $e) {
    $result['message'] = 'Hata: ' . $e->getMessage();
}

echo json_encode($result);
exit;

/**
 * Verilen coin verisiyle indikatörleri hesaplar
 * 
 * @param array $coin Coin verisi
 * @param BinanceAPI $binance_api Binance API nesnesi
 * @return array Güncellenmiş coin verisi
 */
function calculateIndicators($coin, $binance_api) {
    $symbol = $coin['symbol'];
    $price = $coin['price'];
    
    // Binance'den kline (mum) verileri al
    $klines = $binance_api->getKlines($symbol, '1h', 100);
    
    if (empty($klines)) {
        return $coin;
    }
    
    // Kapanış fiyatlarını diziye dönüştür
    $closes = array_column($klines, 'close');
    $closes = array_map('floatval', $closes);
    
    // En son kapanış fiyatını güncelle
    $closes[count($closes) - 1] = $price;
    
    // RSI hesapla (14 periyot)
    $coin['indicators']['rsi'] = calculateRSI($closes, 14);
    
    // MACD hesapla (12, 26, 9)
    $coin['indicators']['macd'] = calculateMACD($closes, 12, 26, 9);
    
    // Bollinger Bands hesapla (20 periyot, 2 standart sapma)
    $coin['indicators']['bollinger'] = calculateBollingerBands($closes, 20, 2);
    
    // Hareketli ortalamalar hesapla
    $coin['indicators']['moving_averages'] = calculateMovingAverages($closes);
    
    // Genel sinyal değerini belirle
    $coin['signal'] = determineSignal($coin);
    
    // Son güncelleme zamanını ekle
    $coin['last_updated'] = date('Y-m-d H:i:s');
    
    return $coin;
}

/**
 * RSI (Göreceli Güç İndeksi) hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $period Periyot
 * @return array RSI değeri ve sinyali
 */
function calculateRSI($closes, $period = 14) {
    if (count($closes) <= $period) {
        return ['value' => 50, 'signal' => 'NEUTRAL'];
    }
    
    $gains = [];
    $losses = [];
    
    // İlk değişimleri hesapla
    for ($i = 1; $i < count($closes); $i++) {
        $change = $closes[$i] - $closes[$i - 1];
        $gains[] = $change > 0 ? $change : 0;
        $losses[] = $change < 0 ? abs($change) : 0;
    }
    
    // İlk ortalama kazanç ve kayıpları hesapla
    $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
    $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
    
    // Sonraki değerleri hesapla (smooth)
    for ($i = $period; $i < count($gains); $i++) {
        $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
        $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
    }
    
    if ($avgLoss == 0) {
        $rsi = 100;
    } else {
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));
    }
    
    // RSI değerini 0-100 aralığında tut
    $rsi = min(100, max(0, $rsi));
    
    // Sinyal hesapla
    $signal = 'NEUTRAL';
    if ($rsi <= 30) $signal = 'BUY';
    else if ($rsi >= 70) $signal = 'SELL';
    
    return [
        'value' => round($rsi, 2),
        'signal' => $signal
    ];
}

/**
 * MACD (Hareketli Ortalama Yakınsama/Uzaklaşma) hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $fast Hızlı EMA periyodu
 * @param int $slow Yavaş EMA periyodu
 * @param int $signal Sinyal EMA periyodu
 * @return array MACD değeri, sinyal çizgisi ve histogram
 */
function calculateMACD($closes, $fast = 12, $slow = 26, $signal_period = 9) {
    if (count($closes) <= $slow) {
        return [
            'value' => 0,
            'signal_line' => 0,
            'histogram' => 0,
            'signal' => 'NEUTRAL'
        ];
    }
    
    // EMA hesaplama
    $ema_fast = calculateEMA($closes, $fast);
    $ema_slow = calculateEMA($closes, $slow);
    
    // MACD çizgisi hesapla
    $macd_line = $ema_fast - $ema_slow;
    
    // MACD için sinyal çizgisini hesapla
    $macd_vals = [];
    for ($i = 0; $i < count($closes) - $slow + 1; $i++) {
        $macd_vals[] = $ema_fast[$i] - $ema_slow[$i];
    }
    
    $signal_line = calculateEMA($macd_vals, $signal_period);
    
    // Histogram hesapla
    $histogram = end($macd_vals) - end($signal_line);
    
    // Sinyal belirle
    $signal = 'NEUTRAL';
    if (end($macd_vals) > end($signal_line)) {
        $signal = 'BUY';
    } else if (end($macd_vals) < end($signal_line)) {
        $signal = 'SELL';
    }
    
    return [
        'value' => round(end($macd_vals), 4),
        'signal_line' => round(end($signal_line), 4),
        'histogram' => round($histogram, 4),
        'signal' => $signal
    ];
}

/**
 * Bollinger Bantları hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $period Periyot
 * @param float $deviation Standart sapma çarpanı
 * @return array Üst bant, orta bant ve alt bant
 */
function calculateBollingerBands($closes, $period = 20, $deviation = 2) {
    if (count($closes) <= $period) {
        $last_price = end($closes);
        return [
            'upper' => $last_price * 1.02,
            'middle' => $last_price,
            'lower' => $last_price * 0.98
        ];
    }
    
    // Hareketli ortalama hesapla (orta bant)
    $middle = calculateSMA($closes, $period);
    
    // Standart sapma hesapla
    $std_dev = calculateStdDev($closes, $period);
    
    // Üst ve alt bantları hesapla
    $upper = end($middle) + ($deviation * $std_dev);
    $lower = end($middle) - ($deviation * $std_dev);
    
    return [
        'upper' => round($upper, 4),
        'middle' => round(end($middle), 4),
        'lower' => round($lower, 4)
    ];
}

/**
 * Hareketli Ortalamalar hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @return array MA20, MA50, MA100, MA200 değerleri
 */
function calculateMovingAverages($closes) {
    $ma20 = count($closes) >= 20 ? array_sum(array_slice($closes, -20)) / 20 : array_sum($closes) / count($closes);
    $ma50 = count($closes) >= 50 ? array_sum(array_slice($closes, -50)) / 50 : array_sum($closes) / count($closes);
    $ma100 = count($closes) >= 100 ? array_sum(array_slice($closes, -100)) / 100 : array_sum($closes) / count($closes);
    $ma200 = count($closes) >= 200 ? array_sum(array_slice($closes, -200)) / 200 : array_sum($closes) / count($closes);
    
    return [
        'ma20' => round($ma20, 4),
        'ma50' => round($ma50, 4),
        'ma100' => round($ma100, 4),
        'ma200' => round($ma200, 4)
    ];
}

/**
 * Basit Hareketli Ortalama (SMA) hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $period Periyot
 * @return array SMA değerleri
 */
function calculateSMA($closes, $period) {
    $sma = [];
    for ($i = $period - 1; $i < count($closes); $i++) {
        $sum = 0;
        for ($j = 0; $j < $period; $j++) {
            $sum += $closes[$i - $j];
        }
        $sma[] = $sum / $period;
    }
    return $sma;
}

/**
 * Üssel Hareketli Ortalama (EMA) hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $period Periyot
 * @return array EMA değerleri
 */
function calculateEMA($closes, $period) {
    // İlk değer SMA
    $ema = [];
    $sma = array_sum(array_slice($closes, 0, $period)) / $period;
    $ema[] = $sma;
    
    // Çarpan hesapla
    $multiplier = 2 / ($period + 1);
    
    // EMA hesapla
    for ($i = $period; $i < count($closes); $i++) {
        $ema[] = ($closes[$i] - end($ema)) * $multiplier + end($ema);
    }
    
    return $ema;
}

/**
 * Standart sapma hesaplar
 * 
 * @param array $closes Kapanış fiyatları
 * @param int $period Periyot
 * @return float Standart sapma
 */
function calculateStdDev($closes, $period) {
    $last_values = array_slice($closes, -$period);
    $avg = array_sum($last_values) / count($last_values);
    
    $variance = 0;
    foreach ($last_values as $value) {
        $variance += pow($value - $avg, 2);
    }
    
    return sqrt($variance / count($last_values));
}

/**
 * İndikatörlere göre sinyal belirler
 * 
 * @param array $coin Coin verisi
 * @return string Sinyal (BUY, SELL, NEUTRAL)
 */
function determineSignal($coin) {
    $price = $coin['price'];
    $indicators = $coin['indicators'];
    
    $buy_signals = 0;
    $sell_signals = 0;
    $total_indicators = 0;
    
    // RSI sinyali
    if (isset($indicators['rsi']['value'])) {
        $total_indicators++;
        if ($indicators['rsi']['value'] <= 30) {
            $buy_signals++;
        } else if ($indicators['rsi']['value'] >= 70) {
            $sell_signals++;
        }
    }
    
    // MACD sinyali
    if (isset($indicators['macd']['signal'])) {
        $total_indicators++;
        if ($indicators['macd']['signal'] == 'BUY') {
            $buy_signals++;
        } else if ($indicators['macd']['signal'] == 'SELL') {
            $sell_signals++;
        }
    }
    
    // Bollinger Bands sinyali
    if (isset($indicators['bollinger']) && isset($price)) {
        $total_indicators++;
        if ($price <= $indicators['bollinger']['lower']) {
            $buy_signals++;
        } else if ($price >= $indicators['bollinger']['upper']) {
            $sell_signals++;
        }
    }
    
    // Hareketli Ortalama sinyali
    if (isset($indicators['moving_averages']['ma20']) && isset($indicators['moving_averages']['ma50'])) {
        $total_indicators++;
        if ($indicators['moving_averages']['ma20'] > $indicators['moving_averages']['ma50']) {
            $buy_signals++;
        } else if ($indicators['moving_averages']['ma20'] < $indicators['moving_averages']['ma50']) {
            $sell_signals++;
        }
    }
    
    // Sinyal belirle
    if ($total_indicators > 0) {
        $buy_percent = ($buy_signals / $total_indicators) * 100;
        $sell_percent = ($sell_signals / $total_indicators) * 100;
        
        if ($buy_percent >= 50 && $buy_percent > $sell_percent) {
            return 'BUY';
        } else if ($sell_percent >= 50 && $sell_percent > $buy_percent) {
            return 'SELL';
        }
    }
    
    return 'NEUTRAL';
}
?>