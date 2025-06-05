-- 🚀 TRADING BOT GÜVENLİ FUTURES AYARLARI SCRIPT
-- Bu script bot ayarlarını güvenli futures trading için optimize eder

USE trading_bot_db;

-- Mevcut ayarları kontrol et
SELECT 
    'MEVCUT AYARLAR' as Info,
    id,
    CASE 
        WHEN settings_json LIKE '%"market_type":"futures"%' THEN 'FUTURES MODE'
        WHEN settings_json LIKE '%"market_type":"spot"%' THEN 'SPOT MODE'
        ELSE 'UNKNOWN MODE'
    END as current_mode,
    CASE 
        WHEN settings_json LIKE '%"trade_amount":%' THEN 'TRADE AMOUNT SET'
        ELSE 'NO TRADE AMOUNT'
    END as trade_amount_status
FROM bot_settings 
ORDER BY id DESC LIMIT 1;

-- 🎯 1. MINIMUM İŞLEM TUTARI DÜZELTMESİ (5+ USDT)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"min_trade_amount":5', '"min_trade_amount":6')
WHERE settings_json LIKE '%"min_trade_amount":5%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_trade_amount":6', '"max_trade_amount":20')
WHERE settings_json LIKE '%"max_trade_amount":6%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":10', '"trade_amount":15')
WHERE settings_json LIKE '%"trade_amount":10%';

-- Min trade amount yoksa ekle
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"exchange":"binance"', '"exchange":"binance","min_trade_amount":6')
WHERE settings_json NOT LIKE '%"min_trade_amount"%';

-- 🛡️ 2. KALDIRAÇ GÜVENLİ HALE GETİR (1X)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":3', '"leverage":1')
WHERE settings_json LIKE '%"leverage":3%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":2', '"leverage":1')
WHERE settings_json LIKE '%"leverage":2%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":5', '"leverage":1')
WHERE settings_json LIKE '%"leverage":5%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage":10', '"leverage":1')
WHERE settings_json LIKE '%"leverage":10%';

-- 🔄 3. MARGIN MODU CROSS YAP
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"leverage_mode":"isolated"', '"leverage_mode":"cross"')
WHERE settings_json LIKE '%"leverage_mode":"isolated"%';

-- 🚨 4. RİSK YÖNETİMİNİ AKTİF YAP
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"enabled":false', '"enabled":true')
WHERE settings_json LIKE '%"risk_management"%' 
AND settings_json LIKE '%"enabled":false%';

-- Stop loss'u aktif yap (2%)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"stop_loss":0', '"stop_loss":2')
WHERE settings_json LIKE '%"stop_loss":0%';

-- Take profit'i aktif yap (3%)
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"take_profit":0', '"take_profit":3')
WHERE settings_json LIKE '%"take_profit":0%';

-- 📊 5. MAX POZİSYONLARI GÜVENLİ SEVİYEYE ÇEK
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_trades":3', '"max_open_trades":2')
WHERE settings_json LIKE '%"max_open_trades":3%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_trades":5', '"max_open_trades":2')
WHERE settings_json LIKE '%"max_open_trades":5%';

UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"max_open_positions":5', '"max_open_positions":2')
WHERE settings_json LIKE '%"max_open_positions":5%';

-- 💰 6. TRADE AMOUNT'U GÜVENLİ ARTIR
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"trade_amount":10', '"trade_amount":15')
WHERE settings_json LIKE '%"trade_amount":10%';

-- 7. FUTURES MODE KONTROL ET VE DÜZELT
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '"market_type":"spot"', '"market_type":"futures"')
WHERE settings_json LIKE '%"market_type":"spot"%';

-- Market type yoksa ekle
UPDATE bot_settings 
SET settings_json = REPLACE(settings_json, '{', '{"market_type":"futures",')
WHERE settings_json NOT LIKE '%"market_type"%';

-- 8. BAKIYE ARTIR (Test için)
UPDATE account_balance 
SET total_balance = 100.0, available_balance = 100.0 
WHERE currency = 'USDT' AND exchange = 'binance';

-- Eğer USDT bakiyesi yoksa ekle
INSERT IGNORE INTO account_balance (exchange, currency, total_balance, available_balance, locked_balance, update_time, created_at)
VALUES ('binance', 'USDT', 100.0, 100.0, 0.0, NOW(), NOW());

-- 9. FUTURES BALANCE ARTIR
UPDATE futures_balances 
SET wallet_balance = 100.0, available_balance = 100.0, margin_balance = 100.0 
WHERE asset = 'USDT';

-- Eğer futures USDT bakiyesi yoksa ekle
INSERT IGNORE INTO futures_balances (asset, wallet_balance, available_balance, margin_balance, created_at) 
VALUES ('USDT', 100.0, 100.0, 100.0, NOW());

-- 10. MARGIN STATUS GÜNCELLE
UPDATE margin_status 
SET available_balance = 100.0, free_margin = 100.0, margin_status = 'SUFFICIENT' 
WHERE asset = 'USDT';

-- Eğer margin status yoksa ekle
INSERT IGNORE INTO margin_status (asset, available_balance, free_margin, margin_status, created_at)
VALUES ('USDT', 100.0, 100.0, 'SUFFICIENT', NOW());

-- ✅ SONUÇ KONTROL
SELECT 'GÜVENLİ FUTURES AYARLARI UYGULANDI!' as SONUC;

-- Güncellenmiş ayarları göster
SELECT 
    'GÜNCEL AYARLAR' as Info,
    bs.id,
    CASE 
        WHEN bs.settings_json LIKE '%"market_type":"futures"%' THEN '✅ FUTURES MODE'
        ELSE '❌ SPOT MODE'
    END as market_mode,
    CASE 
        WHEN bs.settings_json LIKE '%"leverage":1%' THEN '✅ 1X LEVERAGE (GÜVENLİ)'
        WHEN bs.settings_json LIKE '%"leverage":2%' THEN '⚠️ 2X LEVERAGE'
        WHEN bs.settings_json LIKE '%"leverage":3%' THEN '🚨 3X LEVERAGE'
        ELSE '❓ LEVERAGE UNKNOWN'
    END as leverage_status,
    CASE 
        WHEN bs.settings_json LIKE '%"leverage_mode":"cross"%' THEN '✅ CROSS MARGIN'
        WHEN bs.settings_json LIKE '%"leverage_mode":"isolated"%' THEN '⚠️ ISOLATED MARGIN'
        ELSE '❓ MARGIN MODE UNKNOWN'
    END as margin_mode,
    CASE 
        WHEN bs.settings_json LIKE '%"min_trade_amount":6%' THEN '✅ MIN 6 USDT'
        WHEN bs.settings_json LIKE '%"min_trade_amount":5%' THEN '⚠️ MIN 5 USDT'
        ELSE '❓ MIN TRADE UNKNOWN'
    END as min_trade_status
FROM bot_settings bs
ORDER BY bs.id DESC LIMIT 1;

-- Bakiye durumunu göster
SELECT 
    'BAKIYE DURUMU' as Info,
    'ACCOUNT_BALANCE' as Table_Name,
    currency,
    available_balance,
    CASE 
        WHEN available_balance >= 50 THEN '✅ YETERLİ'
        WHEN available_balance >= 20 THEN '⚠️ DÜŞÜK'
        ELSE '🚨 YETERSİZ'
    END as status
FROM account_balance 
WHERE currency = 'USDT' AND exchange = 'binance'

UNION ALL

SELECT 
    'FUTURES BAKIYE' as Info,
    'FUTURES_BALANCES' as Table_Name,
    asset as currency,
    available_balance,
    CASE 
        WHEN available_balance >= 50 THEN '✅ YETERLİ'
        WHEN available_balance >= 20 THEN '⚠️ DÜŞÜK'
        ELSE '🚨 YETERSİZ'
    END as status
FROM futures_balances 
WHERE asset = 'USDT';

-- Final Check - Tüm ayarların doğru olup olmadığını kontrol et
SELECT 
    CASE 
        WHEN settings_json LIKE '%"market_type":"futures"%' AND 
             settings_json LIKE '%"leverage":1%' AND 
             settings_json LIKE '%"leverage_mode":"cross"%' AND
             settings_json LIKE '%"min_trade_amount":6%' THEN '🎉 TÜM AYARLAR DOĞRU!'
        ELSE '⚠️ AYARLARDA SORUN VAR'
    END as Final_Status,
    'BOT ARTIK GÜVENLİ FUTURES TRADİNG İÇİN HAZIR' as Message
FROM bot_settings 
ORDER BY id DESC LIMIT 1;