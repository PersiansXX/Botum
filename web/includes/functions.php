<?php

class SystemStatus {
    private $db;
    private $botPath;
    
    public function __construct($db) {
        $this->db = $db;
        $this->botPath = dirname(dirname(dirname(__FILE__))) . '/bot';
    }
    
    public function checkBotStatus() {
        try {
            $pidFile = $this->botPath . '/bot.pid';
            if (file_exists($pidFile)) {
                $pid = trim(file_get_contents($pidFile));
                // Linux ve Windows için process kontrol
                if ($this->isProcessRunning($pid)) {
                    $lastLog = $this->getLastBotLog();
                    return [
                        'status' => 'success',
                        'running' => true,
                        'pid' => $pid,
                        'last_activity' => $lastLog['message'],
                        'last_activity_time' => $lastLog['time'],
                        'uptime' => $this->getBotUptime($pid)
                    ];
                }
            }
            return ['status' => 'error', 'running' => false, 'message' => 'Bot çalışmıyor'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function isProcessRunning($pid) {
        if (PHP_OS === 'Linux') {
            return file_exists("/proc/$pid");
        } else {
            // Windows için tasklist komutu
            exec("tasklist /FI \"PID eq $pid\" 2>&1", $output);
            return count($output) > 1;
        }
    }

    private function getLastBotLog() {
        $logFile = $this->botPath . '/bot.log';
        if (file_exists($logFile)) {
            $lastLine = trim(exec("tail -n 1 " . escapeshellarg($logFile)));
            // Log satırını parse et
            if (preg_match('/\[(.*?)\](.*)/', $lastLine, $matches)) {
                return [
                    'time' => $matches[1],
                    'message' => trim($matches[2])
                ];
            }
        }
        return ['time' => '', 'message' => 'Log bulunamadı'];
    }

    private function getBotUptime($pid) {
        if (PHP_OS === 'Linux') {
            $cmd = "ps -p $pid -o etime=";
        } else {
            // Windows için process başlangıç zamanı
            $cmd = "wmic process where ProcessId=$pid get CreationDate";
        }
        exec($cmd, $output);
        if (!empty($output)) {
            if (PHP_OS === 'Linux') {
                return trim($output[0]);
            } else {
                // Windows timestamp'ini parse et
                $date = substr($output[1], 0, 14);
                $start = DateTime::createFromFormat('YmdHis', $date);
                if ($start) {
                    $now = new DateTime();
                    $diff = $start->diff($now);
                    return $diff->format('%a gün %h saat %i dakika');
                }
            }
        }
        return 'N/A';
    }

    public function getSystemResources() {
        try {
            if (PHP_OS === 'Linux') {
                // Linux sistem bilgileri
                $cpu = sys_getloadavg();
                $free = shell_exec('free');
                $free = trim($free);
                $free_arr = explode("\n", $free);
                $mem = explode(" ", $free_arr[1]);
                $mem = array_filter($mem);
                $mem = array_merge($mem);
                $memory_usage = $mem[2]/$mem[1]*100;
            } else {
                // Windows sistem bilgileri
                $cmd = "wmic cpu get loadpercentage";
                exec($cmd, $output);
                $cpu = isset($output[1]) ? [(int)trim($output[1])/100] : [0];

                $cmd = "wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value";
                exec($cmd, $output);
                $memory = [];
                foreach ($output as $line) {
                    if (preg_match('/(\w+)=(\d+)/', $line, $matches)) {
                        $memory[$matches[1]] = $matches[2];
                    }
                }
                $memory_usage = isset($memory['TotalVisibleMemorySize']) && $memory['TotalVisibleMemorySize'] > 0 
                    ? (($memory['TotalVisibleMemorySize'] - $memory['FreePhysicalMemory']) / $memory['TotalVisibleMemorySize']) * 100
                    : 0;
            }

            $disk_total = disk_total_space('/');
            $disk_free = disk_free_space('/');
            $disk_used = $disk_total - $disk_free;
            $disk_percent = ($disk_used / $disk_total) * 100;

            return [
                'status' => 'success',
                'cpu_usage' => $cpu[0] * 100,
                'memory_usage' => $memory_usage,
                'disk_usage' => $disk_percent,
                'disk_total' => $this->formatBytes($disk_total),
                'disk_free' => $this->formatBytes($disk_free),
                'load_average' => $cpu
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }

    public function checkDatabaseStatus() {
        try {
            $start = microtime(true);
            
            // Basit sorgu testi
            $result = $this->db->query("SELECT 1");
            $end = microtime(true);
            $response_time = ($end - $start) * 1000;

            // Tablo durumlarını kontrol et
            $tables = $this->getTableStatus();
            
            // Aktif bağlantıları kontrol et
            $connections = $this->db->query("SHOW STATUS WHERE `variable_name` = 'Threads_connected'")->fetch_assoc();
            
            return [
                'status' => 'success',
                'connected' => true,
                'response_time' => round($response_time, 2),
                'tables' => $tables,
                'active_connections' => $connections['Value']
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getTableStatus() {
        $tables = [];
        $result = $this->db->query("SHOW TABLE STATUS");
        while ($row = $result->fetch_assoc()) {
            $tables[] = [
                'name' => $row['Name'],
                'rows' => $row['Rows'],
                'size' => $this->formatBytes($row['Data_length'] + $row['Index_length']),
                'last_update' => $row['Update_time']
            ];
        }
        return $tables;
    }

    public function getBotMetrics() {
        try {
            // Son 24 saatteki işlemleri al
            $query = "SELECT 
                COUNT(*) as total_trades,
                SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as profitable_trades,
                AVG(CASE WHEN profit_loss IS NOT NULL THEN profit_loss ELSE 0 END) as avg_profit,
                SUM(CASE WHEN profit_loss IS NOT NULL THEN profit_loss ELSE 0 END) as total_profit
            FROM trade_history 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            
            $result = $this->db->query($query);
            if ($result === false) {
                throw new Exception("Veritabanı sorgusu başarısız: " . $this->db->error);
            }
            
            $stats = $result->fetch_assoc();
            if (!$stats) {
                return [
                    'status' => 'success',
                    'trade_stats' => [
                        'total_trades' => 0,
                        'profitable_trades' => 0,
                        'success_rate' => 0,
                        'avg_profit' => 0,
                        'total_profit' => 0
                    ],
                    'active_strategies' => 0,
                    'risk_parameters' => []
                ];
            }
            
            // Aktif stratejileri say
            $strategyDir = $this->botPath . '/strategies';
            $activeStrategies = 0;
            if (is_dir($strategyDir)) {
                $activeStrategies = count(glob($strategyDir . '/*.py'));
            }
            
            // Risk parametrelerini oku
            $riskParams = $this->getRiskParameters();
            
            $successRate = $stats['total_trades'] > 0 
                ? ($stats['profitable_trades'] / $stats['total_trades']) * 100 
                : 0;
            
            return [
                'status' => 'success',
                'trade_stats' => [
                    'total_trades' => (int)$stats['total_trades'],
                    'profitable_trades' => (int)$stats['profitable_trades'],
                    'success_rate' => round($successRate, 2),
                    'avg_profit' => round($stats['avg_profit'] ?? 0, 2),
                    'total_profit' => round($stats['total_profit'] ?? 0, 2)
                ],
                'active_strategies' => $activeStrategies,
                'risk_parameters' => $riskParams
            ];
        } catch (Exception $e) {
            error_log("Bot metrics error: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getRiskParameters() {
        try {
            // Risk manager yapılandırmasını oku
            $configFile = $this->botPath . '/config/config.json';
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                return isset($config['risk_settings']) ? $config['risk_settings'] : [];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    public function checkApiStatus() {
        try {
            $start = microtime(true);
            
            // API ayarlarını veritabanından al
            $query = "SELECT settings_json FROM bot_settings ORDER BY id DESC LIMIT 1";
            $result = $this->db->query($query);
            $settings = json_decode($result->fetch_assoc()['settings_json'], true);
            
            if (empty($settings['api_credentials']['api_key']) || empty($settings['api_credentials']['api_secret'])) {
                return ['status' => 'error', 'message' => 'API anahtarları bulunamadı'];
            }
            
            // Binance API testi
            $apiKey = $settings['api_credentials']['api_key'];
            $timestamp = time() * 1000;
            $params = "timestamp=" . $timestamp;
            $signature = hash_hmac('sha256', $params, $settings['api_credentials']['api_secret']);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.binance.com/api/v3/account?" . $params . "&signature=" . $signature,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["X-MBX-APIKEY: " . $apiKey],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $end = microtime(true);
            $response_time = ($end - $start) * 1000;
            
            if ($httpCode === 200) {
                return [
                    'status' => 'success',
                    'connected' => true,
                    'response_time' => round($response_time, 2),
                    'rate_limits' => $this->checkRateLimits()
                ];
            } else {
                return [
                    'status' => 'error',
                    'connected' => false,
                    'message' => 'API yanıt kodu: ' . $httpCode
                ];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function checkRateLimits() {
        try {
            $ch = curl_init("https://api.binance.com/api/v3/exchangeInfo");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $info = json_decode($response, true);
            $limits = [];
            
            if (isset($info['rateLimits'])) {
                foreach ($info['rateLimits'] as $limit) {
                    $limits[$limit['rateLimitType']] = [
                        'interval' => $limit['interval'],
                        'limit' => $limit['limit']
                    ];
                }
            }
            
            return $limits;
        } catch (Exception $e) {
            return [];
        }
    }

    public function checkWebSocketStatus() {
        try {
            $logFile = $this->botPath . '/websocket.log';
            if (!file_exists($logFile)) {
                return [
                    'status' => 'warning',
                    'connected' => false,
                    'message' => 'WebSocket log dosyası bulunamadı'
                ];
            }
            
            // Son 5 satırı oku
            $lines = array_slice(file($logFile), -5);
            $connected = false;
            $lastMessage = null;
            $subscribedPairs = [];
            
            foreach ($lines as $line) {
                if (strpos($line, 'Connected') !== false) {
                    $connected = true;
                }
                if (strpos($line, 'Message received') !== false) {
                    $lastMessage = trim($line);
                }
                if (preg_match('/Subscribed to ([A-Z]+)/', $line, $matches)) {
                    $subscribedPairs[] = $matches[1];
                }
            }
            
            return [
                'status' => $connected ? 'success' : 'warning',
                'connected' => $connected,
                'last_message' => $lastMessage,
                'subscribed_pairs' => array_unique($subscribedPairs)
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}