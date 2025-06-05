-- VERİTABANI KARAKTER SETİ SORUNU ÇÖZÜMÜ - MARİADB UYUMLU
-- Bu script tüm tabloları UTF8MB4 karakter setine dönüştürür

USE trading_bot_db;

-- Veritabanı karakter setini güncelle
ALTER DATABASE trading_bot_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Ana tabloları düzelt
ALTER TABLE open_positions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE trade_history CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE active_coins CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE bot_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE discovered_coins CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE account_balance CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Belirli sütunları da açıkça düzelt
ALTER TABLE open_positions 
    MODIFY symbol VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY strategy VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY close_reason VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE trade_history 
    MODIFY symbol VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY strategy VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY close_reason VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- MariaDB için signal kelimesini backtick ile sarıyoruz
ALTER TABLE active_coins 
    MODIFY symbol VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY `signal` VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY added_by VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE bot_settings 
    MODIFY settings TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
    MODIFY settings_json TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Mevcut tabloların karakter setini kontrol et
SELECT 
    TABLE_NAME, 
    TABLE_COLLATION 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'trading_bot_db';

-- Sütun karakter setlerini kontrol et
SELECT 
    TABLE_NAME, 
    COLUMN_NAME, 
    CHARACTER_SET_NAME, 
    COLLATION_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'trading_bot_db' 
    AND CHARACTER_SET_NAME IS NOT NULL;

-- Başarı mesajı
SELECT 'VERİTABANI KARAKTER SETİ BAŞARIYLA DÜZELTİLDİ!' as SONUC;