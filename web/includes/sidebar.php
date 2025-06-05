<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Menü</h5>
        <span class="badge badge-light">
            <i class="fas fa-code-branch"></i> v1.0.2
        </span>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <a href="index.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Ana Sayfa
            </a>
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="coins.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'coins.php' ? 'active' : ''; ?>">
                <i class="fas fa-coins"></i> Coinler
            </a>
            <a href="discovered_coins.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'discovered_coins.php' ? 'active' : ''; ?>">
                <i class="fas fa-search-dollar"></i> Keşfedilen Coinler
            </a>
            <a href="open_positions.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'open_positions.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-area"></i> Açık Pozisyonlar
            </a>
            <a href="trades.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'trades.php' ? 'active' : ''; ?>">
                <i class="fas fa-exchange-alt"></i> İşlemler
            </a>
            <a href="reports.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Raporlar
            </a>
            <a href="settings.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Ayarlar
            </a>
            <a href="bot_status.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'bot_status.php' ? 'active' : ''; ?>">
                <i class="fas fa-heartbeat"></i> Bot Durum
            </a>
        </div>
    </div>
    
    <!-- Bot Durum Göstergesi -->
    <?php
    // Bot API'si üzerinden durum kontrolü yap
    if (!class_exists('BotAPI')) {
        require_once __DIR__ . '/../api/bot_api.php';
    }
    $bot_api = new BotAPI();
    $status = $bot_api->getStatus();
    
    // Bot durumunu getStatus() fonksiyonundan al
    $running = $status['running'] ?? false;
    ?>
    
    <div class="card mt-3 border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-robot"></i> Bot Durumu</h5>
        </div>
        <div class="card-body p-3 text-center">
            <div class="bot-status-indicator <?php echo $running ? 'running' : 'stopped'; ?>">
                <i class="fas <?php echo $running ? 'fa-play' : 'fa-stop'; ?>"></i>
            </div>
            <h5 class="mt-2 <?php echo $running ? 'text-success' : 'text-danger'; ?>">
                <?php echo $running ? 'Aktif' : 'Pasif'; ?>
            </h5>
            <a href="bot_status.php" class="btn btn-sm btn-outline-secondary mt-2">
                <i class="fas fa-cog"></i> Detaylar
            </a>
        </div>
    </div>
    
    <style>
        .bot-status-indicator {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .bot-status-indicator.running {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
            animation: pulse 2s infinite;
        }
        
        .bot-status-indicator.stopped {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
    </style>
</div>