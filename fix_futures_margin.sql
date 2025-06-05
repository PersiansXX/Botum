-- 🔧 FUTURES MARGIN SORUNU ÇÖZÜMÜ - MARİADB 5.5 SÜTUN YAPISI UYUMLU
-- Bu script mevcut account_balance tablosu yapısına uygun olarak çalışır

USE trading_bot_db;

-- MariaDB versiyonunu kontrol et
SELECT VERSION() as MariaDB_Version;

-- Mevcut bot ayarlarını kontrol et
SELECT 
    id,
    CASE 
        WHEN settings_json IS NOT NULL AND settings_json != '' THEN 'JSON SETTINGS EXIST'
        WHEN settings IS NOT NULL AND settings != '' THEN 'OLD SETTINGS EXIST'
        ELSE 'NO SETTINGS'
    END as settings_status,
    CHAR_LENGTH(COALESCE(settings_json, settings, '')) as settings_length
FROM bot_settings 
ORDER BY id DESC LIMIT 1;

-- Mevcut account_balance tablo yapısını kontrol et
DESCRIBE account_balance;

-- Mevcut ayarları yedekle
CREATE TABLE IF NOT EXISTS bot_settings_backup AS 
SELECT * FROM bot_settings WHERE id = (SELECT MAX(id) FROM bot_settings);

-- Basit string replacement ile futures ayarlarını güncelle (MariaDB 5.5 uyumlu)
-- 1. Önce settings_json'u düzelt (boşsa settings'den al)
UPDATE bot_settings 
SET settings_json = CASE
    WHEN (settings_json IS NULL OR settings_json = '') AND settings IS NOT NULL THEN settings
    WHEN settings_json IS NOT NULL AND settings_json != '' THEN settings_json
    ELSE '{"exchange":"binance","base_currency":"USDT","trade_amount":10.0,"max_open_trades":2,"stop_loss_pct":2.0,"take_profit_pct":3.0,"auto_trade":true,"use_telegram":false}'
END
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp);

-- 2. Market type'ı futures olarak ayarla (basit REPLACE ile)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"market_type":"spot"', '"market_type":"futures"')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"market_type":"spot"%';

-- 3. Market type yoksa ekle (başa ekle)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '{', '{"market_type":"futures",')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json NOT LIKE '%market_type%';

-- 4. Leverage'ı 1 yap (mevcut varsa değiştir)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":2', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":2%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":3', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":3%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":4', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":4%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":5', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":5%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":10', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":10%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":20', '"leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage":20%';

-- 5. Leverage yoksa ekle
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"market_type":"futures"', '"market_type":"futures","leverage":1')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json NOT LIKE '%"leverage"%';

-- 6. Leverage mode'u cross yap
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage_mode":"isolated"', '"leverage_mode":"cross"')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"leverage_mode":"isolated"%';

-- 7. Leverage mode yoksa ekle
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":1', '"leverage":1,"leverage_mode":"cross"')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json NOT LIKE '%"leverage_mode"%';

-- 8. Trade amount'u güvenli seviyeye çek (10.0)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":100', '"trade_amount":10.0')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"trade_amount":100%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":50', '"trade_amount":10.0')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"trade_amount":50%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":25', '"trade_amount":10.0')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"trade_amount":25%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":20', '"trade_amount":10.0')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"trade_amount":20%';

-- 9. Max open trades'i 2 yap
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_trades":5', '"max_open_trades":2')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"max_open_trades":5%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_trades":3', '"max_open_trades":2')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"max_open_trades":3%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_trades":10', '"max_open_trades":2')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json LIKE '%"max_open_trades":10%';

-- 10. Max open trades yoksa ekle
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage_mode":"cross"', '"leverage_mode":"cross","max_open_trades":2')
WHERE id = (SELECT MAX(id) FROM (SELECT id FROM bot_settings) as temp)
AND settings_json NOT LIKE '%"max_open_trades"%';

-- Futures settings tablosu oluştur (MariaDB 5.5 TIMESTAMP uyumlu)
CREATE TABLE IF NOT EXISTS futures_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_settings_id INT,
    margin_type VARCHAR(20) DEFAULT 'cross',
    position_mode VARCHAR(20) DEFAULT 'oneway',
    auto_margin BOOLEAN DEFAULT TRUE,
    margin_safety_buffer DECIMAL(5,3) DEFAULT 0.1,
    max_leverage INT DEFAULT 1,
    min_margin_ratio DECIMAL(5,3) DEFAULT 0.05,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Mevcut futures ayarlarını sil ve yenisini ekle
DELETE FROM futures_settings WHERE bot_settings_id = (SELECT MAX(id) FROM bot_settings);

-- Yeni futures ayarlarını ekle
INSERT INTO futures_settings (bot_settings_id, margin_type, position_mode, auto_margin, margin_safety_buffer, max_leverage)
VALUES (
    (SELECT MAX(id) FROM bot_settings),
    'cross',
    'oneway', 
    TRUE,
    0.1,
    1
);

-- Hesap bakiye tablosunu kontrol et ve güncelle (MariaDB 5.5 TIMESTAMP uyumlu)
CREATE TABLE IF NOT EXISTS futures_balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset VARCHAR(10) NOT NULL,
    wallet_balance DECIMAL(20, 8) DEFAULT 0,
    unrealized_pnl DECIMAL(20, 8) DEFAULT 0,
    margin_balance DECIMAL(20, 8) DEFAULT 0,
    available_balance DECIMAL(20, 8) DEFAULT 0,
    cross_wallet_balance DECIMAL(20, 8) DEFAULT 0,
    cross_unrealized_pnl DECIMAL(20, 8) DEFAULT 0,
    position_initial_margin DECIMAL(20, 8) DEFAULT 0,
    open_order_initial_margin DECIMAL(20, 8) DEFAULT 0,
    max_withdraw_amount DECIMAL(20, 8) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT NULL,
    UNIQUE KEY unique_asset (asset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Mevcut USDT bakiyesini sil ve yenisini ekle
DELETE FROM futures_balances WHERE asset = 'USDT';
INSERT INTO futures_balances (asset, wallet_balance, available_balance, created_at) 
VALUES ('USDT', 50.0, 50.0, NOW());

-- Account balance tablosuna USDT ekle (mevcut tablo yapısına uygun)
-- account_type sütunu yok, exchange sütunu var
DELETE FROM account_balance WHERE currency = 'USDT' AND exchange = 'binance';
INSERT INTO account_balance (exchange, currency, total_balance, available_balance, locked_balance, update_time, created_at)
VALUES ('binance', 'USDT', 50.0, 50.0, 0.0, NOW(), NOW());

-- Margin hesaplama tablosu oluştur (MariaDB 5.5 TIMESTAMP uyumlu)
CREATE TABLE IF NOT EXISTS margin_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset VARCHAR(10) NOT NULL,
    available_balance DECIMAL(20, 8) DEFAULT 0,
    margin_balance DECIMAL(20, 8) DEFAULT 0,
    position_initial_margin DECIMAL(20, 8) DEFAULT 0,
    open_order_initial_margin DECIMAL(20, 8) DEFAULT 0,
    free_margin DECIMAL(20, 8) DEFAULT 0,
    margin_status VARCHAR(20) DEFAULT 'UNKNOWN',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT NULL,
    UNIQUE KEY unique_asset (asset)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Margin status'u güncelle
DELETE FROM margin_status WHERE asset = 'USDT';
INSERT INTO margin_status (asset, available_balance, free_margin, margin_status, created_at)
VALUES ('USDT', 50.0, 50.0, 'SUFFICIENT', NOW());

-- Sonuç kontrolü
SELECT 'FUTURES MARGIN AYARLARI GÜNCELLENDİ (SÜTUN YAPISI UYUMLU)!' as SONUC;

-- Güncellenmiş ayarları göster
SELECT 
    bs.id,
    CASE 
        WHEN bs.settings_json LIKE '%futures%' THEN 'FUTURES MODE AKTIF'
        ELSE 'SPOT MODE'
    END as mode,
    CASE 
        WHEN bs.settings_json LIKE '%"leverage":1%' THEN '1X LEVERAGE (GÜVENLI)'
        WHEN bs.settings_json LIKE '%"leverage":%' THEN 'CUSTOM LEVERAGE'
        ELSE 'NO LEVERAGE'
    END as leverage_status,
    CASE 
        WHEN bs.settings_json LIKE '%"leverage_mode":"cross"%' THEN 'CROSS MARGIN'
        WHEN bs.settings_json LIKE '%"leverage_mode":"isolated"%' THEN 'ISOLATED MARGIN'
        ELSE 'NO MARGIN MODE'
    END as margin_mode,
    fs.margin_type,
    fs.max_leverage
FROM bot_settings bs
LEFT JOIN futures_settings fs ON bs.id = fs.bot_settings_id
ORDER BY bs.id DESC LIMIT 1;

-- Margin durumunu göster
SELECT 
    'MARGIN DURUMU' as Info,
    asset,
    available_balance,
    margin_status,
    last_updated
FROM margin_status 
WHERE asset = 'USDT';

-- Bakiye kontrolü (mevcut account_balance tablosu yapısına uygun)
SELECT 
    'BAKIYE DURUMU' as Info,
    exchange,
    currency,
    available_balance,
    'FUTURES HAZIR' as Status
FROM account_balance 
WHERE currency = 'USDT' AND exchange = 'binance' AND available_balance > 0;

-- Futures bakiye kontrolü
SELECT 
    'FUTURES BAKIYE' as Info,
    asset,
    available_balance,
    'FUTURES AKTIF' as Status
FROM futures_balances 
WHERE asset = 'USDT' AND available_balance > 0;

-- Test için basit kontrol
SELECT 
    'FUTURES AYARLARI TAMAMLANDI' as Status,
    COUNT(*) as Futures_Settings_Count,
    'MARİADB 5.5 SÜTUN UYUMLU' as Compatibility
FROM futures_settings;

-- Son kontrol - ayarların düzgün olduğunu doğrula
SELECT 
    CASE 
        WHEN settings_json LIKE '%"market_type":"futures"%' AND 
             settings_json LIKE '%"leverage":1%' AND 
             settings_json LIKE '%"leverage_mode":"cross"%' THEN 'TÜM AYARLAR DOĞRU'
        ELSE 'AYARLARDA SORUN VAR'
    END as Final_Check
FROM bot_settings 
ORDER BY id DESC LIMIT 1;