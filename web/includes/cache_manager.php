<?php
/**
 * Cache Manager - Trading Bot için gelişmiş önbellek (cache) yönetim sistemi
 * Bu dosya API ve veritabanı sorgularını önbelleğe alarak performansı artırır
 * Sadece değişen verileri günceller, değişmeyen veriler önbellekte saklanır
 */

// Cache klasör yolu
define('CACHE_DIR', dirname(__DIR__) . '/cache/');

// Cache dosyaları için izinler
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

/**
 * Önbellekten veri almak için
 * 
 * @param string $cache_key Önbellek anahtarı
 * @param int $ttl Saniye cinsinden geçerlilik süresi (Time To Live)
 * @param callable $hash_function Veri değişimi kontrolü için hash fonksiyonu (opsiyonel)
 * @return mixed Önbellekteki veri veya false
 */
function getCachedData($cache_key, $ttl = 300, $hash_function = null) {
    $cache_file = CACHE_DIR . md5($cache_key) . '.json';
    
    // Önbellek dosyası var mı ve TTL süresi geçerli mi kontrol et
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        $cached_content = file_get_contents($cache_file);
        $cached_data = json_decode($cached_content, true);
        
        // Hash kontrolü yapılacaksa
        if ($hash_function !== null && isset($cached_data['_hash'])) {
            // Gerçek verinin hash'ini hesapla
            $current_hash = call_user_func($hash_function);
            
            // Hash değeri aynıysa veriyi döndür, değilse false
            if ($current_hash === $cached_data['_hash']) {
                return $cached_data['data'];
            }
            return false;
        }
        
        // Hash kontrolü yoksa direkt veriyi döndür
        return isset($cached_data['data']) ? $cached_data['data'] : $cached_data;
    }
    
    return false;
}

/**
 * Veriyi önbelleğe kaydetmek için
 * 
 * @param string $cache_key Önbellek anahtarı
 * @param mixed $data Kaydedilecek veri
 * @param string $hash Verinin hash değeri (opsiyonel)
 * @return bool İşlem başarılı mı
 */
function setCachedData($cache_key, $data, $hash = null) {
    $cache_file = CACHE_DIR . md5($cache_key) . '.json';
    
    // Hash varsa veriyi hash ile birlikte kaydet
    if ($hash !== null) {
        $cache_data = [
            'data' => $data,
            '_hash' => $hash,
            '_timestamp' => time()
        ];
    } else {
        $cache_data = [
            'data' => $data,
            '_timestamp' => time()
        ];
    }
    
    // JSON'a dönüştür ve dosyaya kaydet
    $json_data = json_encode($cache_data, JSON_PRETTY_PRINT);
    return file_put_contents($cache_file, $json_data) !== false;
}

/**
 * Önbelleği temizlemek için
 * 
 * @param string $cache_key Temizlenecek önbellek anahtarı (boş ise tüm önbellek)
 * @return bool İşlem başarılı mı
 */
function clearCache($cache_key = '') {
    if (empty($cache_key)) {
        // Tüm önbelleği temizle
        $files = glob(CACHE_DIR . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    } else {
        // Belirli bir anahtara ait önbelleği temizle
        $cache_file = CACHE_DIR . md5($cache_key) . '.json';
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        return true;
    }
}

/**
 * API verileri için hash hesapla
 * Bu fonksiyon API'den dönen veri değiştiğinde önbelleği geçersiz kılmak için kullanılır
 * 
 * @param array $data Hash'i hesaplanacak veri
 * @return string Hash değeri
 */
function calculateDataHash($data) {
    return md5(json_encode($data));
}

/**
 * Veriyi önbellekten al veya API'den çek
 * Veri değişmediyse önbellekten alır, değiştiyse yeni veriyi çeker
 *
 * @param string $cache_key Önbellek anahtarı
 * @param int $ttl Saniye cinsinden geçerlilik süresi
 * @param callable $fetch_function Veriyi çekecek fonksiyon - örneğin function() { return $bot_api->getWeeklyProfitStats(); }
 * @param callable $hash_check_function Veri değişimini kontrol edecek fonksiyon (opsiyonel)
 * @return mixed Önbellekteki veri veya API'den yeni çekilen veri
 */
function getOrFetchData($cache_key, $ttl, $fetch_function, $hash_check_function = null) {
    global $api_calls; // API çağrı sayacı
    
    if (!isset($api_calls)) {
        $api_calls = 0;
    }
    
    // Önce önbellekten veriyi almayı dene
    $cached_data = getCachedData($cache_key, $ttl, $hash_check_function);
    
    // Eğer önbellekte yoksa veya veri değişmişse
    if ($cached_data === false) {
        // API'den veriyi çek
        $api_calls++;
        $fresh_data = call_user_func($fetch_function);
        
        // Hash hesapla ve veriyi önbelleğe kaydet
        $hash = calculateDataHash($fresh_data);
        setCachedData($cache_key, $fresh_data, $hash);
        
        return $fresh_data;
    }
    
    // Veri değişmemiş, önbellekten al
    return $cached_data;
}

/**
 * İki veri arasındaki değişiklikleri bulur ve sadece değişen kısımları günceller
 * 
 * @param mixed $old_data Eski veri
 * @param mixed $new_data Yeni veri
 * @return mixed Değişen kısımları içeren güncellenmiş veri
 */
function mergeChangedData($old_data, $new_data) {
    // Eğer diziyse, her bir anahtarı kontrol et
    if (is_array($old_data) && is_array($new_data)) {
        $result = $old_data;
        
        // Her yeni veri anahtarını kontrol et
        foreach ($new_data as $key => $value) {
            // Eski veride bu anahtar yoksa, ekle
            if (!isset($old_data[$key])) {
                $result[$key] = $value;
            } 
            // Değer bir dizi ise, recursive olarak kontrol et
            elseif (is_array($value) && is_array($old_data[$key])) {
                $result[$key] = mergeChangedData($old_data[$key], $value);
            }
            // Değer farklıysa güncelle
            elseif ($old_data[$key] !== $value) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    // Dizi değilse veya tipleri farklıysa yeni veriyi döndür
    return $new_data;
}

/**
 * Veriyi önbellekten al, değiştiyse sadece değişen kısımları güncelle
 * 
 * @param string $cache_key Önbellek anahtarı
 * @param int $ttl Saniye cinsinden geçerlilik süresi
 * @param callable $fetch_function Veriyi çekecek fonksiyon
 * @return mixed Önbellekteki veri veya güncellenmiş veri
 */
function getOrUpdateChangedData($cache_key, $ttl, $fetch_function) {
    $cache_file = CACHE_DIR . md5($cache_key) . '.json';
    $needs_update = false;
    
    // Önbellek dosyası var mı ve TTL süresi geçerli mi kontrol et
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        $cached_content = file_get_contents($cache_file);
        $cached_data = json_decode($cached_content, true);
        
        if (isset($cached_data['data'])) {
            // Sadece değişiklikleri kontrol etmek için yeni veriyi çek
            $new_data = call_user_func($fetch_function);
            $new_hash = calculateDataHash($new_data);
            
            // Hash değeri aynıysa değişiklik yok, direkt önbellekten döndür
            if ($new_hash === (isset($cached_data['_hash']) ? $cached_data['_hash'] : '')) {
                return $cached_data['data'];
            }
            
            // Değişiklik varsa, sadece değişen kısımları güncelle
            $merged_data = mergeChangedData($cached_data['data'], $new_data);
            
            // Güncellenmiş veriyi önbelleğe kaydet
            setCachedData($cache_key, $merged_data, $new_hash);
            
            return $merged_data;
        }
    }
    
    // Önbellekte yoksa veya TTL geçmişse, yeni veriyi çek ve kaydet
    $new_data = call_user_func($fetch_function);
    $new_hash = calculateDataHash($new_data);
    setCachedData($cache_key, $new_data, $new_hash);
    
    return $new_data;
}

/**
 * Önbellek tamamen boş mu kontrol et
 *
 * @return bool Önbellek boş ise true
 */
function isCacheEmpty() {
    $files = glob(CACHE_DIR . '*.json');
    return count($files) === 0;
}

/**
 * Süresi geçmiş önbellek dosyalarını temizle
 * 
 * @param int $max_age Maksimum yaş (saniye)
 * @return int Temizlenen dosya sayısı
 */
function cleanupExpiredCache($max_age = 86400) { // Varsayılan 1 gün
    $count = 0;
    $files = glob(CACHE_DIR . '*.json');
    $now = time();
    
    foreach ($files as $file) {
        if (($now - filemtime($file)) > $max_age) {
            unlink($file);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Önbellek kullanım istatistiklerini al
 * 
 * @return array Önbellek istatistikleri
 */
function getCacheStats() {
    $files = glob(CACHE_DIR . '*.json');
    $total_size = 0;
    $oldest_file = time();
    $newest_file = 0;
    $file_count = count($files);
    
    foreach ($files as $file) {
        $total_size += filesize($file);
        $file_time = filemtime($file);
        $oldest_file = min($oldest_file, $file_time);
        $newest_file = max($newest_file, $file_time);
    }
    
    return [
        'file_count' => $file_count,
        'total_size' => $total_size,
        'total_size_mb' => round($total_size / 1048576, 2), // MB olarak
        'oldest_file' => $oldest_file > 0 ? date('Y-m-d H:i:s', $oldest_file) : null,
        'newest_file' => $newest_file > 0 ? date('Y-m-d H:i:s', $newest_file) : null,
    ];
}

/**
 * Belirli bir anahtarla başlayan tüm önbellek verilerini temizle
 * 
 * @param string $prefix Önbellek anahtarı öneki
 * @return int Temizlenen dosya sayısı
 */
function clearCacheByPrefix($prefix) {
    $count = 0;
    $files = glob(CACHE_DIR . '*.json');
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (isset($data['_cache_key']) && strpos($data['_cache_key'], $prefix) === 0) {
            unlink($file);
            $count++;
        }
    }
    
    return $count;
}

// Her saat başı otomatik olarak 1 günden eski önbellekleri temizle
if (mt_rand(1, 100) <= 5) { // %5 olasılıkla çalıştır (her istek için ağır yük oluşturmasın)
    cleanupExpiredCache();
}