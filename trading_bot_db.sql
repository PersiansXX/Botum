-- phpMyAdmin SQL Dump
-- version 4.4.15.10
-- https://www.phpmyadmin.net
--
-- Anamakine: localhost
-- Üretim Zamanı: 01 Haz 2025, 13:27:13
-- Sunucu sürümü: 5.5.68-MariaDB
-- PHP Sürümü: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `trading_bot_db`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `account_balance`
--

CREATE TABLE IF NOT EXISTS `account_balance` (
  `id` int(11) NOT NULL,
  `exchange` varchar(50) NOT NULL,
  `currency` varchar(20) NOT NULL,
  `total_balance` decimal(20,8) DEFAULT '0.00000000',
  `available_balance` decimal(20,8) DEFAULT '0.00000000',
  `locked_balance` decimal(20,8) DEFAULT '0.00000000',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `account_balance`
--

INSERT INTO `account_balance` (`id`, `exchange`, `currency`, `total_balance`, `available_balance`, `locked_balance`, `update_time`, `created_at`) VALUES
(1, '', 'USDT', '0.15056955', '0.15056955', '0.00000000', '2025-05-06 22:19:11', NULL),
(2, '', 'USDT', '0.15056955', '0.15056955', '0.00000000', '2025-05-06 22:19:12', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `active_coins`
--

CREATE TABLE IF NOT EXISTS `active_coins` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `added_by` varchar(50) DEFAULT 'system',
  `added_at` datetime DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `price` decimal(20,8) DEFAULT '0.00000000',
  `signal` varchar(10) DEFAULT 'NEUTRAL',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1543 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `active_coins`
--

INSERT INTO `active_coins` (`id`, `symbol`, `added_by`, `added_at`, `last_updated`, `price`, `signal`, `is_active`, `created_at`) VALUES
(1352, 'SOLV/USDT', 'bot_update', NULL, '2025-06-01 13:07:12', '0.04476000', 'BUY', 1, '2025-05-31 14:10:05'),
(1353, 'AMP/USDT', 'bot_discovery', NULL, '2025-05-31 14:10:11', '0.00426800', 'BUY', 1, '2025-05-31 14:10:11'),
(1355, 'KAVA/USDT', 'bot_discovery', NULL, '2025-05-31 14:12:15', '0.41970000', 'BUY', 1, '2025-05-31 14:12:15'),
(1356, 'BIFI/USDT', 'bot_update', NULL, '2025-05-31 15:03:02', '202.60000000', 'BUY', 1, '2025-05-31 14:15:56'),
(1357, 'NEXO/USDT', 'bot_discovery', NULL, '2025-05-31 14:18:05', '1.23200000', 'BUY', 1, '2025-05-31 14:18:05'),
(1359, 'COOKIE/USDT', 'bot_update', NULL, '2025-05-31 15:03:13', '0.23110000', 'BUY', 1, '2025-05-31 14:33:42'),
(1360, 'DGB/USDT', 'bot_discovery', NULL, '2025-05-31 14:39:33', '0.00926000', 'BUY', 1, '2025-05-31 14:39:33'),
(1361, 'QNT/USDT', 'bot_update', NULL, '2025-06-01 12:50:09', '110.20000000', 'BUY', 1, '2025-05-31 14:49:20'),
(1362, 'AWE/USDT', 'bot_update', NULL, '2025-05-31 15:19:25', '0.05923000', 'BUY', 1, '2025-05-31 14:49:26'),
(1363, 'PENDLE/USDT', 'bot_update', NULL, '2025-05-31 15:03:07', '4.12400000', 'BUY', 1, '2025-05-31 14:59:15'),
(1364, 'BANANAS31/USDT', 'bot_update', NULL, '2025-06-01 04:15:57', '0.00631900', 'BUY', 1, '2025-05-31 15:03:22'),
(1365, 'BCH/USDT', 'bot_update', NULL, '2025-05-31 16:04:59', '417.70000000', 'BUY', 1, '2025-05-31 15:07:05'),
(1366, 'TAO/USDT', 'bot_update', NULL, '2025-06-01 13:09:24', '413.47000000', 'BUY', 1, '2025-05-31 15:09:05'),
(1367, 'EOS/USDT', 'bot_discovery', NULL, '2025-05-31 15:09:15', '0.00000000', 'BUY', 1, '2025-05-31 15:09:15'),
(1368, 'ICX/USDT', 'bot_update', NULL, '2025-05-31 15:11:31', '0.12100000', 'BUY', 1, '2025-05-31 15:09:21'),
(1369, 'AAVE/USDT', 'bot_discovery', NULL, '2025-05-31 15:13:27', '249.22000000', 'BUY', 1, '2025-05-31 15:13:27'),
(1370, 'PHA/USDT', 'bot_discovery', NULL, '2025-05-31 15:13:33', '0.14560000', 'BUY', 1, '2025-05-31 15:13:33'),
(1371, 'VIRTUAL/USDT', 'bot_discovery', NULL, '2025-05-31 16:04:55', '2.05880000', 'BUY', 1, '2025-05-31 16:04:55'),
(1372, 'DEXE/USDT', 'bot_update', NULL, '2025-06-01 13:09:51', '14.29700000', 'BUY', 1, '2025-05-31 16:10:32'),
(1373, 'XRP/USDT', 'bot_update', NULL, '2025-06-01 12:00:27', '2.13950000', 'BUY', 1, '2025-05-31 16:38:45'),
(1374, 'HBAR/USDT', 'bot_update', NULL, '2025-06-01 12:26:14', '0.16555000', 'BUY', 1, '2025-05-31 16:39:11'),
(1375, 'SPELL/USDT', 'bot_update', NULL, '2025-06-01 08:01:23', '0.00052200', 'BUY', 1, '2025-05-31 16:39:19'),
(1376, 'DOT/USDT', 'bot_update', NULL, '2025-06-01 11:55:24', '4.02100000', 'BUY', 1, '2025-05-31 16:41:29'),
(1377, 'ALGO/USDT', 'bot_update', NULL, '2025-06-01 11:58:43', '0.19190000', 'BUY', 1, '2025-05-31 16:41:37'),
(1378, 'YFI/USDT', 'bot_update', NULL, '2025-05-31 21:41:51', '5222.00000000', 'BUY', 1, '2025-05-31 16:41:48'),
(1379, 'GPS/USDT', 'bot_update', NULL, '2025-05-31 22:31:09', '0.02250000', 'BUY', 1, '2025-05-31 16:41:53'),
(1380, 'XVG/USDT', 'bot_update', NULL, '2025-06-01 12:09:47', '0.00662170', 'BUY', 1, '2025-05-31 16:43:39'),
(1381, 'TRUMP/USDT', 'bot_update', NULL, '2025-06-01 12:19:54', '11.19900000', 'BUY', 1, '2025-05-31 16:47:12'),
(1382, 'RENDER/USDT', 'bot_update', NULL, '2025-06-01 11:55:35', '3.83200000', 'BUY', 1, '2025-05-31 16:47:23'),
(1383, 'LINK/USDT', 'bot_update', NULL, '2025-06-01 11:57:47', '13.79300000', 'BUY', 1, '2025-05-31 16:51:37'),
(1384, 'PNUT/USDT', 'bot_update', NULL, '2025-06-01 12:00:54', '0.25741000', 'BUY', 1, '2025-05-31 16:53:58'),
(1385, 'POL/USDT', 'bot_update', NULL, '2025-06-01 09:11:53', '0.21177000', 'BUY', 1, '2025-05-31 16:54:09'),
(1386, 'BEAMX/USDT', 'bot_update', NULL, '2025-05-31 16:58:57', '0.00643900', 'BUY', 1, '2025-05-31 16:54:17'),
(1387, 'AIXBT/USDT', 'bot_update', NULL, '2025-06-01 13:21:24', '0.19188000', 'BUY', 1, '2025-05-31 16:56:21'),
(1388, 'MKR/USDT', 'bot_update', NULL, '2025-06-01 06:49:23', '1572.80000000', 'BUY', 1, '2025-05-31 16:56:26'),
(1389, 'CGPT/USDT', 'bot_update', NULL, '2025-05-31 22:18:59', '0.11665000', 'BUY', 1, '2025-05-31 16:56:34'),
(1390, 'HOT/USDT', 'bot_update', NULL, '2025-05-31 17:24:08', '0.00098000', 'BUY', 1, '2025-05-31 16:56:41'),
(1391, 'VET/USDT', 'bot_update', NULL, '2025-06-01 07:43:01', '0.02406400', 'BUY', 1, '2025-05-31 16:58:52'),
(1392, 'NEIRO/USDT', 'bot_update', NULL, '2025-06-01 11:45:40', '0.00043800', 'BUY', 1, '2025-05-31 17:03:10'),
(1393, 'THETA/USDT', 'bot_update', NULL, '2025-06-01 11:02:42', '0.74140000', 'BUY', 1, '2025-05-31 17:05:29'),
(1394, 'LTC/USDT', 'bot_update', NULL, '2025-06-01 11:45:57', '86.93000000', 'BUY', 1, '2025-05-31 17:07:32'),
(1395, 'COMP/USDT', 'bot_discovery', NULL, '2025-05-31 17:11:33', '40.56000000', 'BUY', 1, '2025-05-31 17:11:33'),
(1396, 'VTHO/USDT', 'bot_update', NULL, '2025-05-31 21:32:12', '0.00215900', 'BUY', 1, '2025-05-31 17:15:37'),
(1397, 'VANA/USDT', 'bot_update', NULL, '2025-06-01 13:26:50', '6.52900000', 'BUY', 1, '2025-05-31 17:17:29'),
(1398, 'BSV/USDT', 'bot_discovery', NULL, '2025-05-31 17:17:40', '33.58000000', 'BUY', 1, '2025-05-31 17:17:40'),
(1399, 'JTO/USDT', 'bot_update', NULL, '2025-06-01 05:09:38', '1.65860000', 'BUY', 1, '2025-05-31 17:19:43'),
(1400, 'ONDO/USDT', 'bot_update', NULL, '2025-06-01 11:58:17', '0.81760000', 'BUY', 1, '2025-05-31 17:21:42'),
(1401, 'TNSR/USDT', 'bot_update', NULL, '2025-06-01 13:07:27', '0.12820000', 'BUY', 1, '2025-05-31 17:24:03'),
(1402, 'GRT/USDT', 'bot_update', NULL, '2025-06-01 10:13:51', '0.09356000', 'BUY', 1, '2025-05-31 17:26:05'),
(1403, 'CHR/USDT', 'bot_discovery', NULL, '2025-05-31 17:26:18', '0.08450000', 'BUY', 1, '2025-05-31 17:26:18'),
(1404, 'PERP/USDT', 'bot_update', NULL, '2025-06-01 13:16:53', '0.24120000', 'BUY', 1, '2025-05-31 17:33:24'),
(1405, 'ZEC/USDT', 'bot_update', NULL, '2025-06-01 12:04:17', '51.99000000', 'BUY', 1, '2025-05-31 17:39:59'),
(1406, 'PARTI/USDT', 'bot_update', NULL, '2025-06-01 12:57:26', '0.22950000', 'BUY', 1, '2025-05-31 18:12:05'),
(1407, 'FORM/USDT', 'bot_update', NULL, '2025-05-31 21:26:03', '2.87740000', 'BUY', 1, '2025-05-31 18:40:15'),
(1408, 'TST/USDT', 'bot_update', NULL, '2025-06-01 12:17:26', '0.04510000', 'BUY', 1, '2025-05-31 18:43:37'),
(1409, 'ACT/USDT', 'bot_update', NULL, '2025-06-01 10:41:21', '0.05114000', 'BUY', 1, '2025-05-31 18:47:12'),
(1410, 'BANANA/USDT', 'bot_update', NULL, '2025-05-31 21:21:47', '22.16400000', 'BUY', 1, '2025-05-31 19:24:32'),
(1411, 'BTC/USDT', 'bot_update', NULL, '2025-06-01 12:19:46', '104054.70000000', 'BUY', 1, '2025-05-31 19:43:03'),
(1412, 'JOE/USDT', 'bot_update', NULL, '2025-05-31 21:40:00', '0.16425000', 'BUY', 1, '2025-05-31 19:49:16'),
(1413, 'PYTH/USDT', 'bot_update', NULL, '2025-06-01 08:28:43', '0.11755000', 'BUY', 1, '2025-05-31 20:18:44'),
(1414, 'KAITO/USDT', 'bot_update', NULL, '2025-06-01 12:11:54', '1.90390000', 'BUY', 1, '2025-05-31 20:26:29'),
(1415, 'FIL/USDT', 'bot_update', NULL, '2025-06-01 12:00:59', '2.53700000', 'BUY', 1, '2025-05-31 20:51:32'),
(1416, '1000SATS/USDT', 'bot_update', NULL, '2025-06-01 13:18:54', '0.00004530', 'BUY', 1, '2025-05-31 20:55:37'),
(1417, 'EDU/USDT', 'bot_update', NULL, '2025-05-31 21:55:32', '0.13490000', 'BUY', 1, '2025-05-31 20:59:52'),
(1418, 'PROM/USDT', 'bot_discovery', NULL, '2025-05-31 20:59:57', '5.46300000', 'BUY', 1, '2025-05-31 20:59:57'),
(1419, 'ACH/USDT', 'bot_update', NULL, '2025-06-01 10:16:42', '0.02130800', 'BUY', 1, '2025-05-31 21:02:03'),
(1420, 'BEL/USDT', 'bot_discovery', NULL, '2025-05-31 21:04:02', '0.28220000', 'BUY', 1, '2025-05-31 21:04:02'),
(1421, 'FORTH/USDT', 'bot_discovery', NULL, '2025-05-31 21:06:14', '2.34000000', 'BUY', 1, '2025-05-31 21:06:14'),
(1422, 'AXL/USDT', 'bot_discovery', NULL, '2025-05-31 21:26:11', '0.32010000', 'BUY', 1, '2025-05-31 21:26:11'),
(1423, 'DEGO/USDT', 'bot_update', NULL, '2025-05-31 22:58:04', '2.64390000', 'BUY', 1, '2025-05-31 21:32:03'),
(1424, 'TUT/USDT', 'bot_update', NULL, '2025-06-01 12:34:19', '0.02682000', 'BUY', 1, '2025-05-31 21:32:07'),
(1425, 'FIDA/USDT', 'bot_update', NULL, '2025-06-01 10:19:12', '0.06864000', 'BUY', 1, '2025-05-31 21:44:04'),
(1426, 'CHZ/USDT', 'bot_discovery', NULL, '2025-05-31 21:50:57', '0.03942000', 'BUY', 1, '2025-05-31 21:50:57'),
(1427, 'ATOM/USDT', 'bot_update', NULL, '2025-06-01 12:26:49', '4.28400000', 'BUY', 1, '2025-05-31 21:55:28'),
(1428, 'BNT/USDT', 'bot_discovery', NULL, '2025-05-31 22:05:48', '0.64438000', 'BUY', 1, '2025-05-31 22:05:48'),
(1429, 'ZEN/USDT', 'bot_update', NULL, '2025-06-01 12:00:42', '10.09400000', 'BUY', 1, '2025-05-31 22:09:47'),
(1430, 'REI/USDT', 'bot_update', NULL, '2025-06-01 01:30:30', '0.01683000', 'BUY', 1, '2025-05-31 22:25:42'),
(1431, 'LQTY/USDT', 'bot_update', NULL, '2025-06-01 13:18:59', '0.81690000', 'BUY', 1, '2025-05-31 22:28:48'),
(1432, 'ORDI/USDT', 'bot_update', NULL, '2025-06-01 12:57:15', '8.38500000', 'BUY', 1, '2025-05-31 22:33:21'),
(1433, 'CETUS/USDT', 'bot_update', NULL, '2025-06-01 12:23:45', '0.13429000', 'BUY', 1, '2025-05-31 22:41:26'),
(1434, 'BROCCOLI714/USDT', 'bot_update', NULL, '2025-06-01 09:14:47', '0.02608000', 'BUY', 1, '2025-05-31 22:44:33'),
(1436, 'EIGEN/USDT', 'bot_update', NULL, '2025-06-01 12:17:44', '1.31640000', 'BUY', 1, '2025-05-31 22:54:42'),
(1437, 'MEME/USDT', 'bot_update', NULL, '2025-06-01 12:57:31', '0.00187100', 'BUY', 1, '2025-05-31 22:55:05'),
(1438, 'AVAX/USDT', 'bot_update', NULL, '2025-06-01 12:03:28', '20.36100000', 'BUY', 1, '2025-05-31 23:00:08'),
(1439, 'ARPA/USDT', 'bot_update', NULL, '2025-06-01 01:16:41', '0.02212000', 'BUY', 1, '2025-05-31 23:00:37'),
(1440, 'ZRX/USDT', 'bot_update', NULL, '2025-06-01 10:57:20', '0.23070000', 'BUY', 1, '2025-05-31 23:08:44'),
(1441, 'WLD/USDT', 'bot_update', NULL, '2025-06-01 04:26:34', '1.12630000', 'BUY', 1, '2025-05-31 23:10:41'),
(1442, 'LDO/USDT', 'bot_update', NULL, '2025-06-01 11:42:24', '0.85380000', 'BUY', 1, '2025-05-31 23:10:53'),
(1443, 'TURBO/USDT', 'bot_update', NULL, '2025-06-01 12:26:29', '0.00420250', 'BUY', 1, '2025-05-31 23:11:01'),
(1444, 'BABY/USDT', 'bot_update', NULL, '2025-06-01 11:17:01', '0.06491000', 'BUY', 1, '2025-05-31 23:11:14'),
(1445, 'STRK/USDT', 'bot_update', NULL, '2025-06-01 10:03:04', '0.13160000', 'BUY', 1, '2025-05-31 23:11:23'),
(1446, 'BERA/USDT', 'bot_update', NULL, '2025-06-01 11:00:00', '2.26400000', 'BUY', 1, '2025-05-31 23:14:03'),
(1447, 'ORCA/USDT', 'bot_update', NULL, '2025-06-01 06:28:01', '2.78000000', 'BUY', 1, '2025-05-31 23:14:08'),
(1448, 'ADA/USDT', 'bot_update', NULL, '2025-06-01 11:54:52', '0.66330000', 'BUY', 1, '2025-05-31 23:16:15'),
(1449, 'ENA/USDT', 'bot_update', NULL, '2025-06-01 11:52:03', '0.30690000', 'BUY', 1, '2025-05-31 23:16:19'),
(1450, 'PEOPLE/USDT', 'bot_update', NULL, '2025-06-01 12:20:26', '0.02011000', 'BUY', 1, '2025-05-31 23:16:45'),
(1451, 'SEI/USDT', 'bot_update', NULL, '2025-06-01 11:33:49', '0.19040000', 'BUY', 1, '2025-05-31 23:17:02'),
(1452, 'CAKE/USDT', 'bot_update', NULL, '2025-06-01 13:00:16', '2.28560000', 'BUY', 1, '2025-05-31 23:17:11'),
(1453, 'TON/USDT', 'bot_update', NULL, '2025-06-01 12:09:31', '3.11430000', 'BUY', 1, '2025-05-31 23:24:41'),
(1454, 'SOL/USDT', 'bot_update', NULL, '2025-06-01 05:49:29', '155.12000000', 'BUY', 1, '2025-05-31 23:26:54'),
(1455, 'BNB/USDT', 'bot_update', NULL, '2025-06-01 05:35:56', '655.90000000', 'BUY', 1, '2025-05-31 23:27:12'),
(1456, 'OM/USDT', 'bot_update', NULL, '2025-05-31 23:35:45', '0.30838000', 'BUY', 1, '2025-05-31 23:30:16'),
(1457, 'ARB/USDT', 'bot_update', NULL, '2025-06-01 05:09:07', '0.34150000', 'BUY', 1, '2025-05-31 23:30:24'),
(1458, 'HAEDAL/USDT', 'bot_update', NULL, '2025-06-01 12:26:19', '0.12897100', 'BUY', 1, '2025-05-31 23:30:42'),
(1459, 'ETH/USDT', 'bot_update', NULL, '2025-06-01 05:11:21', '2521.69000000', 'BUY', 1, '2025-05-31 23:32:27'),
(1460, 'BOME/USDT', 'bot_update', NULL, '2025-06-01 12:26:33', '0.00178800', 'BUY', 1, '2025-05-31 23:33:20'),
(1461, 'INIT/USDT', 'bot_update', NULL, '2025-06-01 12:14:46', '0.74470000', 'BUY', 1, '2025-05-31 23:52:01'),
(1462, 'SAGA/USDT', 'bot_discovery', NULL, '2025-06-01 00:05:32', '0.29140000', 'BUY', 1, '2025-06-01 00:05:32'),
(1463, 'AXS/USDT', 'bot_update', NULL, '2025-06-01 09:12:11', '2.50200000', 'BUY', 1, '2025-06-01 00:05:43'),
(1464, '1MBABYDOGE/USDT', 'bot_discovery', NULL, '2025-06-01 00:05:51', '0.00138880', 'BUY', 1, '2025-06-01 00:05:51'),
(1465, 'DF/USDT', 'bot_update', NULL, '2025-06-01 10:32:22', '0.04510000', 'BUY', 1, '2025-06-01 00:16:52'),
(1466, 'KERNEL/USDT', 'bot_update', NULL, '2025-06-01 13:26:58', '0.15500000', 'BUY', 1, '2025-06-01 00:32:52'),
(1467, 'INJ/USDT', 'bot_update', NULL, '2025-06-01 02:53:40', '11.97900000', 'BUY', 1, '2025-06-01 00:37:46'),
(1468, 'MUBARAK/USDT', 'bot_update', NULL, '2025-06-01 12:26:24', '0.03846000', 'BUY', 1, '2025-06-01 00:37:57'),
(1469, 'NIL/USDT', 'bot_update', NULL, '2025-06-01 12:20:55', '0.44850000', 'BUY', 1, '2025-06-01 00:41:02'),
(1470, 'USUAL/USDT', 'bot_update', NULL, '2025-06-01 12:26:57', '0.10170000', 'BUY', 1, '2025-06-01 00:43:23'),
(1471, 'XAI/USDT', 'bot_discovery', NULL, '2025-06-01 00:43:48', '0.07077000', 'BUY', 1, '2025-06-01 00:43:48'),
(1472, 'SYRUP/USDT', 'bot_update', NULL, '2025-06-01 11:16:31', '0.33951000', 'BUY', 1, '2025-06-01 00:46:17'),
(1473, 'S/USDT', 'bot_update', NULL, '2025-06-01 11:55:51', '0.38750000', 'BUY', 1, '2025-06-01 00:57:02'),
(1474, 'IO/USDT', 'bot_update', NULL, '2025-06-01 05:03:58', '0.82040000', 'BUY', 1, '2025-06-01 00:57:19'),
(1475, 'CATI/USDT', 'bot_discovery', NULL, '2025-06-01 01:07:55', '0.10050000', 'BUY', 1, '2025-06-01 01:07:55'),
(1476, 'NOT/USDT', 'bot_update', NULL, '2025-06-01 12:37:08', '0.00218300', 'BUY', 1, '2025-06-01 01:08:06'),
(1477, 'A/USDT', 'bot_discovery', NULL, '2025-06-01 01:10:48', '0.61420000', 'BUY', 1, '2025-06-01 01:10:48'),
(1478, 'SUI/USDT', 'bot_update', NULL, '2025-06-01 11:41:56', '3.25930000', 'BUY', 1, '2025-06-01 01:12:42'),
(1479, 'UNI/USDT', 'bot_update', NULL, '2025-06-01 11:48:55', '6.22700000', 'BUY', 1, '2025-06-01 01:13:14'),
(1480, 'NEAR/USDT', 'bot_update', NULL, '2025-06-01 12:01:03', '2.39000000', 'BUY', 1, '2025-06-01 01:13:19'),
(1481, 'XLM/USDT', 'bot_update', NULL, '2025-06-01 10:40:56', '0.26269000', 'BUY', 1, '2025-06-01 01:13:40'),
(1482, 'PENGU/USDT', 'bot_update', NULL, '2025-06-01 12:09:36', '0.01028300', 'BUY', 1, '2025-06-01 01:13:47'),
(1483, 'DYDX/USDT', 'bot_update', NULL, '2025-06-01 11:33:40', '0.54200000', 'NEUTRAL', 1, '2025-06-01 01:16:36'),
(1484, 'SSV/USDT', 'bot_update', NULL, '2025-06-01 13:11:55', '8.53800000', 'BUY', 1, '2025-06-01 01:30:21'),
(1485, 'APT/USDT', 'bot_update', NULL, '2025-06-01 11:52:41', '4.69510000', 'BUY', 1, '2025-06-01 01:43:57'),
(1486, 'TRX/USDT', 'bot_update', NULL, '2025-06-01 06:59:37', '0.26895000', 'BUY', 1, '2025-06-01 01:46:51'),
(1488, 'FLOW/USDT', 'bot_update', NULL, '2025-06-01 05:20:32', '0.36700000', 'BUY', 1, '2025-06-01 02:18:56'),
(1489, 'OP/USDT', 'bot_update', NULL, '2025-06-01 08:44:40', '0.65170000', 'SELL', 1, '2025-06-01 02:20:57'),
(1490, 'ETHFI/USDT', 'bot_update', NULL, '2025-06-01 11:42:11', '1.11800000', 'BUY', 1, '2025-06-01 02:21:02'),
(1491, 'PUNDIX/USDT', 'bot_update', NULL, '2025-06-01 09:12:01', '0.32400000', 'BUY', 1, '2025-06-01 02:21:37'),
(1492, 'EPIC/USDT', 'bot_discovery', NULL, '2025-06-01 02:21:51', '1.20990000', 'BUY', 1, '2025-06-01 02:21:51'),
(1493, 'GUN/USDT', 'bot_update', NULL, '2025-06-01 09:12:06', '0.03960000', 'BUY', 1, '2025-06-01 02:27:12'),
(1494, 'WIF/USDT', 'bot_update', NULL, '2025-06-01 12:25:54', '0.82300000', 'BUY', 1, '2025-06-01 02:37:16'),
(1495, 'LAYER/USDT', 'bot_discovery', NULL, '2025-06-01 02:53:57', '0.77530000', 'BUY', 1, '2025-06-01 02:53:57'),
(1496, 'ETC/USDT', 'bot_update', NULL, '2025-06-01 11:52:50', '16.78900000', 'BUY', 1, '2025-06-01 03:02:35'),
(1497, 'FET/USDT', 'bot_update', NULL, '2025-06-01 11:39:44', '0.74300000', 'BUY', 1, '2025-06-01 03:31:39'),
(1498, 'VOXEL/USDT', 'bot_discovery', NULL, '2025-06-01 03:32:12', '0.06050000', 'BUY', 1, '2025-06-01 03:32:12'),
(1499, 'SXT/USDT', 'bot_update', NULL, '2025-06-01 10:02:59', '0.10038000', 'BUY', 1, '2025-06-01 03:32:17'),
(1500, 'DOGE/USDT', 'bot_update', NULL, '2025-06-01 12:06:07', '0.18866000', 'BUY', 1, '2025-06-01 03:34:13'),
(1501, 'CRV/USDT', 'bot_update', NULL, '2025-06-01 12:20:12', '0.65800000', 'BUY', 1, '2025-06-01 03:37:54'),
(1502, 'APE/USDT', 'bot_update', NULL, '2025-06-01 11:19:36', '0.62250000', 'BUY', 1, '2025-06-01 03:48:41'),
(1503, 'ENS/USDT', 'bot_update', NULL, '2025-06-01 11:49:53', '20.34900000', 'BUY', 1, '2025-06-01 04:02:21'),
(1504, 'SAND/USDT', 'bot_discovery', NULL, '2025-06-01 04:27:20', '0.27111000', 'BUY', 1, '2025-06-01 04:27:20'),
(1505, 'BIO/USDT', 'bot_update', NULL, '2025-06-01 12:07:04', '0.06568000', 'BUY', 1, '2025-06-01 04:30:07'),
(1506, 'TIA/USDT', 'bot_update', NULL, '2025-06-01 10:02:19', '2.16570000', 'BUY', 1, '2025-06-01 04:38:14'),
(1507, 'HUMA/USDT', 'bot_update', NULL, '2025-06-01 11:22:04', '0.03639200', 'BUY', 1, '2025-06-01 04:40:52'),
(1508, 'ARKM/USDT', 'bot_update', NULL, '2025-06-01 12:07:09', '0.53820000', 'BUY', 1, '2025-06-01 04:52:27'),
(1509, 'AUCTION/USDT', 'bot_update', NULL, '2025-06-01 10:16:30', '10.27400000', 'BUY', 1, '2025-06-01 05:01:23'),
(1510, 'DOGS/USDT', 'bot_update', NULL, '2025-06-01 12:55:15', '0.00014690', 'BUY', 1, '2025-06-01 05:04:03'),
(1511, 'MOVE/USDT', 'bot_update', NULL, '2025-06-01 11:49:45', '0.13690000', 'BUY', 1, '2025-06-01 05:23:02'),
(1512, 'ICP/USDT', 'bot_update', NULL, '2025-06-01 11:43:02', '4.87800000', 'BUY', 1, '2025-06-01 05:47:33'),
(1513, 'ANIME/USDT', 'bot_update', NULL, '2025-06-01 13:05:18', '0.02507000', 'BUY', 1, '2025-06-01 06:11:43'),
(1514, 'GALA/USDT', 'bot_update', NULL, '2025-06-01 12:20:31', '0.01649000', 'BUY', 1, '2025-06-01 07:15:59'),
(1515, 'PAXG/USDT', 'bot_update', NULL, '2025-06-01 12:34:49', '3305.64000000', 'BUY', 1, '2025-06-01 07:53:50'),
(1516, 'ZK/USDT', 'bot_discovery', NULL, '2025-06-01 08:01:53', '0.05313000', 'BUY', 1, '2025-06-01 08:01:53'),
(1517, 'NXPC/USDT', 'bot_discovery', NULL, '2025-06-01 08:20:00', '1.38190000', 'BUY', 1, '2025-06-01 08:20:00'),
(1518, 'STX/USDT', 'bot_update', NULL, '2025-06-01 11:13:57', '0.72490000', 'BUY', 1, '2025-06-01 08:28:51'),
(1519, 'ZRO/USDT', 'bot_update', NULL, '2025-06-01 11:34:09', '2.24270000', 'BUY', 1, '2025-06-01 08:34:28'),
(1520, 'SIGN/USDT', 'bot_update', NULL, '2025-06-01 12:54:58', '0.07602000', 'BUY', 1, '2025-06-01 08:37:02'),
(1521, 'KMNO/USDT', 'bot_update', NULL, '2025-06-01 11:19:51', '0.05329000', 'BUY', 1, '2025-06-01 09:28:14'),
(1522, 'OGN/USDT', 'bot_discovery', NULL, '2025-06-01 10:10:48', '0.05720000', 'BUY', 1, '2025-06-01 10:10:48'),
(1523, 'REZ/USDT', 'bot_update', NULL, '2025-06-01 13:00:36', '0.01118000', 'BUY', 1, '2025-06-01 10:16:38'),
(1524, 'SOPH/USDT', 'bot_update', NULL, '2025-06-01 11:45:35', '0.05167200', 'BUY', 1, '2025-06-01 10:31:52'),
(1525, 'XTZ/USDT', 'bot_discovery', NULL, '2025-06-01 10:38:21', '0.55700000', 'BUY', 1, '2025-06-01 10:38:21'),
(1526, 'PORTAL/USDT', 'bot_update', NULL, '2025-06-01 11:28:16', '0.04780000', 'BUY', 1, '2025-06-01 10:46:50'),
(1527, 'JUP/USDT', 'bot_update', NULL, '2025-06-01 12:26:38', '0.51480000', 'BUY', 1, '2025-06-01 11:25:13'),
(1528, 'IMX/USDT', 'bot_discovery', NULL, '2025-06-01 11:25:36', '0.54210000', 'BUY', 1, '2025-06-01 11:25:36'),
(1529, 'ALICE/USDT', 'bot_discovery', NULL, '2025-06-01 11:28:27', '0.39400000', 'BUY', 1, '2025-06-01 11:28:27'),
(1530, 'TRB/USDT', 'bot_update', NULL, '2025-06-01 13:16:23', '43.56400000', 'BUY', 1, '2025-06-01 12:34:04'),
(1531, 'RPL/USDT', 'bot_discovery', NULL, '2025-06-01 12:50:14', '4.77100000', 'BUY', 1, '2025-06-01 12:50:14'),
(1532, 'GTC/USDT', 'bot_discovery', NULL, '2025-06-01 12:50:38', '0.25200000', 'BUY', 1, '2025-06-01 12:50:38'),
(1533, 'BSW/USDT', 'bot_discovery', NULL, '2025-06-01 12:53:07', '0.02514000', 'BUY', 1, '2025-06-01 12:53:07'),
(1534, 'HEI/USDT', 'bot_discovery', NULL, '2025-06-01 12:53:18', '0.31750000', 'BUY', 1, '2025-06-01 12:53:18'),
(1535, 'MASK/USDT', 'bot_update', NULL, '2025-06-01 13:00:01', '2.12830000', 'BUY', 1, '2025-06-01 12:54:34'),
(1536, 'HYPER/USDT', 'bot_discovery', NULL, '2025-06-01 12:55:23', '0.12690000', 'BUY', 1, '2025-06-01 12:55:23'),
(1537, 'MANTA/USDT', 'bot_discovery', NULL, '2025-06-01 12:57:42', '0.23720000', 'BUY', 1, '2025-06-01 12:57:42'),
(1538, '1000CAT/USDT', 'bot_discovery', NULL, '2025-06-01 12:57:50', '0.00707900', 'BUY', 1, '2025-06-01 12:57:50'),
(1539, 'RDNT/USDT', 'bot_discovery', NULL, '2025-06-01 12:58:01', '0.02364000', 'BUY', 1, '2025-06-01 12:58:01'),
(1540, 'ILV/USDT', 'bot_discovery', NULL, '2025-06-01 12:58:05', '12.71300000', 'BUY', 1, '2025-06-01 12:58:05'),
(1541, 'NFP/USDT', 'bot_discovery', NULL, '2025-06-01 13:19:29', '0.07140000', 'BUY', 1, '2025-06-01 13:19:29'),
(1542, 'BMT/USDT', 'bot_discovery', NULL, '2025-06-01 13:27:12', '0.08880000', 'BUY', 1, '2025-06-01 13:27:12');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `adaptive_parameter_settings`
--

CREATE TABLE IF NOT EXISTS `adaptive_parameter_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `adaptation_speed` float DEFAULT '0.3',
  `market_regime_detection` tinyint(1) DEFAULT '1',
  `volatility_adjustment` tinyint(1) DEFAULT '1',
  `trend_strength_adjustment` tinyint(1) DEFAULT '1',
  `learning_rate` float DEFAULT '0.1',
  `min_history_required` int(11) DEFAULT '100',
  `reset_after_market_shift` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `adaptive_parameter_settings`
--

INSERT INTO `adaptive_parameter_settings` (`id`, `user_id`, `enabled`, `adaptation_speed`, `market_regime_detection`, `volatility_adjustment`, `trend_strength_adjustment`, `learning_rate`, `min_history_required`, `reset_after_market_shift`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 0.5, 0, 0, 0, 0.05, 100, 0, '2025-05-11 00:34:22', '2025-05-15 22:33:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `api_optimization_settings`
--

CREATE TABLE IF NOT EXISTS `api_optimization_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `api_call_limit_per_minute` int(11) DEFAULT '60',
  `api_call_distribution` enum('even','priority-based','adaptive') DEFAULT 'adaptive',
  `cache_duration` int(11) DEFAULT '300',
  `prioritize_active_trades` tinyint(1) DEFAULT '1',
  `reduce_timeframes_count` tinyint(1) DEFAULT '1',
  `optimize_indicator_calculations` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `api_optimization_settings`
--

INSERT INTO `api_optimization_settings` (`id`, `user_id`, `enabled`, `api_call_limit_per_minute`, `api_call_distribution`, `cache_duration`, `prioritize_active_trades`, `reduce_timeframes_count`, `optimize_indicator_calculations`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 60, 'even', 60, 0, 0, 0, '2025-05-11 00:34:22', '2025-05-15 22:33:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bot_settings`
--

CREATE TABLE IF NOT EXISTS `bot_settings` (
  `id` int(11) NOT NULL,
  `settings_json` longtext,
  `settings` text NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `smart_trend_settings_id` int(11) DEFAULT NULL,
  `risk_management_settings_id` int(11) DEFAULT NULL,
  `adaptive_parameter_settings_id` int(11) DEFAULT NULL,
  `api_optimization_settings_id` int(11) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `bot_settings`
--

INSERT INTO `bot_settings` (`id`, `settings_json`, `settings`, `last_updated`, `smart_trend_settings_id`, `risk_management_settings_id`, `adaptive_parameter_settings_id`, `api_optimization_settings_id`) VALUES
(1, '{\n    "exchange": "binance",\n    "base_currency": "USDT",\n    "min_volume": 1000000,\n    "max_coins": 5,\n    "min_trade_amount": 5,\n    "max_trade_amount": 6,\n    "position_size": 0.5,\n    "api_delay": 0.5,\n    "scan_interval": 30,\n    "use_tradingview": false,\n    "tradingview_exchange": "BINANCE",\n    "auto_discovery": {\n        "enabled": true,\n        "discovery_interval": 60,\n        "min_volume_for_discovery": 1000,\n        "min_price_change": 5,\n        "min_volume_change": 10,\n        "max_coins_to_discover": 10,\n        "auto_add_to_watchlist": false\n    },\n    "indicators": {\n        "bollinger_bands": {\n            "enabled": true,\n            "window": 20,\n            "num_std": 2\n        },\n        "rsi": {\n            "enabled": true,\n            "window": 14\n        },\n        "macd": {\n            "enabled": true,\n            "fast_period": 12,\n            "slow_period": 26,\n            "signal_period": 9\n        },\n        "moving_average": {\n            "enabled": true,\n            "short_window": 10,\n            "long_window": 50\n        },\n        "supertrend": {\n            "enabled": true,\n            "period": 10,\n            "multiplier": 3\n        },\n        "vwap": {\n            "enabled": false,\n            "period": 14\n        },\n        "pivot_points": {\n            "enabled": false,\n            "method": "standard"\n        },\n        "fibonacci": {\n            "enabled": false,\n            "period": 20\n        },\n        "stochastic": {\n            "enabled": true,\n            "k_period": 14,\n            "d_period": 3,\n            "slowing": 3\n        }\n    },\n    "strategies": {\n        "short_term": {\n            "enabled": true\n        },\n        "trend_following": {\n            "enabled": true\n        },\n        "breakout": {\n            "enabled": true\n        },\n        "volatility_breakout": {\n            "enabled": true\n        }\n    },\n    "risk_management": {\n        "enabled": false,\n        "stop_loss": 0,\n        "take_profit": 0,\n        "trailing_stop": false,\n        "trailing_stop_distance": 0,\n        "trailing_stop_activation_pct": 0,\n        "trailing_stop_pct": 0,\n        "max_open_positions": 5,\n        "max_risk_per_trade": 1\n    },\n    "backtesting": {\n        "default_start_date": "2023-01-01",\n        "default_end_date": "2023-12-31",\n        "initial_capital": 1000,\n        "trading_fee": 0.01,\n        "slippage": 0.001,\n        "enable_visualization": false\n    },\n    "telegram": {\n        "enabled": true,\n        "token": "7096827438:AAHn3B9jgE7gZcakMZFTNnv7NceItOWYN8Y",\n        "chat_id": "959370656",\n        "trade_signals": false,\n        "discovered_coins": false\n    },\n    "trade_mode": "live",\n    "auto_trade": true,\n    "trade_direction": "both",\n    "leverage": 3,\n    "leverage_mode": "isolated",\n    "trade_amount": 10,\n    "max_open_trades": 3,\n    "stop_loss_pct": 2,\n    "take_profit_pct": 3,\n    "use_telegram": false,\n    "interval": "3m",\n    "max_api_retries": 3,\n    "retry_delay": 5,\n    "active_coins": [\n        "BTC",\n        "ETH",\n        "BNB",\n        "ADA",\n        "SOL"\n    ],\n    "timeframes": [\n        "1m",\n        "3m",\n        "5m",\n        "15m"\n    ],\n    "primary_timeframe": "1h",\n    "timeframe_weight_short": 50,\n    "timeframe_weight_medium": 25,\n    "timeframe_weight_long": 25,\n    "timeframe_consensus": "all",\n    "api_keys": {\n        "binance_api_key": "yG2R3AycGxjg9aI3Lw8pyAl1uA5X0GfJ5CIGmX5UxJScuD8G8O11S44z4vlQ8H6s",\n        "binance_api_secret": "jNspyoq8ysV9YCpTDGNyGavzpRYldXjsPmDeBbKqAXfJELa4OzZSh8JMMeyB6nuD",\n        "kucoin_api_key": "",\n        "kucoin_api_secret": "",\n        "kucoin_api_passphrase": ""\n    },\n    "indicator_weights": {\n        "rsi": 20,\n        "macd": 15,\n        "bollinger_bands": 15,\n        "moving_average": 15,\n        "supertrend": 15,\n        "stochastic": 10,\n        "adx": 5,\n        "other": 5\n    },\n    "signal_consensus_method": "weighted",\n    "signal_consensus_threshold": 80,\n    "signal_confirmation_count": 2,\n    "signal_conflicting_action": "wait",\n    "indicator_signals": {\n        "rsi": {\n            "oversold": 33,\n            "overbought": 80,\n            "center_line_cross": false,\n            "divergence": true\n        },\n        "bollinger_bands": {\n            "squeeze_threshold": 0.5,\n            "breakout_confirmation_candles": 2,\n            "use_percentage_b": false,\n            "mean_reversion": false\n        },\n        "macd": {\n            "signal_strength": 0.01,\n            "zero_line_cross": true,\n            "histogram_divergence": false,\n            "trigger_type": "signal_line"\n        },\n        "supertrend": {\n            "confirmation_candles": 1,\n            "filter_adx": false,\n            "adx_threshold": 0\n        }\n    },\n    "market_type": "futures",\n    "integration_settings": {\n        "use_smart_trend": true,\n        "smart_trend_settings": {\n            "detection_method": "ema_cross",\n            "sensitivity": 0.5,\n            "lookback_period": 100,\n            "confirmation_period": 3,\n            "signal_quality_threshold": 0.7\n        }\n    },\n    "advanced_risk_management": {\n        "enabled": true,\n        "dynamic_position_sizing": true,\n        "position_size_method": "fixed",\n        "max_risk_per_trade": 1,\n        "volatility_based_stops": false,\n        "adaptive_take_profit": false,\n        "auto_adjust_risk": false,\n        "max_open_positions": 5\n    },\n    "adaptive_parameters": {\n        "enabled": true,\n        "adaptation_speed": 0.5,\n        "market_regime_detection": false,\n        "volatility_adjustment": false,\n        "trend_strength_adjustment": false,\n        "reset_after_market_shift": false,\n        "learning_rate": 0.05,\n        "min_history_required": 100\n    },\n    "api_optimization": {\n        "enabled": true,\n        "api_call_limit_per_minute": 60,\n        "api_call_distribution": "even",\n        "cache_duration": 60,\n        "prioritize_active_trades": false,\n        "reduce_timeframes_count": false,\n        "optimize_indicator_calculations": false\n    },\n    "telegram_enabled": true,\n    "telegram_trade_signals": false,\n    "telegram_discovered_coins": false,\n    "telegram_position_updates": false,\n    "telegram_performance_updates": false,\n    "enable_visualization": false,\n    "auto_discovery_enabled": true,\n    "auto_add_to_watchlist": true,\n    "trailing_stop": true,\n    "dynamic_position_sizing": true,\n    "volatility_based_stops": false,\n    "adaptive_take_profit": false,\n    "auto_adjust_risk": false,\n    "market_regime_detection": false,\n    "reset_after_market_shift": false,\n    "volatility_adjustment": false,\n    "trend_strength_adjustment": false,\n    "prioritize_active_trades": false,\n    "reduce_timeframes_count": false,\n    "optimize_indicator_calculations": false\n}', '{\n    "exchange": "binance",\n    "base_currency": "USDT",\n    "min_volume": 1000000,\n    "max_coins": 5,\n    "min_trade_amount": 5,\n    "max_trade_amount": 6,\n    "position_size": 0.5,\n    "api_delay": 0.5,\n    "scan_interval": 30,\n    "use_tradingview": false,\n    "tradingview_exchange": "BINANCE",\n    "auto_discovery": {\n        "enabled": true,\n        "discovery_interval": 60,\n        "min_volume_for_discovery": 1000,\n        "min_price_change": 5,\n        "min_volume_change": 10,\n        "max_coins_to_discover": 10,\n        "auto_add_to_watchlist": false\n    },\n    "indicators": {\n        "bollinger_bands": {\n            "enabled": true,\n            "window": 20,\n            "num_std": 2\n        },\n        "rsi": {\n            "enabled": true,\n            "window": 14\n        },\n        "macd": {\n            "enabled": true,\n            "fast_period": 12,\n            "slow_period": 26,\n            "signal_period": 9\n        },\n        "moving_average": {\n            "enabled": true,\n            "short_window": 10,\n            "long_window": 50\n        },\n        "supertrend": {\n            "enabled": true,\n            "period": 10,\n            "multiplier": 3\n        },\n        "vwap": {\n            "enabled": false,\n            "period": 14\n        },\n        "pivot_points": {\n            "enabled": false,\n            "method": "standard"\n        },\n        "fibonacci": {\n            "enabled": false,\n            "period": 20\n        },\n        "stochastic": {\n            "enabled": true,\n            "k_period": 14,\n            "d_period": 3,\n            "slowing": 3\n        }\n    },\n    "strategies": {\n        "short_term": {\n            "enabled": true\n        },\n        "trend_following": {\n            "enabled": true\n        },\n        "breakout": {\n            "enabled": true\n        },\n        "volatility_breakout": {\n            "enabled": true\n        }\n    },\n    "risk_management": {\n        "enabled": false,\n        "stop_loss": 0,\n        "take_profit": 0,\n        "trailing_stop": false,\n        "trailing_stop_distance": 0,\n        "trailing_stop_activation_pct": 0,\n        "trailing_stop_pct": 0,\n        "max_open_positions": 5,\n        "max_risk_per_trade": 1\n    },\n    "backtesting": {\n        "default_start_date": "2023-01-01",\n        "default_end_date": "2023-12-31",\n        "initial_capital": 1000,\n        "trading_fee": 0.01,\n        "slippage": 0.001,\n        "enable_visualization": false\n    },\n    "telegram": {\n        "enabled": true,\n        "token": "7096827438:AAHn3B9jgE7gZcakMZFTNnv7NceItOWYN8Y",\n        "chat_id": "959370656",\n        "trade_signals": false,\n        "discovered_coins": false\n    },\n    "trade_mode": "live",\n    "auto_trade": true,\n    "trade_direction": "both",\n    "leverage": 3,\n    "leverage_mode": "isolated",\n    "trade_amount": 10,\n    "max_open_trades": 3,\n    "stop_loss_pct": 2,\n    "take_profit_pct": 3,\n    "use_telegram": false,\n    "interval": "3m",\n    "max_api_retries": 3,\n    "retry_delay": 5,\n    "active_coins": [\n        "BTC",\n        "ETH",\n        "BNB",\n        "ADA",\n        "SOL"\n    ],\n    "timeframes": [\n        "1m",\n        "3m",\n        "5m",\n        "15m"\n    ],\n    "primary_timeframe": "1h",\n    "timeframe_weight_short": 50,\n    "timeframe_weight_medium": 25,\n    "timeframe_weight_long": 25,\n    "timeframe_consensus": "all",\n    "api_keys": {\n        "binance_api_key": "yG2R3AycGxjg9aI3Lw8pyAl1uA5X0GfJ5CIGmX5UxJScuD8G8O11S44z4vlQ8H6s",\n        "binance_api_secret": "jNspyoq8ysV9YCpTDGNyGavzpRYldXjsPmDeBbKqAXfJELa4OzZSh8JMMeyB6nuD",\n        "kucoin_api_key": "",\n        "kucoin_api_secret": "",\n        "kucoin_api_passphrase": ""\n    },\n    "indicator_weights": {\n        "rsi": 20,\n        "macd": 15,\n        "bollinger_bands": 15,\n        "moving_average": 15,\n        "supertrend": 15,\n        "stochastic": 10,\n        "adx": 5,\n        "other": 5\n    },\n    "signal_consensus_method": "weighted",\n    "signal_consensus_threshold": 80,\n    "signal_confirmation_count": 2,\n    "signal_conflicting_action": "wait",\n    "indicator_signals": {\n        "rsi": {\n            "oversold": 33,\n            "overbought": 80,\n            "center_line_cross": false,\n            "divergence": true\n        },\n        "bollinger_bands": {\n            "squeeze_threshold": 3,\n            "breakout_confirmation_candles": 2,\n            "use_percentage_b": false,\n            "mean_reversion": false\n        },\n        "macd": {\n            "signal_strength": 0.2,\n            "zero_line_cross": true,\n            "histogram_divergence": false,\n            "trigger_type": "crossover"\n        },\n        "supertrend": {\n            "confirmation_candles": 1,\n            "filter_adx": false,\n            "adx_threshold": 0\n        }\n    },\n    "market_type": "futures",\n    "integration_settings": {\n        "use_smart_trend": true,\n        "smart_trend_settings": {\n            "detection_method": "multi_timeframe",\n            "sensitivity": 0.5,\n            "lookback_period": 100,\n            "confirmation_period": 3,\n            "signal_quality_threshold": 0.7\n        }\n    },\n    "advanced_risk_management": {\n        "enabled": true,\n        "dynamic_position_sizing": true,\n        "position_size_method": "fixed",\n        "max_risk_per_trade": 1,\n        "volatility_based_stops": false,\n        "adaptive_take_profit": false,\n        "auto_adjust_risk": false,\n        "max_open_positions": 3,\n        "reset_after_market_shift": false\n    },\n    "adaptive_parameters": {\n        "enabled": true,\n        "adaptation_speed": 0.5,\n        "market_regime_detection": false,\n        "volatility_adjustment": false,\n        "trend_strength_adjustment": false,\n        "reset_after_market_shift": false,\n        "learning_rate": 0.05,\n        "min_history_required": 100\n    },\n    "api_optimization": {\n        "enabled": true,\n        "api_call_limit_per_minute": 60,\n        "api_call_distribution": "even",\n        "cache_duration": 60,\n        "prioritize_active_trades": false,\n        "reduce_timeframes_count": false,\n        "optimize_indicator_calculations": false\n    },\n    "telegram_enabled": true,\n    "telegram_trade_signals": false,\n    "telegram_discovered_coins": false,\n    "telegram_position_updates": false,\n    "telegram_performance_updates": false,\n    "enable_visualization": false,\n    "auto_discovery_enabled": true,\n    "auto_add_to_watchlist": true,\n    "trailing_stop": true,\n    "dynamic_position_sizing": true,\n    "volatility_based_stops": false,\n    "adaptive_take_profit": false,\n    "auto_adjust_risk": false,\n    "market_regime_detection": false,\n    "reset_after_market_shift": false,\n    "volatility_adjustment": false,\n    "trend_strength_adjustment": false,\n    "prioritize_active_trades": false,\n    "reduce_timeframes_count": false,\n    "optimize_indicator_calculations": false\n}', '2025-05-31 13:58:58', 2, 2, 2, 2);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bot_settings_individual`
--

CREATE TABLE IF NOT EXISTS `bot_settings_individual` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `bot_settings_individual`
--

INSERT INTO `bot_settings_individual` (`id`, `setting_name`, `setting_value`, `created_at`, `updated_at`, `last_updated`) VALUES
(1, 'exchange', 'binance', '2025-05-28 21:42:23', NULL, NULL),
(2, 'base_currency', 'USDT', '2025-05-28 21:42:23', NULL, NULL),
(3, 'min_volume', '1000000', '2025-05-28 21:42:23', NULL, NULL),
(4, 'max_coins', '5', '2025-05-28 21:42:23', NULL, NULL),
(5, 'min_trade_amount', '5', '2025-05-28 21:42:23', NULL, NULL),
(6, 'max_trade_amount', '6', '2025-05-28 21:42:23', NULL, NULL),
(7, 'position_size', '0.5', '2025-05-28 21:42:23', NULL, NULL),
(8, 'api_delay', '0.5', '2025-05-28 21:42:23', NULL, NULL),
(9, 'scan_interval', '30', '2025-05-28 21:42:23', NULL, NULL),
(10, 'use_tradingview', '', '2025-05-28 21:42:23', NULL, NULL),
(11, 'tradingview_exchange', 'BINANCE', '2025-05-28 21:42:23', NULL, NULL),
(12, 'auto_discovery', '{"enabled":true,"discovery_interval":60,"min_volume_for_discovery":1000,"min_price_change":5,"min_volume_change":10,"max_coins_to_discover":10,"auto_add_to_watchlist":true}', '2025-05-28 21:42:23', NULL, NULL),
(13, 'indicators', '{"bollinger_bands":{"enabled":true,"window":20,"num_std":2},"rsi":{"enabled":true,"window":14},"macd":{"enabled":true,"fast_period":12,"slow_period":26,"signal_period":9},"moving_average":{"enabled":true,"short_window":10,"long_window":50},"supertrend":{"enabled":true,"period":10,"multiplier":3},"vwap":{"enabled":false,"period":14},"pivot_points":{"enabled":false,"method":"standard"},"fibonacci":{"enabled":false,"period":20},"stochastic":{"enabled":true,"k_period":14,"d_period":3,"slowing":3}}', '2025-05-28 21:42:23', NULL, NULL),
(14, 'strategies', '{"short_term":{"enabled":true},"trend_following":{"enabled":true},"breakout":{"enabled":true},"volatility_breakout":{"enabled":true}}', '2025-05-28 21:42:23', NULL, NULL),
(15, 'risk_management', '{"enabled":true,"stop_loss":5,"take_profit":10,"trailing_stop":true,"trailing_stop_distance":2,"trailing_stop_activation_pct":3,"trailing_stop_pct":2,"max_open_positions":3,"max_risk_per_trade":1}', '2025-05-28 21:42:23', NULL, NULL),
(16, 'backtesting', '{"default_start_date":"2023-01-01","default_end_date":"2023-12-31","initial_capital":1000,"trading_fee":0.01,"slippage":0.001,"enable_visualization":false}', '2025-05-28 21:42:23', NULL, NULL),
(17, 'telegram', '{"enabled":true,"token":"7096827438:AAHn3B9jgE7gZcakMZFTNnv7NceItOWYN8Y","chat_id":"959370656","trade_signals":false,"discovered_coins":false}', '2025-05-28 21:42:23', NULL, NULL),
(18, 'trade_mode', 'live', '2025-05-28 21:42:23', NULL, NULL),
(19, 'auto_trade', '1', '2025-05-28 21:42:23', NULL, NULL),
(20, 'trade_direction', 'both', '2025-05-28 21:42:23', NULL, NULL),
(21, 'leverage', '3', '2025-05-28 21:42:23', NULL, NULL),
(22, 'leverage_mode', 'isolated', '2025-05-28 21:42:23', NULL, NULL),
(23, 'trade_amount', '10', '2025-05-28 21:42:23', NULL, NULL),
(24, 'max_open_trades', '3', '2025-05-28 21:42:23', NULL, NULL),
(25, 'stop_loss_pct', '2', '2025-05-28 21:42:23', NULL, NULL),
(26, 'take_profit_pct', '3', '2025-05-28 21:42:23', NULL, NULL),
(27, 'use_telegram', '', '2025-05-28 21:42:23', NULL, NULL),
(28, 'interval', '3m', '2025-05-28 21:42:23', NULL, NULL),
(29, 'max_api_retries', '3', '2025-05-28 21:42:23', NULL, NULL),
(30, 'retry_delay', '5', '2025-05-28 21:42:23', NULL, NULL),
(31, 'active_coins', '["BTC","ETH","BNB","ADA","SOL"]', '2025-05-28 21:42:23', NULL, NULL),
(32, 'timeframes', '["1m","3m","5m","15m"]', '2025-05-28 21:42:23', NULL, NULL),
(33, 'primary_timeframe', '1h', '2025-05-28 21:42:23', NULL, NULL),
(34, 'timeframe_weight_short', '50', '2025-05-28 21:42:23', NULL, NULL),
(35, 'timeframe_weight_medium', '25', '2025-05-28 21:42:23', NULL, NULL),
(36, 'timeframe_weight_long', '25', '2025-05-28 21:42:23', NULL, NULL),
(37, 'timeframe_consensus', 'all', '2025-05-28 21:42:23', NULL, NULL),
(38, 'api_keys', '{"binance_api_key":"yG2R3AycGxjg9aI3Lw8pyAl1uA5X0GfJ5CIGmX5UxJScuD8G8O11S44z4vlQ8H6s","binance_api_secret":"jNspyoq8ysV9YCpTDGNyGavzpRYldXjsPmDeBbKqAXfJELa4OzZSh8JMMeyB6nuD","kucoin_api_key":"","kucoin_api_secret":"","kucoin_api_passphrase":""}', '2025-05-28 21:42:23', NULL, NULL),
(39, 'indicator_weights', '{"rsi":20,"macd":15,"bollinger_bands":15,"moving_average":15,"supertrend":15,"stochastic":10,"adx":5,"other":5}', '2025-05-28 21:42:23', NULL, NULL),
(40, 'signal_consensus_method', 'weighted', '2025-05-28 21:42:23', NULL, NULL),
(41, 'signal_consensus_threshold', '80', '2025-05-28 21:42:23', NULL, NULL),
(42, 'signal_confirmation_count', '2', '2025-05-28 21:42:23', NULL, NULL),
(43, 'signal_conflicting_action', 'wait', '2025-05-28 21:42:23', NULL, NULL),
(44, 'indicator_signals', '{"rsi":{"oversold":33,"overbought":80,"center_line_cross":false,"divergence":true},"bollinger_bands":{"squeeze_threshold":3,"breakout_confirmation_candles":2,"use_percentage_b":false,"mean_reversion":false},"macd":{"signal_strength":0.2,"zero_line_cross":true,"histogram_divergence":false,"trigger_type":"crossover"},"supertrend":{"confirmation_candles":1,"filter_adx":false,"adx_threshold":0}}', '2025-05-28 21:42:23', NULL, NULL),
(45, 'market_type', 'futures', '2025-05-28 21:42:23', NULL, NULL),
(46, 'integration_settings', '{"use_smart_trend":true,"smart_trend_settings":{"detection_method":"multi_timeframe","sensitivity":0.5,"lookback_period":100,"confirmation_period":3,"signal_quality_threshold":0.7}}', '2025-05-28 21:42:23', NULL, NULL),
(47, 'advanced_risk_management', '{"enabled":true,"dynamic_position_sizing":true,"position_size_method":"fixed","max_risk_per_trade":1,"volatility_based_stops":false,"adaptive_take_profit":false,"auto_adjust_risk":false,"max_open_positions":3,"reset_after_market_shift":false}', '2025-05-28 21:42:23', NULL, NULL),
(48, 'adaptive_parameters', '{"enabled":true,"adaptation_speed":0.5,"market_regime_detection":false,"volatility_adjustment":false,"trend_strength_adjustment":false,"reset_after_market_shift":false,"learning_rate":0.05,"min_history_required":100}', '2025-05-28 21:42:23', NULL, NULL),
(49, 'api_optimization', '{"enabled":true,"api_call_limit_per_minute":60,"api_call_distribution":"even","cache_duration":60,"prioritize_active_trades":false,"reduce_timeframes_count":false,"optimize_indicator_calculations":false}', '2025-05-28 21:42:23', NULL, NULL),
(50, 'telegram_enabled', '1', '2025-05-28 21:42:23', NULL, NULL),
(51, 'telegram_trade_signals', '', '2025-05-28 21:42:23', NULL, NULL),
(52, 'telegram_discovered_coins', '', '2025-05-28 21:42:23', NULL, NULL),
(53, 'telegram_position_updates', '', '2025-05-28 21:42:23', NULL, NULL),
(54, 'telegram_performance_updates', '', '2025-05-28 21:42:23', NULL, NULL),
(55, 'enable_visualization', '', '2025-05-28 21:42:23', NULL, NULL),
(56, 'auto_discovery_enabled', '1', '2025-05-28 21:42:23', NULL, NULL),
(57, 'auto_add_to_watchlist', '1', '2025-05-28 21:42:23', NULL, NULL),
(58, 'trailing_stop', '1', '2025-05-28 21:42:23', NULL, NULL),
(59, 'dynamic_position_sizing', '1', '2025-05-28 21:42:23', NULL, NULL),
(60, 'volatility_based_stops', '', '2025-05-28 21:42:23', NULL, NULL),
(61, 'adaptive_take_profit', '', '2025-05-28 21:42:23', NULL, NULL),
(62, 'auto_adjust_risk', '', '2025-05-28 21:42:23', NULL, NULL),
(63, 'market_regime_detection', '', '2025-05-28 21:42:23', NULL, NULL),
(64, 'reset_after_market_shift', '', '2025-05-28 21:42:23', NULL, NULL),
(65, 'volatility_adjustment', '', '2025-05-28 21:42:23', NULL, NULL),
(66, 'trend_strength_adjustment', '', '2025-05-28 21:42:23', NULL, NULL),
(67, 'prioritize_active_trades', '', '2025-05-28 21:42:23', NULL, NULL),
(68, 'reduce_timeframes_count', '', '2025-05-28 21:42:23', NULL, NULL),
(69, 'optimize_indicator_calculations', '', '2025-05-28 21:42:23', NULL, NULL);

--
-- Tetikleyiciler `bot_settings_individual`
--
DELIMITER $$
CREATE TRIGGER `update_bot_settings_timestamp` BEFORE UPDATE ON `bot_settings_individual`
 FOR EACH ROW SET NEW.last_updated = NOW()
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bot_status`
--

CREATE TABLE IF NOT EXISTS `bot_status` (
  `id` int(11) NOT NULL,
  `status` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4;

--
-- Tablo döküm verisi `bot_status`
--

INSERT INTO `bot_status` (`id`, `status`, `timestamp`, `details`) VALUES
(1, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:23:32", "pid": 9314}', '2025-04-26 11:23:32', NULL),
(2, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:24:38", "pid": 9314}', '2025-04-26 11:24:38', NULL),
(3, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:25:45", "pid": 9314}', '2025-04-26 11:25:45', NULL),
(4, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:26:52", "pid": 9314}', '2025-04-26 11:26:52', NULL),
(5, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:30:37", "pid": 10325}', '2025-04-26 11:30:37', NULL),
(6, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:31:43", "pid": 10325}', '2025-04-26 11:31:43', NULL),
(7, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:32:51", "pid": 10325}', '2025-04-26 11:32:51', NULL),
(8, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:33:58", "pid": 10325}', '2025-04-26 11:33:58', NULL),
(9, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:35:44", "pid": 11262}', '2025-04-26 11:35:44', NULL),
(10, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:37:15", "pid": 11448}', '2025-04-26 11:37:15', NULL),
(11, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:38:22", "pid": 11448}', '2025-04-26 11:38:22', NULL),
(12, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:39:29", "pid": 11448}', '2025-04-26 11:39:29', NULL),
(13, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:42:09", "pid": 12170}', '2025-04-26 11:42:09', NULL),
(14, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:43:59", "pid": 12625}', '2025-04-26 11:43:59', NULL),
(15, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:45:06", "pid": 12625}', '2025-04-26 11:45:06', NULL),
(16, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:46:13", "pid": 12625}', '2025-04-26 11:46:13', NULL),
(17, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:47:20", "pid": 12625}', '2025-04-26 11:47:20', NULL),
(18, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:48:26", "pid": 12625}', '2025-04-26 11:48:26', NULL),
(19, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:49:33", "pid": 12625}', '2025-04-26 11:49:33', NULL),
(20, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:50:40", "pid": 12625}', '2025-04-26 11:50:40', NULL),
(21, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:51:47", "pid": 12625}', '2025-04-26 11:51:47', NULL),
(22, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:52:53", "pid": 12625}', '2025-04-26 11:52:53', NULL),
(23, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:54:00", "pid": 12625}', '2025-04-26 11:54:00', NULL),
(24, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:55:07", "pid": 12625}', '2025-04-26 11:55:07', NULL),
(25, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:56:13", "pid": 12625}', '2025-04-26 11:56:13', NULL),
(26, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:57:20", "pid": 12625}', '2025-04-26 11:57:20', NULL),
(27, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:58:26", "pid": 12625}', '2025-04-26 11:58:26', NULL),
(28, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 11:59:33", "pid": 12625}', '2025-04-26 11:59:33', NULL),
(29, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:00:40", "pid": 12625}', '2025-04-26 12:00:40', NULL),
(30, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:01:47", "pid": 12625}', '2025-04-26 12:01:47', NULL),
(31, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:02:54", "pid": 12625}', '2025-04-26 12:02:54', NULL),
(32, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:04:00", "pid": 12625}', '2025-04-26 12:04:00', NULL),
(33, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:05:07", "pid": 12625}', '2025-04-26 12:05:07', NULL),
(34, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:06:14", "pid": 12625}', '2025-04-26 12:06:14', NULL),
(35, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:07:20", "pid": 12625}', '2025-04-26 12:07:20', NULL),
(36, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:08:27", "pid": 12625}', '2025-04-26 12:08:27', NULL),
(37, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:09:34", "pid": 12625}', '2025-04-26 12:09:34', NULL),
(38, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:10:41", "pid": 12625}', '2025-04-26 12:10:41', NULL),
(39, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:11:47", "pid": 12625}', '2025-04-26 12:11:47', NULL),
(40, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:12:54", "pid": 12625}', '2025-04-26 12:12:54', NULL),
(41, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:14:01", "pid": 12625}', '2025-04-26 12:14:01', NULL),
(42, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:15:08", "pid": 12625}', '2025-04-26 12:15:08', NULL),
(43, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:16:14", "pid": 12625}', '2025-04-26 12:16:14', NULL),
(44, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:17:21", "pid": 12625}', '2025-04-26 12:17:21', NULL),
(45, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:18:27", "pid": 12625}', '2025-04-26 12:18:27', NULL),
(46, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:19:35", "pid": 12625}', '2025-04-26 12:19:35', NULL),
(47, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:20:42", "pid": 12625}', '2025-04-26 12:20:42', NULL),
(48, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:21:48", "pid": 12625}', '2025-04-26 12:21:48', NULL),
(49, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:22:55", "pid": 12625}', '2025-04-26 12:22:55', NULL),
(50, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:24:02", "pid": 12625}', '2025-04-26 12:24:02', NULL),
(51, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:25:09", "pid": 12625}', '2025-04-26 12:25:09', NULL),
(52, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:26:15", "pid": 12625}', '2025-04-26 12:26:15', NULL),
(53, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:27:22", "pid": 12625}', '2025-04-26 12:27:22', NULL),
(54, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:28:28", "pid": 12625}', '2025-04-26 12:28:28', NULL),
(55, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:29:35", "pid": 12625}', '2025-04-26 12:29:35', NULL),
(56, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:30:44", "pid": 12625}', '2025-04-26 12:30:44', NULL),
(57, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:31:50", "pid": 12625}', '2025-04-26 12:31:50', NULL),
(58, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:32:57", "pid": 12625}', '2025-04-26 12:32:57', NULL),
(59, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:34:03", "pid": 12625}', '2025-04-26 12:34:03', NULL),
(60, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:35:10", "pid": 12625}', '2025-04-26 12:35:10', NULL),
(61, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:36:17", "pid": 12625}', '2025-04-26 12:36:17', NULL),
(62, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:37:23", "pid": 12625}', '2025-04-26 12:37:23', NULL),
(63, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:39:17", "pid": 15487}', '2025-04-26 12:39:17', NULL),
(64, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:40:24", "pid": 15487}', '2025-04-26 12:40:24', NULL),
(65, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:41:30", "pid": 15487}', '2025-04-26 12:41:30', NULL),
(66, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:42:37", "pid": 15487}', '2025-04-26 12:42:37', NULL),
(67, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:43:43", "pid": 15487}', '2025-04-26 12:43:43', NULL),
(68, '{"running": true, "active_coins_count": 30, "last_update": "2025-04-26 12:44:50", "pid": 15487}', '2025-04-26 12:44:50', NULL),
(69, 'RUNNING', '2025-05-24 19:59:45', '{"pid": 6416, "trade_mode": "live", "open_positions": 0, "auto_trade": true}'),
(70, 'RUNNING', '2025-05-24 20:21:07', '{"pid": 7241, "trade_mode": "live", "open_positions": 0, "auto_trade": true}'),
(71, 'RUNNING', '2025-05-24 20:37:04', '{"pid": 8173, "trade_mode": "live", "open_positions": 0, "auto_trade": true}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `coin_analysis`
--

CREATE TABLE IF NOT EXISTS `coin_analysis` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `price` decimal(20,8) NOT NULL,
  `rsi_value` decimal(10,2) DEFAULT NULL,
  `rsi_signal` varchar(10) DEFAULT NULL,
  `macd_value` decimal(10,4) DEFAULT NULL,
  `macd_signal_line` decimal(10,4) DEFAULT NULL,
  `macd_signal` varchar(10) DEFAULT NULL,
  `bollinger_upper` decimal(20,8) DEFAULT NULL,
  `bollinger_middle` decimal(20,8) DEFAULT NULL,
  `bollinger_lower` decimal(20,8) DEFAULT NULL,
  `bollinger_signal` varchar(10) DEFAULT NULL,
  `ma20` decimal(20,8) DEFAULT NULL,
  `ma50` decimal(20,8) DEFAULT NULL,
  `ma100` decimal(20,8) DEFAULT NULL,
  `ma200` decimal(20,8) DEFAULT NULL,
  `ma_signal` varchar(10) DEFAULT NULL,
  `tradingview_recommend` decimal(10,4) DEFAULT NULL,
  `tradingview_signal` varchar(10) DEFAULT NULL,
  `overall_signal` varchar(10) DEFAULT NULL,
  `trade_signal` varchar(10) DEFAULT NULL,
  `reason` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `indicators_json` longtext
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `discovered_coins`
--

CREATE TABLE IF NOT EXISTS `discovered_coins` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `discovery_time` datetime DEFAULT NULL,
  `price` decimal(20,8) NOT NULL,
  `volume_usd` decimal(20,2) DEFAULT NULL,
  `price_change_pct` decimal(10,2) DEFAULT NULL,
  `buy_signals` int(11) DEFAULT NULL,
  `sell_signals` int(11) DEFAULT NULL,
  `trade_signal` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `notes` text,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `direction` varchar(10) DEFAULT 'NEUTRAL',
  `signal_strength` varchar(10) DEFAULT 'WEAK',
  `risk_level` varchar(10) DEFAULT 'medium',
  `recommended_leverage` int(11) DEFAULT '5',
  `timeframe` varchar(10) DEFAULT '5m',
  `timeframes` text
) ENGINE=InnoDB AUTO_INCREMENT=569 DEFAULT CHARSET=utf8;

--
-- Tablo döküm verisi `discovered_coins`
--

INSERT INTO `discovered_coins` (`id`, `symbol`, `discovery_time`, `price`, `volume_usd`, `price_change_pct`, `buy_signals`, `sell_signals`, `trade_signal`, `is_active`, `notes`, `last_updated`, `direction`, `signal_strength`, `risk_level`, `recommended_leverage`, `timeframe`, `timeframes`) VALUES
(1, 'ETH/USDT', '2025-06-01 08:10:44', '2521.36000000', '6687476599.19', '6.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:12:27', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(2, 'AAVE/USDT', '2025-05-31 18:12:58', '249.08000000', '53177202.62', '78.50', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:13:48', 'SHORT', 'MODERATE', 'medium', 5, '5m', NULL),
(3, 'SANTOS/USDT', '2025-05-25 21:10:56', '2.52800000', '2119044.11', '164.90', 1, 0, 'BUY', 1, NULL, '2025-05-25 18:11:53', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(4, 'STPT/USDT', '2025-05-17 11:58:10', '0.07079000', '1334724.88', '182.70', 1, 0, 'BUY', 1, NULL, '2025-05-17 08:58:35', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(5, 'CITY/USDT', '2025-05-23 21:31:28', '1.13900000', '716914.01', '-155.60', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-23 21:31:28', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(6, 'BTTC/USDT', '2025-05-23 21:08:25', '0.00000072', '2491943.96', '-526.30', 3, 2, 'BUY', 1, NULL, '2025-05-23 21:08:25', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(7, 'ALPINE/USDT', '2025-05-25 10:36:25', '0.92100000', '1040996.61', '120.90', 1, 0, 'BUY', 1, NULL, '2025-05-25 07:37:36', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(8, 'ASR/USDT', '2025-05-31 00:16:12', '2.13500000', '5539917.29', '32.90', 1, 0, 'BUY', 1, NULL, '2025-05-30 21:16:58', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(9, 'LAYER/USDT', '2025-06-01 05:53:02', '0.77520000', '40394812.44', '23.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 02:54:18', 'LONG', 'MODERATE', 'medium', 5, '5m', NULL),
(10, 'JUV/USDT', '2025-05-25 20:54:07', '1.22300000', '1695566.76', '16.40', 1, 0, 'BUY', 1, NULL, '2025-05-25 17:54:45', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(11, 'OG/USDT', '2025-05-26 01:51:43', '5.43100000', '3833759.19', '243.30', 1, 0, 'BUY', 1, NULL, '2025-05-25 22:53:24', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(12, 'ACM/USDT', '2025-05-26 11:26:13', '0.93400000', '10120969.35', '119.20', 1, 0, 'BUY', 1, NULL, '2025-05-26 08:27:55', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(13, 'ATM/USDT', '2025-05-25 20:17:10', '1.23600000', '1434028.50', '106.30', 1, 0, 'BUY', 1, NULL, '2025-05-25 17:18:08', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(14, 'LAZIO/USDT', '2025-05-24 19:09:27', '1.04700000', '431788.73', '-205.80', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-24 19:09:27', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(15, 'SUN/USDT', '2025-05-25 13:06:17', '0.01959000', '729103.25', '35.90', 1, 0, 'BUY', 1, NULL, '2025-05-25 10:07:07', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(16, 'STRAX/USDT', '2025-05-24 19:11:15', '0.05390000', '3274931.21', '200.60', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-24 19:11:15', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(17, 'JOE/USDT', '2025-06-01 00:39:22', '0.16416000', '3017440.24', '128.30', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:40:02', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(18, 'USTC/USDT', '2025-05-24 18:39:16', '0.01288000', '838592.19', '-197.90', 1, 3, 'SELL', 1, NULL, '2025-05-24 18:39:16', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(19, 'TST/USDT', '2025-06-01 15:16:51', '0.04540000', '72538494.05', '1552.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:18:09', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(20, 'ZEC/USDT', '2025-06-01 15:03:01', '51.98000000', '17977563.12', '1214.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:04:22', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(21, 'SFP/USDT', '2025-05-25 06:56:34', '0.56140000', '426918.01', '218.40', 1, 0, 'BUY', 1, NULL, '2025-05-25 03:57:31', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(22, 'ZK/USDT', '2025-06-01 11:00:36', '0.05299000', '6597555.75', '149.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 08:01:55', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(23, 'FTT/USDT', '2025-05-25 08:13:02', '1.17900000', '1272488.54', '13.60', 1, 0, 'BUY', 1, NULL, '2025-05-25 05:14:39', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(24, 'REQ/USDT', '2025-05-25 01:29:39', '0.15010000', '359218.30', '46.90', 1, 0, 'BUY', 1, NULL, '2025-05-24 22:31:55', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(25, 'SOLV/USDT', '2025-06-01 16:06:58', '0.04481000', '116837655.02', '367.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:07:46', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(26, 'PHA/USDT', '2025-05-31 18:12:48', '0.14590000', '22074318.88', '209.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:13:48', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(27, 'BONK/USDT', '2025-05-26 11:23:15', '0.00002133', '33819150.97', '611.90', 1, 0, 'BUY', 1, NULL, '2025-05-26 08:24:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(28, 'ENJ/USDT', '2025-05-24 16:09:45', '0.08650000', '1767056.10', '-494.50', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-24 16:09:45', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(29, 'ANIME/USDT', '2025-06-01 16:04:27', '0.02504000', '22068565.70', '788.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:05:36', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(30, 'LQTY/USDT', '2025-06-01 16:18:16', '0.81680000', '21547276.59', '965.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:19:40', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(31, 'PARTI/USDT', '2025-06-01 15:56:42', '0.22930000', '31388064.24', '528.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(32, 'ONDO/USDT', '2025-06-01 14:57:20', '0.81770000', '69721756.21', '166.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:58:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(33, 'ZRO/USDT', '2025-06-01 14:32:50', '2.24360000', '14265605.96', '182.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:34:15', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(34, 'BMT/USDT', '2025-06-01 14:45:01', '0.08610000', '14153693.24', '461.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:46:40', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(35, 'FORM/USDT', '2025-06-01 00:25:12', '2.87750000', '10340725.50', '193.80', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:26:16', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(36, 'MBOX/USDT', '2025-05-24 05:51:56', '0.06040000', '2661065.38', '-1296.80', 0, 2, 'SELL', 1, NULL, '2025-05-24 05:51:56', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(37, 'TURBO/USDT', '2025-06-01 15:25:41', '0.00420040', '34286547.25', '374.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(38, 'LISTA/USDT', '2025-05-26 11:26:17', '0.29450000', '15553931.37', '728.60', 1, 0, 'BUY', 1, NULL, '2025-05-26 08:27:55', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(39, 'TRX/USDT', '2025-06-01 09:58:45', '0.26894000', '80303101.73', '18.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 07:00:15', 'LONG', 'MODERATE', 'medium', 5, '5m', NULL),
(40, 'RUNE/USDT', '2025-05-26 10:58:43', '1.99600000', '24660681.05', '859.60', 1, 0, 'BUY', 1, NULL, '2025-05-26 08:00:22', 'SHORT', 'MODERATE', 'medium', 5, '5m', NULL),
(41, 'HYPER/USDT', '2025-06-01 15:53:52', '0.12700000', '9538260.46', '127.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:55:28', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(42, 'OM/USDT', '2025-06-01 02:35:07', '0.30871000', '143602172.30', '336.50', 1, 0, 'BUY', 1, NULL, '2025-05-31 23:36:22', 'SHORT', 'MODERATE', 'medium', 5, '5m', NULL),
(43, 'BTC/USDT', '2025-06-01 15:19:10', '104068.20000000', '9067101522.61', '30.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(44, 'TAO/USDT', '2025-06-01 16:08:55', '413.63000000', '270649511.06', '188.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:10:06', 'SHORT', 'MODERATE', 'medium', 5, '5m', NULL),
(45, 'RAD/USDT', '2025-05-23 20:30:32', '0.79600000', '1473370.38', '-62.40', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-23 20:30:32', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(46, 'CAKE/USDT', '2025-06-01 15:59:25', '2.28480000', '26736415.02', '11.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:00:57', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(47, 'BIO/USDT', '2025-06-01 15:05:33', '0.06569000', '15699151.17', '593.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:07:11', 'SHORT', 'STRONG', 'medium', 5, '5m', NULL),
(48, 'CELO/USDT', '2025-05-26 14:36:33', '0.37640000', '21173691.12', '423.70', 1, 0, 'BUY', 1, NULL, '2025-05-26 11:38:20', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(49, 'PAXG/USDT', '2025-06-01 15:33:51', '3305.69000000', '17476249.70', '10.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:35:04', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(50, 'ME/USDT', '2025-05-24 12:55:21', '0.99500000', '2103982.22', '-79.80', 0, 0, 'NEUTRAL', 1, NULL, '2025-05-24 12:55:21', 'LONG', 'STRONG', 'medium', 5, '5m', NULL),
(407, 'AMP/USDT', '2025-05-31 17:09:31', '0.00427700', '8515641.96', '631.40', 1, 0, 'BUY', 1, NULL, '2025-05-31 14:10:21', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(408, 'SLF/USDT', '2025-05-31 17:26:46', '0.17770000', '11348947.25', '1391.00', 1, 0, 'BUY', 1, NULL, '2025-05-31 14:27:42', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(409, 'KAVA/USDT', '2025-05-31 17:11:45', '0.41930000', '7466216.91', '373.60', 1, 0, 'BUY', 1, NULL, '2025-05-31 14:12:21', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(410, 'BIFI/USDT', '2025-05-31 18:02:46', '202.20000000', '18301817.08', '2412.50', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:03:28', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(411, 'NEXO/USDT', '2025-05-31 17:17:17', '1.23100000', '893852.23', '81.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 14:18:07', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(412, 'IOTX/USDT', '2025-06-01 14:59:56', '0.02232000', '23293166.75', '295.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(413, 'COOKIE/USDT', '2025-05-31 18:02:45', '0.23170000', '15317742.07', '113.50', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:03:28', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(414, 'DGB/USDT', '2025-05-31 17:38:55', '0.00925000', '1288734.25', '209.70', 1, 0, 'BUY', 1, NULL, '2025-05-31 14:39:35', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(415, 'QNT/USDT', '2025-06-01 15:49:44', '110.25000000', '14918284.59', '457.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:50:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(416, 'AWE/USDT', '2025-05-31 18:18:42', '0.05922000', '2236110.41', '242.10', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:19:27', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(417, 'PENDLE/USDT', '2025-05-31 18:02:24', '4.12500000', '15824953.79', '97.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:03:28', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(418, 'BANANAS31/USDT', '2025-06-01 07:14:32', '0.00632100', '17733051.11', '28.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 04:16:03', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(419, 'BCH/USDT', '2025-05-31 19:04:46', '417.90000000', '19914325.08', '295.60', 1, 0, 'BUY', 1, NULL, '2025-05-31 16:05:01', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(420, 'EOS/USDT', '2025-05-31 18:08:25', '0.77990000', '9968514.41', '887.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:09:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(421, 'ICX/USDT', '2025-05-31 18:10:42', '0.12110000', '7302031.83', '8.30', 1, 0, 'BUY', 1, NULL, '2025-05-31 15:11:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(422, 'VIRTUAL/USDT', '2025-05-31 19:04:19', '2.05310000', '90154995.72', '26.40', 1, 0, 'BUY', 1, NULL, '2025-05-31 16:05:01', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(423, 'DEXE/USDT', '2025-06-01 16:09:12', '14.29600000', '9028375.18', '68.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:10:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(424, 'XRP/USDT', '2025-06-01 14:59:48', '2.13970000', '856731490.58', '8.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(425, 'HBAR/USDT', '2025-06-01 15:25:41', '0.16564000', '45478102.38', '207.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(426, 'SPELL/USDT', '2025-06-01 11:00:37', '0.00052160', '49041844.71', '63.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 08:01:55', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(427, 'DOT/USDT', '2025-06-01 14:54:03', '4.02300000', '78251434.73', '128.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:55:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(428, 'ALGO/USDT', '2025-06-01 14:57:21', '0.19210000', '18676971.59', '126.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:58:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(429, 'YFI/USDT', '2025-06-01 00:41:19', '5222.00000000', '7402195.20', '50.00', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:42:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(430, 'GPS/USDT', '2025-06-01 01:30:19', '0.02250000', '5307622.62', '786.20', 1, 0, 'BUY', 1, NULL, '2025-05-31 22:31:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(431, 'XVG/USDT', '2025-06-01 15:08:18', '0.00660720', '18137497.21', '707.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:09:59', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(432, 'TRUMP/USDT', '2025-06-01 15:19:31', '11.19900000', '318312182.60', '359.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(433, 'RENDER/USDT', '2025-06-01 14:54:32', '3.83700000', '35836573.22', '273.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:55:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(434, 'LINK/USDT', '2025-06-01 14:56:56', '13.80500000', '185781360.46', '109.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:58:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(435, 'PNUT/USDT', '2025-06-01 14:59:49', '0.25732000', '110778438.18', '438.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(436, 'POL/USDT', '2025-06-01 12:10:39', '0.21189000', '13600203.52', '212.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 09:12:13', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(437, 'BEAMX/USDT', '2025-05-31 19:58:16', '0.00643800', '10029257.92', '31.20', 1, 0, 'BUY', 1, NULL, '2025-05-31 16:59:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(438, 'AIXBT/USDT', '2025-06-01 16:21:10', '0.19208000', '89322816.15', '386.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:22:21', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(439, 'MKR/USDT', '2025-06-01 09:48:10', '1571.70000000', '17111258.22', '107.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 06:49:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(440, 'CGPT/USDT', '2025-06-01 01:18:03', '0.11650000', '6974206.87', '76.10', 1, 0, 'BUY', 1, NULL, '2025-05-31 22:19:05', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(441, 'HOT/USDT', '2025-05-31 20:23:06', '0.00098200', '4534427.34', '20.40', 1, 0, 'BUY', 1, NULL, '2025-05-31 17:24:10', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(442, 'VET/USDT', '2025-06-01 10:41:31', '0.02405300', '13999004.06', '207.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 07:43:03', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(443, 'NEIRO/USDT', '2025-06-01 14:44:56', '0.00043820', '221641299.94', '274.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:46:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(444, 'THETA/USDT', '2025-06-01 14:01:36', '0.74150000', '14375758.69', '154.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:03:05', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(445, 'LTC/USDT', '2025-06-01 14:45:06', '86.94000000', '125882247.95', '240.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:46:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(446, 'COMP/USDT', '2025-05-31 20:10:52', '40.55000000', '16463477.20', '47.10', 1, 0, 'BUY', 1, NULL, '2025-05-31 17:11:41', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(447, 'VTHO/USDT', '2025-06-01 00:31:03', '0.00215800', '4818273.20', '107.70', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:32:14', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(448, 'VANA/USDT', '2025-06-01 16:13:39', '6.48900000', '41224465.19', '786.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:14:44', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(449, 'BSV/USDT', '2025-05-31 20:16:53', '33.61000000', '4295619.40', '14.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 17:17:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(450, 'JTO/USDT', '2025-06-01 08:08:11', '1.65930000', '15733307.36', '335.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:09:43', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(451, 'TNSR/USDT', '2025-06-01 16:06:40', '0.12810000', '29318183.69', '215.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:07:46', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(452, 'GRT/USDT', '2025-06-01 13:12:35', '0.09350000', '13944336.70', '80.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:13:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(453, 'CHR/USDT', '2025-05-31 20:25:31', '0.08450000', '4761415.10', '11.80', 1, 0, 'BUY', 1, NULL, '2025-05-31 17:26:23', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(454, 'PERP/USDT', '2025-06-01 16:16:12', '0.24040000', '2220815.59', '58.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:16:59', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(455, 'ACT/USDT', '2025-06-01 13:39:44', '0.05105000', '15530296.72', '25.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:41:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(456, 'BANANA/USDT', '2025-06-01 00:21:02', '22.15300000', '16947508.66', '280.30', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:22:15', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(457, 'PYTH/USDT', '2025-06-01 11:27:49', '0.11768000', '22502559.52', '439.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 08:29:14', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(458, 'KAITO/USDT', '2025-06-01 15:11:17', '1.90450000', '133082099.28', '155.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:12:41', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(459, 'FIL/USDT', '2025-06-01 15:00:16', '2.53700000', '91585018.93', '143.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(460, '1000SATS/USDT', '2025-06-01 16:18:10', '0.00004540', '23239435.24', '111.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:19:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(461, 'EDU/USDT', '2025-06-01 00:55:10', '0.13490000', '5397529.31', '120.00', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:55:34', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(462, 'PROM/USDT', '2025-05-31 23:59:09', '5.46300000', '5289381.66', '156.20', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:00:04', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(463, 'ACH/USDT', '2025-06-01 13:15:15', '0.02131800', '9029218.50', '78.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:16:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(464, 'BEL/USDT', '2025-06-01 00:03:29', '0.28220000', '7855412.15', '14.20', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:04:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(465, 'FORTH/USDT', '2025-06-01 00:05:22', '2.34000000', '7107352.02', '103.60', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:06:16', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(466, 'AXL/USDT', '2025-06-01 00:25:18', '0.32010000', '3634362.67', '50.20', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:26:16', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(467, 'DEGO/USDT', '2025-06-01 01:56:36', '2.64060000', '12608911.68', '354.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 22:58:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(468, 'TUT/USDT', '2025-06-01 15:33:26', '0.02683000', '66157700.14', '1850.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:35:04', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(469, 'FIDA/USDT', '2025-06-01 13:18:15', '0.06844000', '8347712.79', '145.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:19:27', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(470, 'CHZ/USDT', '2025-06-01 00:50:03', '0.03940000', '15980460.42', '22.90', 1, 0, 'BUY', 1, NULL, '2025-05-31 21:51:20', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(471, 'ATOM/USDT', '2025-06-01 15:25:27', '4.28000000', '25056726.80', '68.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(472, 'BNT/USDT', '2025-06-01 01:04:57', '0.64546000', '2802048.24', '40.30', 1, 0, 'BUY', 1, NULL, '2025-05-31 22:05:50', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(473, 'ZEN/USDT', '2025-06-01 15:00:09', '10.07200000', '197071100.09', '2045.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(474, 'REI/USDT', '2025-06-01 04:28:59', '0.01683000', '19409169.78', '268.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 01:30:36', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(475, 'ORDI/USDT', '2025-06-01 15:56:58', '8.37700000', '63717619.43', '186.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(476, 'CETUS/USDT', '2025-06-01 15:22:04', '0.13451000', '23837847.76', '105.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:24:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(477, 'BROCCOLI714/USDT', '2025-06-01 12:13:22', '0.02607000', '5555565.68', '235.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 09:14:55', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(478, 'EIGEN/USDT', '2025-06-01 15:16:53', '1.31660000', '57520354.55', '227.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:18:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(479, 'MEME/USDT', '2025-06-01 15:56:59', '0.00187000', '14394196.55', '314.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(480, 'AVAX/USDT', '2025-06-01 15:02:40', '20.38200000', '155673810.36', '100.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:04:22', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(481, 'ARPA/USDT', '2025-06-01 04:15:08', '0.02202000', '32423183.93', '218.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 01:16:43', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(482, 'ZRX/USDT', '2025-06-01 13:55:51', '0.23040000', '10718398.97', '34.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:57:22', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(483, 'WLD/USDT', '2025-06-01 07:25:47', '1.12460000', '164112551.16', '233.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 04:27:22', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(484, 'LDO/USDT', '2025-06-01 14:41:24', '0.85240000', '70655469.60', '532.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:43:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(485, 'BABY/USDT', '2025-06-01 14:15:32', '0.06474000', '14084167.90', '649.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:17:07', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(486, 'STRK/USDT', '2025-06-01 13:01:18', '0.13130000', '11740241.47', '162.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:03:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(487, 'BERA/USDT', '2025-06-01 13:58:45', '2.26200000', '29302305.68', '277.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:00:19', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(488, 'ORCA/USDT', '2025-06-01 09:26:34', '2.77400000', '31632643.76', '331.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 06:28:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(489, 'ADA/USDT', '2025-06-01 14:54:03', '0.66310000', '347269680.37', '6.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:55:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(490, 'ENA/USDT', '2025-06-01 14:51:08', '0.30700000', '200701083.64', '288.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:53:01', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(491, 'PEOPLE/USDT', '2025-06-01 15:19:19', '0.02008000', '45233673.77', '313.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(492, 'SEI/USDT', '2025-06-01 14:32:50', '0.19060000', '27055514.41', '176.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:34:15', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(493, 'TON/USDT', '2025-06-01 15:08:29', '3.11160000', '64189380.03', '156.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:09:59', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(494, 'SOL/USDT', '2025-06-01 08:48:48', '155.03000000', '1931172800.78', '19.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:50:36', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(495, 'BNB/USDT', '2025-06-01 08:35:03', '655.78000000', '212148175.26', '27.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:36:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(496, 'ARB/USDT', '2025-06-01 08:08:24', '0.34160000', '75598272.08', '326.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:09:43', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(497, 'HAEDAL/USDT', '2025-06-01 15:25:32', '0.12913300', '41370545.68', '854.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(498, 'BOME/USDT', '2025-06-01 15:25:35', '0.00178800', '32813490.27', '377.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(499, 'INIT/USDT', '2025-06-01 15:13:55', '0.74520000', '62653961.77', '905.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:15:23', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(500, 'SAGA/USDT', '2025-06-01 03:04:31', '0.29150000', '19275196.08', '253.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 00:05:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(501, 'AXS/USDT', '2025-06-01 12:10:41', '2.50000000', '10171908.60', '68.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 09:12:13', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(502, '1MBABYDOGE/USDT', '2025-06-01 03:04:17', '0.00138620', '12599832.47', '368.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 00:05:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(503, 'DF/USDT', '2025-06-01 13:31:17', '0.04516000', '35886417.50', '1044.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:32:58', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(504, 'KERNEL/USDT', '2025-06-01 14:23:59', '0.15760000', '27510798.66', '1273.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:25:41', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(505, 'INJ/USDT', '2025-06-01 05:52:57', '11.98000000', '96298948.65', '256.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 02:54:18', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(506, 'MUBARAK/USDT', '2025-06-01 15:25:20', '0.03841000', '38490190.28', '177.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(507, 'NIL/USDT', '2025-06-01 15:19:27', '0.44800000', '17800004.12', '583.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(508, 'USUAL/USDT', '2025-06-01 15:25:33', '0.10170000', '21192162.81', '231.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(509, 'XAI/USDT', '2025-06-01 03:42:24', '0.07064000', '12836432.84', '707.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 00:43:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(510, 'SYRUP/USDT', '2025-06-01 14:15:27', '0.33966000', '50815447.63', '590.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:17:07', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(511, 'S/USDT', '2025-06-01 14:54:30', '0.38770000', '25053387.35', '128.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:55:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(512, 'IO/USDT', '2025-06-01 08:02:46', '0.81780000', '12417646.75', '102.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:04:13', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(513, 'CATI/USDT', '2025-06-01 04:06:57', '0.10050000', '26154595.52', '90.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 01:08:08', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(514, 'NOT/USDT', '2025-06-01 15:36:33', '0.00218400', '17366966.46', '115.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:37:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(515, 'A/USDT', '2025-06-01 04:09:24', '0.61416000', '39186682.96', '224.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 01:10:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(516, 'SUI/USDT', '2025-06-01 14:41:23', '3.25960000', '570692651.96', '418.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:43:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(517, 'UNI/USDT', '2025-06-01 14:48:11', '6.22700000', '195670630.56', '369.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:50:03', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(518, 'NEAR/USDT', '2025-06-01 15:00:15', '2.39100000', '88043819.43', '58.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:01:37', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(519, 'XLM/USDT', '2025-06-01 13:40:07', '0.26253000', '48094126.76', '94.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:41:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(520, 'PENGU/USDT', '2025-06-01 15:08:30', '0.01028100', '46727982.75', '539.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:09:59', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(521, 'DYDX/USDT', '2025-06-01 14:32:38', '0.54400000', '36936998.54', '481.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:34:15', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(522, 'SSV/USDT', '2025-06-01 16:11:19', '8.53300000', '28222549.00', '259.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:12:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(523, 'APT/USDT', '2025-06-01 14:51:30', '4.69920000', '56284316.34', '207.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:53:01', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(524, 'MASK/USDT', '2025-06-01 15:59:27', '2.12990000', '86665907.33', '591.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:00:57', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(525, 'FLOW/USDT', '2025-06-01 08:19:16', '0.36700000', '12947419.58', '309.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 05:20:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(526, 'OP/USDT', '2025-06-01 11:43:40', '0.65200000', '109044372.68', '359.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 08:45:19', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(527, 'ETHFI/USDT', '2025-06-01 14:41:17', '1.11650000', '105130791.22', '169.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:43:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(528, 'PUNDIX/USDT', '2025-06-01 12:10:41', '0.32420000', '12737922.63', '233.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 09:12:13', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(529, 'EPIC/USDT', '2025-06-01 05:20:09', '1.20650000', '9153043.63', '1184.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 02:21:53', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(530, 'GUN/USDT', '2025-06-01 12:10:29', '0.03961000', '11179283.14', '476.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 09:12:13', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(531, 'WIF/USDT', '2025-06-01 15:25:25', '0.82270000', '327418800.47', '340.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(532, 'ETC/USDT', '2025-06-01 14:51:20', '16.79800000', '44057556.49', '41.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:53:01', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(533, 'FET/USDT', '2025-06-01 14:38:36', '0.74060000', '74476898.10', '313.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:40:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(534, 'VOXEL/USDT', '2025-06-01 06:30:37', '0.06061000', '13846297.29', '792.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 03:32:25', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(535, 'SXT/USDT', '2025-06-01 13:01:42', '0.10036000', '13237122.06', '10.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:03:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(536, 'DOGE/USDT', '2025-06-01 15:05:45', '0.18856000', '895909004.93', '93.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:07:11', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(537, 'CRV/USDT', '2025-06-01 15:19:26', '0.65700000', '66126422.67', '138.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(538, 'APE/USDT', '2025-06-01 14:18:11', '0.62160000', '30910164.59', '396.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:20:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(539, 'ENS/USDT', '2025-06-01 14:48:21', '20.37800000', '24656620.98', '22.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:50:03', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(540, 'SAND/USDT', '2025-06-01 07:25:55', '0.27106000', '23536971.03', '158.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 04:27:22', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(541, 'TIA/USDT', '2025-06-01 13:01:17', '2.16380000', '73888703.27', '119.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:03:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(542, 'HUMA/USDT', '2025-06-01 14:21:25', '0.03642100', '86933967.17', '66.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:22:48', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(543, 'ARKM/USDT', '2025-06-01 15:05:30', '0.53820000', '15622446.86', '204.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:07:11', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(544, 'AUCTION/USDT', '2025-06-01 13:15:09', '10.27500000', '10468147.05', '209.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:16:45', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(545, 'DOGS/USDT', '2025-06-01 15:54:12', '0.00014670', '10944844.97', '47.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:55:28', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(546, 'MOVE/USDT', '2025-06-01 14:48:28', '0.13690000', '26308550.68', '73.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:50:03', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(547, 'ICP/USDT', '2025-06-01 14:41:15', '4.87500000', '21685339.04', '365.70', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:43:09', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(548, 'GALA/USDT', '2025-06-01 15:19:18', '0.01647000', '41511393.61', '198.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:21:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(549, 'NXPC/USDT', '2025-06-01 11:19:29', '1.38309000', '163789017.35', '127.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 08:20:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(550, 'STX/USDT', '2025-06-01 14:12:59', '0.72510000', '17121459.17', '63.80', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:14:16', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(551, 'SIGN/USDT', '2025-06-01 15:54:05', '0.07608000', '21631439.81', '239.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:55:28', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(552, 'KMNO/USDT', '2025-06-01 14:18:22', '0.05324000', '11127027.03', '81.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:20:00', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(553, 'OGN/USDT', '2025-06-01 13:09:46', '0.05700000', '11713306.37', '35.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:11:06', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(554, 'REZ/USDT', '2025-06-01 15:59:38', '0.01116000', '9065971.60', '163.90', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:00:57', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(555, 'SOPH/USDT', '2025-06-01 14:44:54', '0.05160100', '221812702.87', '339.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:46:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(556, 'XTZ/USDT', '2025-06-01 13:36:53', '0.55700000', '7993337.58', '54.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 10:38:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(557, 'PORTAL/USDT', '2025-06-01 14:27:12', '0.04790000', '11644373.33', '257.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:28:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(558, 'JUP/USDT', '2025-06-01 15:25:28', '0.51420000', '32778448.18', '106.10', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:27:02', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(559, 'IMX/USDT', '2025-06-01 14:24:13', '0.54190000', '10840333.85', '121.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:25:41', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(560, 'ALICE/USDT', '2025-06-01 14:27:03', '0.39500000', '10861465.91', '76.50', 1, 0, 'BUY', 1, NULL, '2025-06-01 11:28:32', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(561, 'TRB/USDT', '2025-06-01 16:16:14', '43.62300000', '364798774.41', '175.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:16:59', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(562, 'RPL/USDT', '2025-06-01 15:49:34', '4.76800000', '12152078.10', '129.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:50:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(563, 'GTC/USDT', '2025-06-01 15:49:39', '0.25200000', '2469377.52', '202.40', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:50:49', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(564, 'MANTA/USDT', '2025-06-01 15:56:50', '0.23690000', '10094456.16', '98.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(565, '1000CAT/USDT', '2025-06-01 15:56:38', '0.00707000', '5157711.75', '58.30', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(566, 'RDNT/USDT', '2025-06-01 15:56:44', '0.02363000', '2731997.54', '115.60', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(567, 'ILV/USDT', '2025-06-01 15:56:57', '12.70200000', '2478958.81', '172.20', 1, 0, 'BUY', 1, NULL, '2025-06-01 12:58:17', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL),
(568, 'NFP/USDT', '2025-06-01 16:18:17', '0.07140000', '3835889.08', '99.00', 1, 0, 'BUY', 1, NULL, '2025-06-01 13:19:40', 'NEUTRAL', 'WEAK', 'medium', 5, '5m', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(20) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `error_message` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notification_settings`
--

CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `telegram_token` varchar(255) DEFAULT NULL,
  `telegram_chat_id` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `settings` text
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `user_id`, `telegram_token`, `telegram_chat_id`, `enabled`, `created_at`, `updated_at`, `settings`) VALUES
(1, NULL, '', '', 1, '2025-05-28 21:24:11', NULL, '{"rate_limit": 30}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `open_positions`
--

CREATE TABLE IF NOT EXISTS `open_positions` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `position_type` varchar(10) NOT NULL DEFAULT 'LONG',
  `entry_price` decimal(16,8) NOT NULL,
  `quantity` decimal(16,8) NOT NULL,
  `take_profit_price` decimal(16,8) DEFAULT NULL,
  `stop_loss_price` decimal(16,8) DEFAULT NULL,
  `trade_time` datetime NOT NULL,
  `status` enum('OPEN','CLOSED','CANCELLED') DEFAULT 'OPEN',
  `close_time` datetime DEFAULT NULL,
  `close_price` decimal(16,8) DEFAULT NULL,
  `profit_loss` decimal(16,8) DEFAULT NULL,
  `profit_loss_percent` decimal(7,2) DEFAULT NULL,
  `strategy` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `entry_time` datetime DEFAULT NULL,
  `exit_time` datetime DEFAULT NULL,
  `exit_price` decimal(20,8) DEFAULT NULL,
  `profit_loss_pct` decimal(10,2) DEFAULT NULL,
  `close_reason` varchar(50) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL,
  `stop_loss` decimal(20,8) DEFAULT NULL,
  `take_profit` decimal(20,8) DEFAULT NULL,
  `trade_mode` varchar(20) DEFAULT 'paper'
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `open_positions`
--

INSERT INTO `open_positions` (`id`, `symbol`, `position_type`, `entry_price`, `quantity`, `take_profit_price`, `stop_loss_price`, `trade_time`, `status`, `close_time`, `close_price`, `profit_loss`, `profit_loss_percent`, `strategy`, `notes`, `created_at`, `updated_at`, `entry_time`, `exit_time`, `exit_price`, `profit_loss_pct`, `close_reason`, `last_updated`, `stop_loss`, `take_profit`, `trade_mode`) VALUES
(1, 'TRX/USDT', 'LONG', '0.27370000', '20.09499452', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -0.11%', '2025-05-26 11:36:07', NULL, '2025-05-26 14:36:07', '2025-05-27 01:01:25', '0.27340000', '-0.11', 'manual_required', '2025-05-26 23:45:00', '0.26008700', '0.30107000', 'paper'),
(2, 'VIRTUAL/USDT', 'LONG', '2.22630000', '2.47046669', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -5.13%', '2025-05-26 11:36:46', NULL, '2025-05-26 14:36:46', '2025-05-26 23:04:54', '2.11220000', '-5.13', 'manual_required', '2025-05-26 12:59:45', '2.11520500', '2.44893000', 'paper'),
(3, 'SHIB/USDT', 'LONG', '0.00001445', '76124.56747405', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -0.76%', '2025-05-26 11:37:06', NULL, '2025-05-26 14:37:06', '2025-05-27 00:49:31', '0.00001434', '-0.76', 'manual_required', '2025-05-26 12:59:46', '0.00001373', '0.00001590', 'paper'),
(4, 'PEPE/USDT', 'LONG', '0.00001412', '77903.68271955', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -2.62%', '2025-05-26 11:37:46', NULL, '2025-05-26 14:37:46', '2025-05-26 23:20:34', '0.00001375', '-2.62', 'manual_required', NULL, '0.00001341', '0.00001553', 'paper'),
(5, 'RUNE/USDT', 'LONG', '1.97300000', '2.78763305', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -3.14%', '2025-05-26 11:38:06', NULL, '2025-05-26 14:38:06', '2025-05-26 23:20:54', '1.91100000', '-3.14', 'manual_required', '2025-05-26 12:59:46', '1.87525000', '2.17030000', 'paper'),
(6, 'POL/USDT', 'LONG', '0.23770000', '23.13840976', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -2.82%', '2025-05-26 11:39:24', NULL, '2025-05-26 14:39:24', '2025-05-27 00:26:35', '0.23100000', '-2.82', 'manual_required', '2025-05-26 12:59:46', '0.22587300', '0.26147000', 'paper'),
(7, 'SYRUP/USDT', 'LONG', '0.44650000', '12.31802912', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'breakout_detection', 'manual_required - -5.71%', '2025-05-26 11:40:43', NULL, '2025-05-26 14:40:43', '2025-05-26 22:26:21', '0.42100000', '-5.71', 'manual_required', NULL, '0.42417500', '0.49115000', 'paper'),
(8, 'EPIC/USDT', 'LONG', '1.43200000', '3.84078212', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -3.49%', '2025-05-26 11:40:44', NULL, '2025-05-26 14:40:44', '2025-05-26 23:23:37', '1.38200000', '-3.49', 'manual_required', NULL, '1.36040000', '1.57520000', 'paper'),
(9, 'JUV/USDT', 'LONG', '1.17900000', '4.66497031', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - +0.51%', '2025-05-26 11:40:45', NULL, '2025-05-26 14:40:45', '2025-05-26 23:23:57', '1.18500000', '0.51', 'manual_required', '2025-05-26 23:18:51', '1.12187000', '1.29690000', 'paper'),
(10, 'CVC/USDT', 'LONG', '0.14420000', '38.14147018', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'manual_required - -2.91%', '2025-05-26 11:42:24', NULL, '2025-05-26 14:42:24', '2025-05-27 00:17:05', '0.14000000', '-2.91', 'manual_required', '2025-05-26 12:59:48', '0.13704800', '0.15862000', 'paper'),
(11, 'SLF/USDT', 'LONG', '0.17730000', '0.00849236', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'breakout_detection', 'Risk yönetimli aç?l??: 2025-05-31 18:44:25 - Risk: NORMAL - SL: 0.160488 - TP: 0.210924', '2025-05-31 15:44:25', NULL, '2025-05-31 18:44:25', NULL, NULL, NULL, 'delisted', '2025-05-31 15:44:59', '0.16048786', '0.21092428', 'paper'),
(12, 'NEXO/USDT', 'LONG', '1.23500000', '0.00152398', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'breakout_detection', 'Risk yönetimli aç?l??: 2025-05-31 18:58:29 - Risk: NORMAL - SL: 1.209257 - TP: 1.286486', '2025-05-31 15:58:29', NULL, '2025-05-31 18:58:29', NULL, NULL, NULL, NULL, '2025-05-31 16:14:32', '1.20925700', '1.28648601', 'paper'),
(13, 'QNT/USDT', 'LONG', '108.47000000', '0.00001735', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:00:40 - Risk: NORMAL - SL: 105.597152 - TP: 114.215697', '2025-05-31 16:00:40', NULL, '2025-05-31 19:00:40', NULL, NULL, NULL, NULL, '2025-06-01 13:06:47', '110.77000000', '114.21569683', 'paper'),
(14, 'EOS/USDT', 'LONG', '0.77990000', '0.00193063', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:00:41 - Risk: NORMAL - SL: 0.741369 - TP: 0.856963', '2025-05-31 16:00:41', NULL, '2025-05-31 19:00:41', NULL, NULL, NULL, NULL, NULL, '0.74136862', '0.85696276', 'paper'),
(15, 'IOTX/USDT', 'LONG', '0.02192000', '0.06869049', NULL, NULL, '0000-00-00 00:00:00', 'CLOSED', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:00:46 - Risk: NORMAL - SL: 0.020889 - TP: 0.023982', '2025-05-31 16:00:46', NULL, '2025-05-31 19:00:46', NULL, NULL, NULL, 'delisted', '2025-06-01 11:44:51', '0.02235000', '0.02398192', 'paper'),
(16, 'BIFI/USDT', 'LONG', '206.50000000', '0.00000365', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:02:29 - Risk: HIGH - SL: 175.361851 - TP: 268.776299', '2025-05-31 16:02:29', NULL, '2025-05-31 19:02:29', NULL, NULL, NULL, NULL, '2025-05-31 16:18:10', '175.36185072', '268.77629856', 'paper'),
(17, 'AMP/USDT', 'LONG', '0.00417600', '0.36055927', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:02:33 - Risk: NORMAL - SL: 0.003916 - TP: 0.004695', '2025-05-31 16:02:33', NULL, '2025-05-31 19:02:33', NULL, NULL, NULL, NULL, '2025-05-31 16:18:11', '0.00391625', '0.00469550', 'paper'),
(18, 'VIRTUAL/USDT', 'LONG', '2.05980000', '0.00073099', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:05:00 - Risk: NORMAL - SL: 1.931082 - TP: 2.317237', '2025-05-31 16:05:00', NULL, '2025-05-31 19:05:00', NULL, NULL, NULL, NULL, '2025-05-31 16:35:23', '1.93108168', '2.31723663', 'paper'),
(19, 'BCH/USDT', 'LONG', '418.60000000', '0.00000450', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:05:56 - Risk: NORMAL - SL: 408.557339 - TP: 438.685323', '2025-05-31 16:05:56', NULL, '2025-05-31 19:05:56', NULL, NULL, NULL, NULL, '2025-05-31 16:33:28', '408.55733871', '438.68532257', 'paper'),
(20, 'AWE/USDT', 'LONG', '0.05881000', '0.02560271', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'breakout_detection', 'Risk yönetimli aç?l??: 2025-05-31 19:05:56 - Risk: NORMAL - SL: 0.056219 - TP: 0.063993', '2025-05-31 16:05:56', NULL, '2025-05-31 19:05:56', NULL, NULL, NULL, NULL, '2025-05-31 16:23:37', '0.05621872', '0.06399255', 'paper'),
(21, 'ICX/USDT', 'LONG', '0.11940000', '0.01261052', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:06:01 - Risk: NORMAL - SL: 0.112226 - TP: 0.133748', '2025-05-31 16:06:01', NULL, '2025-05-31 19:06:01', NULL, NULL, NULL, NULL, '2025-05-31 16:49:27', '0.11222603', '0.13374794', 'paper'),
(22, 'PHA/USDT', 'LONG', '0.14330000', '0.01050730', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:07:57 - Risk: NORMAL - SL: 0.132283 - TP: 0.165334', '2025-05-31 16:07:57', NULL, '2025-05-31 19:07:57', NULL, NULL, NULL, NULL, '2025-05-31 16:28:37', '0.13228286', '0.16533429', 'paper'),
(23, 'COOKIE/USDT', 'LONG', '0.22480000', '0.00669793', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:08:02 - Risk: NORMAL - SL: 0.209012 - TP: 0.256376', '2025-05-31 16:08:02', NULL, '2025-05-31 19:08:02', NULL, NULL, NULL, NULL, '2025-05-31 18:18:53', '0.20901198', '0.25637603', 'paper'),
(24, 'DGB/USDT', 'LONG', '0.00907000', '0.16600832', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:08:06 - Risk: NORMAL - SL: 0.008726 - TP: 0.009757', '2025-05-31 16:08:06', NULL, '2025-05-31 19:08:06', NULL, NULL, NULL, NULL, '2025-05-31 16:23:38', '0.00872646', '0.00975708', 'paper'),
(25, 'TAO/USDT', 'LONG', '411.30000000', '0.00000366', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:08:58 - Risk: NORMAL - SL: 395.316611 - TP: 443.266777', '2025-05-31 16:08:58', NULL, '2025-05-31 19:08:58', NULL, NULL, NULL, NULL, '2025-06-01 09:47:59', '416.73000000', '443.26677747', 'paper'),
(26, 'DEXE/USDT', 'LONG', '14.20100000', '0.00010603', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:11:08 - Risk: NORMAL - SL: 13.702662 - TP: 15.197675', '2025-05-31 16:11:08', NULL, '2025-05-31 19:11:08', NULL, NULL, NULL, NULL, '2025-06-01 12:14:25', '14.27100000', '15.19767506', 'paper'),
(27, 'PENDLE/USDT', 'LONG', '4.03800000', '0.00037288', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:11:18 - Risk: NORMAL - SL: 3.906458 - TP: 4.301084', '2025-05-31 16:11:18', NULL, '2025-05-31 19:11:18', NULL, NULL, NULL, NULL, '2025-05-31 16:57:13', '3.90645810', '4.30108380', 'paper'),
(28, 'SOLV/USDT', 'LONG', '0.04478000', '122.82268870', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'breakout_detection', 'Risk yönetimli aç?l??: 2025-06-01 16:09:51 - Risk: NORMAL - SL: 0.041850 - TP: 0.050640', '2025-05-31 16:12:30', NULL, '2025-05-31 19:12:30', NULL, NULL, NULL, NULL, '2025-06-01 13:20:33', '0.04509000', '0.05064005', 'paper'),
(29, 'AAVE/USDT', 'LONG', '249.81000000', '0.00000603', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:19:34 - Risk: NORMAL - SL: 242.079276 - TP: 265.271448', '2025-05-31 16:19:34', NULL, '2025-05-31 19:19:34', NULL, NULL, NULL, NULL, '2025-05-31 21:37:02', '242.07927579', '265.27144843', 'paper'),
(30, 'KAVA/USDT', 'LONG', '0.41990000', '0.00358584', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-05-31 19:22:10 - Risk: NORMAL - SL: 0.402777 - TP: 0.454146', '2025-05-31 16:22:10', NULL, '2025-05-31 19:22:10', NULL, NULL, NULL, NULL, '2025-06-01 13:20:28', '0.42070000', '0.45414603', 'paper'),
(31, 'HEI/USDT', 'LONG', '0.31750000', '17.32283465', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:53:54 - Risk: NORMAL - SL: 0.307680 - TP: 0.337140', '2025-06-01 12:53:54', NULL, '2025-06-01 15:53:54', NULL, NULL, NULL, NULL, '2025-06-01 13:20:28', '0.31990000', '0.33713984', 'paper'),
(32, 'BSW/USDT', 'LONG', '0.02513000', '218.86191803', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:53:55 - Risk: NORMAL - SL: 0.024244 - TP: 0.026902', '2025-06-01 12:53:55', NULL, '2025-06-01 15:53:55', NULL, NULL, NULL, NULL, '2025-06-01 13:20:29', '0.02640000', '0.02690218', 'paper'),
(33, 'GTC/USDT', 'LONG', '0.25200000', '21.82539683', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:53:57 - Risk: NORMAL - SL: 0.243209 - TP: 0.269581', '2025-06-01 12:53:57', NULL, '2025-06-01 15:53:57', NULL, NULL, NULL, NULL, '2025-06-01 13:20:29', '0.25400000', '0.26958146', 'paper'),
(34, 'TUT/USDT', 'LONG', '0.02682000', '205.07084265', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:54:18 - Risk: NORMAL - SL: 0.025253 - TP: 0.029955', '2025-06-01 12:54:18', NULL, '2025-06-01 15:54:18', NULL, NULL, NULL, NULL, '2025-06-01 13:20:30', '0.02727000', '0.02995491', 'paper'),
(35, 'EIGEN/USDT', 'LONG', '1.30460000', '4.21585160', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:55:38 - Risk: NORMAL - SL: 1.245793 - TP: 1.422213', '2025-06-01 12:55:38', NULL, '2025-06-01 15:55:38', NULL, NULL, NULL, NULL, '2025-06-01 13:20:31', '1.31840000', '1.42221343', 'paper'),
(36, 'INIT/USDT', 'LONG', '0.72940000', '7.54044420', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:55:58 - Risk: NORMAL - SL: 0.690043 - TP: 0.808114', '2025-06-01 12:55:58', NULL, '2025-06-01 15:55:58', NULL, NULL, NULL, NULL, '2025-06-01 13:20:31', '0.73230000', '0.80811370', 'paper'),
(37, 'BIO/USDT', 'LONG', '0.06520000', '84.35582822', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:56:19 - Risk: NORMAL - SL: 0.062470 - TP: 0.070660', '2025-06-01 12:56:19', NULL, '2025-06-01 15:56:19', NULL, NULL, NULL, NULL, '2025-06-01 13:20:31', '0.06561000', '0.07066007', 'paper'),
(38, 'ZEC/USDT', 'LONG', '51.56000000', '0.10667184', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:56:21 - Risk: NORMAL - SL: 49.423982 - TP: 55.832036', '2025-06-01 12:56:21', NULL, '2025-06-01 15:56:21', NULL, NULL, NULL, NULL, '2025-06-01 13:20:32', '51.90000000', '55.83203617', 'paper'),
(39, 'ZEN/USDT', 'LONG', '10.02900000', '0.54840961', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 15:56:42 - Risk: NORMAL - SL: 9.238143 - TP: 11.610714', '2025-06-01 12:56:42', NULL, '2025-06-01 15:56:42', NULL, NULL, NULL, NULL, '2025-06-01 13:20:32', '10.66400000', '11.61071383', 'paper'),
(40, 'SOLV/USDT', 'LONG', '0.04478000', '122.82268870', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 16:09:51 - Risk: NORMAL - SL: 0.041850 - TP: 0.050640', '2025-06-01 13:09:51', NULL, '2025-06-01 16:09:51', NULL, NULL, NULL, NULL, '2025-06-01 13:20:33', '0.04509000', '0.05064005', 'paper'),
(41, 'ONDO/USDT', 'LONG', '0.81690000', '6.73277023', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 16:11:16 - Risk: NORMAL - SL: 0.797562 - TP: 0.855575', '2025-06-01 13:11:16', NULL, '2025-06-01 16:11:16', NULL, NULL, NULL, NULL, '2025-06-01 13:20:33', '0.81930000', '0.85557543', 'paper'),
(42, 'TST/USDT', 'LONG', '0.04520000', '121.68141593', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 16:24:07 - Risk: NORMAL - SL: 0.042390 - TP: 0.050819', '2025-06-01 13:24:07', NULL, '2025-06-01 16:24:07', NULL, NULL, NULL, NULL, NULL, '0.04239039', '0.05081923', 'paper'),
(43, 'KERNEL/USDT', 'LONG', '0.15500000', '35.48387097', NULL, NULL, '0000-00-00 00:00:00', 'OPEN', NULL, NULL, NULL, NULL, 'volatility_breakout', 'Risk yönetimli aç?l??: 2025-06-01 16:26:54 - Risk: NORMAL - SL: 0.146862 - TP: 0.171275', '2025-06-01 13:26:54', NULL, '2025-06-01 16:26:54', NULL, NULL, NULL, NULL, NULL, '0.14686245', '0.17127510', 'paper');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `price_analysis`
--

CREATE TABLE IF NOT EXISTS `price_analysis` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `analysis_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `price` decimal(20,8) NOT NULL,
  `rsi` decimal(10,2) DEFAULT NULL,
  `macd` decimal(20,8) DEFAULT NULL,
  `macd_signal` decimal(20,8) DEFAULT NULL,
  `bollinger_upper` decimal(20,8) DEFAULT NULL,
  `bollinger_middle` decimal(20,8) DEFAULT NULL,
  `bollinger_lower` decimal(20,8) DEFAULT NULL,
  `ma20` decimal(20,8) DEFAULT NULL,
  `ma50` decimal(20,8) DEFAULT NULL,
  `ma100` decimal(20,8) DEFAULT NULL,
  `ma200` decimal(20,8) DEFAULT NULL,
  `trade_signal` varchar(10) DEFAULT NULL,
  `buy_signals` int(11) DEFAULT NULL,
  `sell_signals` int(11) DEFAULT NULL,
  `neutral_signals` int(11) DEFAULT NULL,
  `notes` text
) ENGINE=InnoDB AUTO_INCREMENT=4064 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `price_analysis`
--

INSERT INTO `price_analysis` (`id`, `symbol`, `analysis_time`, `price`, `rsi`, `macd`, `macd_signal`, `bollinger_upper`, `bollinger_middle`, `bollinger_lower`, `ma20`, `ma50`, `ma100`, `ma200`, `trade_signal`, `buy_signals`, `sell_signals`, `neutral_signals`, `notes`) VALUES
(3660, 'LINK/USDT', '2025-06-01 16:25:11', '13.83200000', '63.25', '0.01897034', '0.01897420', '13.85699163', '13.81445000', '13.77190837', '13.81445000', '13.77064000', '13.77817000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3661, 'ADA/USDT', '2025-05-31 19:36:12', '0.68720000', '72.74', '0.00097899', '0.00095692', '0.68714185', '0.68566500', '0.68418815', '0.68566500', '0.68302600', '0.68212300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3662, 'BTC/USDT', '2025-06-01 16:26:32', '104182.10000000', '56.38', '61.68255829', '75.75839054', '104359.56441401', '104181.19000000', '104002.81558599', '104181.19000000', '104003.82800000', '104015.31600000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3663, 'PYR/USDT', '2025-05-31 17:11:57', '1.04700000', '47.89', '-0.00001110', '-0.00036135', '1.04916125', '1.04660000', '1.04403875', '1.04660000', '1.04836000', '1.05037000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3664, 'DASH/USDT', '2025-05-31 17:11:57', '21.80000000', '58.14', '0.01184733', '0.00578686', '21.84113913', '21.76650000', '21.69186087', '21.76650000', '21.77720000', '21.77610000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 91.07, %D: 93.41)'),
(3665, 'SLF/USDT', '2025-05-31 19:22:09', '0.17460000', '55.84', '-0.00001011', '-0.00015742', '0.17513698', '0.17306500', '0.17099302', '0.17306500', '0.17405600', '0.17343000', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.1746, SAR: 0.1707)'),
(3666, 'KAVA/USDT', '2025-06-01 16:19:31', '0.42050000', '73.39', '0.00073602', '0.00054531', '0.42057595', '0.41873500', '0.41689405', '0.41873500', '0.41764600', '0.41758400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3667, 'DEXE/USDT', '2025-06-01 16:22:03', '14.46000000', '70.48', '0.05162864', '0.04531032', '14.51044116', '14.37865000', '14.24685884', '14.37865000', '14.29206000', '14.23981000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 93.81, %D: 94.35)'),
(3668, 'POL/USDT', '2025-06-01 16:21:59', '0.21216000', '70.47', '0.00029409', '0.00023636', '0.21239538', '0.21150100', '0.21060662', '0.21150100', '0.21104880', '0.21160090', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3669, 'ATOM/USDT', '2025-05-31 17:12:21', '4.31600000', '56.75', '0.00180415', '-0.00005397', '4.32251922', '4.30690000', '4.29128078', '4.30690000', '4.31438000', '4.30959000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.80, %D: 87.38)'),
(3670, 'KDA/USDT', '2025-05-31 17:12:21', '0.46190000', '58.96', '0.00052419', '0.00028860', '0.46365634', '0.46038000', '0.45710366', '0.46038000', '0.46097200', '0.46031600', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.89, %D: 91.83)'),
(3671, 'GLM/USDT', '2025-05-31 17:12:21', '0.23300000', '56.58', '0.00009962', '-0.00002228', '0.23339056', '0.23243500', '0.23147944', '0.23243500', '0.23287800', '0.23317100', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.50, %D: 90.08)'),
(3672, 'CELR/USDT', '2025-05-31 17:12:21', '0.00812000', '63.87', '0.00001292', '0.00000630', '0.00813787', '0.00807800', '0.00801813', '0.00807800', '0.00808400', '0.00807860', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3673, 'ARDR/USDT', '2025-05-31 17:12:44', '0.08592000', '58.87', '0.00002192', '-0.00010410', '0.08630568', '0.08535950', '0.08441332', '0.08535950', '0.08569000', '0.08569840', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0859, SAR: 0.0844)'),
(3674, 'VANRY/USDT', '2025-05-31 17:12:44', '0.03400000', '58.34', '0.00004605', '0.00003091', '0.03414566', '0.03386000', '0.03357434', '0.03386000', '0.03389800', '0.03369200', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0340, SAR: 0.0338)'),
(3675, 'ICP/USDT', '2025-05-31 19:36:38', '4.87700000', '59.24', '0.00658937', '0.00717985', '4.88223474', '4.87405000', '4.86586526', '4.87405000', '4.85260000', '4.85153000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3676, 'RENDER/USDT', '2025-06-01 16:25:31', '3.81800000', '58.03', '0.00656372', '0.00696682', '3.83114968', '3.81575000', '3.80035032', '3.81575000', '3.79892000', '3.80895000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3677, 'LISTA/USDT', '2025-05-31 17:12:44', '0.21270000', '54.07', '0.00019762', '0.00006487', '0.21376693', '0.21206000', '0.21035307', '0.21206000', '0.21254600', '0.21247100', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.67, %D: 91.41)'),
(3678, 'CHESS/USDT', '2025-05-31 17:13:09', '0.05770000', '57.55', '0.00004166', '0.00000236', '0.05789202', '0.05752000', '0.05714798', '0.05752000', '0.05766000', '0.05775800', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.33, %D: 85.19)'),
(3679, 'TST/USDT', '2025-05-31 17:13:09', '0.03950000', '69.14', '0.00014960', '0.00009758', '0.03952626', '0.03906000', '0.03859374', '0.03906000', '0.03887220', '0.03903110', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0395, SAR: 0.0388)'),
(3680, '1000CHEEMS/USDT', '2025-05-31 17:13:09', '0.00147700', '58.00', '0.00000007', '-0.00000062', '0.00147714', '0.00147325', '0.00146936', '0.00147325', '0.00147628', '0.00147692', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3681, 'TRB/USDT', '2025-05-31 17:13:09', '41.64000000', '58.28', '-0.00147116', '-0.06706648', '41.65225916', '41.32800000', '41.00374084', '41.32800000', '41.60480000', '41.98660000', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 41.6400, SAR: 41.2532)'),
(3682, 'PSG/USDT', '2025-05-31 17:13:09', '2.22800000', '67.48', '0.00292524', '0.00136360', '2.23070469', '2.21790000', '2.20509531', '2.21790000', '2.21760000', '2.22270000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 94.67, %D: 96.28)'),
(3683, 'POND/USDT', '2025-05-31 17:13:33', '0.00936000', '52.96', '0.00000528', '0.00000216', '0.00938843', '0.00934750', '0.00930657', '0.00934750', '0.00935460', '0.00938080', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3684, 'HEI/USDT', '2025-05-31 17:13:33', '0.30500000', '49.05', '0.00018419', '0.00017286', '0.30686827', '0.30486500', '0.30286173', '0.30486500', '0.30504600', '0.30566100', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3685, 'ACM/USDT', '2025-05-31 17:13:33', '0.86700000', '58.88', '0.00122147', '0.00126020', '0.86867133', '0.86450000', '0.86032867', '0.86450000', '0.86196000', '0.86152000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3686, 'ANIME/USDT', '2025-05-31 17:13:33', '0.02322000', '58.97', '0.00004250', '0.00003307', '0.02333726', '0.02313800', '0.02293874', '0.02313800', '0.02312460', '0.02309550', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3687, 'SYRUP/USDT', '2025-05-31 17:13:33', '0.35280000', '63.22', '0.00055639', '0.00025924', '0.35249690', '0.35024000', '0.34798310', '0.35024000', '0.35056600', '0.34504900', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.3528, SAR: 0.3495)'),
(3688, 'SANTOS/USDT', '2025-05-31 17:13:57', '2.30100000', '57.38', '0.00135957', '0.00059211', '2.30691529', '2.29685000', '2.28678471', '2.29685000', '2.29942000', '2.30176000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3689, 'ATM/USDT', '2025-05-31 17:13:57', '1.10200000', '57.57', '0.00058419', '0.00033574', '1.10386512', '1.10055000', '1.09723488', '1.10055000', '1.10054000', '1.10241000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3690, 'JUV/USDT', '2025-05-31 17:13:57', '1.08400000', '60.55', '0.00178528', '0.00146949', '1.08776867', '1.08140000', '1.07503133', '1.08140000', '1.07970000', '1.07774000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.01, %D: 88.71)'),
(3691, 'VOXEL/USDT', '2025-05-31 17:13:57', '0.05770000', '54.25', '0.00003954', '-0.00000824', '0.05793087', '0.05747000', '0.05700913', '0.05747000', '0.05765000', '0.05798100', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0577, SAR: 0.0572)'),
(3692, 'EPIC/USDT', '2025-05-31 17:13:57', '1.19800000', '64.09', '0.00386413', '0.00311206', '1.20277855', '1.19160000', '1.18042145', '1.19160000', '1.18604000', '1.18732000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3693, 'CYBER/USDT', '2025-05-31 17:14:21', '1.20700000', '66.93', '0.00170466', '0.00116664', '1.20901178', '1.20105000', '1.19308822', '1.20105000', '1.20082000', '1.19787000', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 1.2070, SAR: 1.2005)'),
(3694, 'CATI/USDT', '2025-05-31 17:14:21', '0.09550000', '58.37', '0.00011088', '0.00005206', '0.09575440', '0.09507000', '0.09438560', '0.09507000', '0.09513600', '0.09540800', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3695, 'FXS/USDT', '2025-05-31 17:14:21', '2.76600000', '66.02', '0.00731926', '0.00619787', '2.78159664', '2.75180000', '2.72200336', '2.75180000', '2.74408000', '2.74679000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3696, 'BSW/USDT', '2025-05-31 17:14:21', '0.02450000', '55.72', '0.00002329', '0.00000598', '0.02456733', '0.02440000', '0.02423267', '0.02440000', '0.02443800', '0.02442800', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0245, SAR: 0.0243)'),
(3697, 'AUDIO/USDT', '2025-05-31 17:14:21', '0.06840000', '71.93', '0.00016712', '0.00010931', '0.06859086', '0.06772000', '0.06684914', '0.06772000', '0.06769600', '0.06797700', NULL, 'BUY', 1, 0, 7, 'breakout_detection: BUY - Volatilite patlamas? ve yükselen mum'),
(3698, 'SUN/USDT', '2025-05-31 17:14:46', '0.01871000', '53.41', '0.00000037', '-0.00000302', '0.01872756', '0.01869500', '0.01866244', '0.01869500', '0.01871580', '0.01873840', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3699, 'NXPC/USDT', '2025-05-31 17:14:46', '1.34810000', '56.01', '0.00066302', '-0.00010036', '1.35171131', '1.34410000', '1.33648869', '1.34410000', '1.34700000', '1.35092300', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3700, 'PENDLE/USDT', '2025-06-01 16:19:11', '3.92000000', '64.06', '0.00291069', '0.00043534', '3.92008420', '3.90458500', '3.88908580', '3.90458500', '3.90644200', '3.92601100', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3701, 'C98/USDT', '2025-05-31 17:14:46', '0.05140000', '64.37', '0.00008494', '0.00004240', '0.05143217', '0.05104500', '0.05065783', '0.05104500', '0.05108000', '0.05104100', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0514, SAR: 0.0510)'),
(3702, 'PERP/USDT', '2025-06-01 16:20:35', '0.24190000', '80.19', '0.00089293', '0.00066891', '0.24229218', '0.23974000', '0.23718782', '0.23974000', '0.23856400', '0.23931700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3703, 'KERNEL/USDT', '2025-05-31 17:15:10', '0.14460000', '62.25', '0.00033815', '0.00025446', '0.14533983', '0.14387000', '0.14240017', '0.14387000', '0.14368000', '0.14400400', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3704, 'ALPINE/USDT', '2025-05-31 17:15:10', '0.79800000', '64.91', '0.00100189', '0.00056569', '0.79984479', '0.79505000', '0.79025521', '0.79505000', '0.79528000', '0.79734000', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.7980, SAR: 0.7936)'),
(3705, 'NKN/USDT', '2025-05-31 17:15:10', '0.02920000', '53.22', '0.00002121', '0.00001567', '0.02933205', '0.02916000', '0.02898795', '0.02916000', '0.02918000', '0.02920600', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3706, 'LEVER/USDT', '2025-05-31 17:15:10', '0.00045400', '66.01', '0.00000112', '0.00000067', '0.00045498', '0.00045040', '0.00044582', '0.00045040', '0.00045034', '0.00045006', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0005, SAR: 0.0004)'),
(3707, 'BOME/USDT', '2025-05-31 17:15:11', '0.00179800', '59.79', '0.00000528', '0.00000486', '0.00181115', '0.00178840', '0.00176565', '0.00178840', '0.00178252', '0.00177459', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Ichimoku: Fiyat ye?il bulutun üstünde, TK çapraz? yukar?'),
(3708, 'BIO/USDT', '2025-05-31 17:15:36', '0.06570000', '76.90', '0.00039944', '0.00029824', '0.06607694', '0.06465500', '0.06323306', '0.06465500', '0.06436600', '0.06403100', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3709, '1MBABYDOGE/USDT', '2025-05-31 17:15:36', '0.00137440', '63.71', '0.00000308', '0.00000233', '0.00137939', '0.00136654', '0.00135370', '0.00136654', '0.00136479', '0.00136503', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3710, 'TRUMP/USDT', '2025-06-01 16:23:58', '11.22400000', '61.67', '0.01612356', '0.01536259', '11.25214732', '11.20320000', '11.15425268', '11.20320000', '11.17150000', '11.17842000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3711, 'OSMO/USDT', '2025-05-31 17:15:36', '0.20950000', '60.95', '0.00023172', '0.00023821', '0.20993636', '0.20921000', '0.20848364', '0.20921000', '0.20875600', '0.20867700', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3712, 'USUAL/USDT', '2025-05-31 17:15:36', '0.10300000', '63.56', '0.00020627', '0.00013573', '0.10334874', '0.10236000', '0.10137126', '0.10236000', '0.10238800', '0.10198000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 80.83, %D: 83.68)'),
(3713, 'BIFI/USDT', '2025-05-31 19:22:05', '208.80000000', '46.98', '1.11554825', '1.85304201', '216.96113624', '211.68000000', '206.39886376', '211.68000000', '206.89000000', '204.36900000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 208.8000, SAR: 216.3634)'),
(3714, 'FIS/USDT', '2025-05-31 17:15:58', '0.15110000', '78.98', '0.00032287', '0.00019304', '0.15126554', '0.15018000', '0.14909446', '0.15018000', '0.15003600', '0.15003300', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3715, 'BABY/USDT', '2025-05-31 17:15:58', '0.06256000', '73.01', '0.00015695', '0.00009838', '0.06267097', '0.06206250', '0.06145403', '0.06206250', '0.06200660', '0.06221710', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 88.82, %D: 89.27)'),
(3716, 'OM/USDT', '2025-05-31 19:38:57', '0.31095000', '39.51', '0.00007504', '0.00033245', '0.31377466', '0.31253950', '0.31130434', '0.31253950', '0.31115260', '0.31151360', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.3110, SAR: 0.3132)'),
(3717, 'HAEDAL/USDT', '2025-05-31 17:15:59', '0.12400000', '67.03', '0.00025815', '0.00016236', '0.12416321', '0.12287000', '0.12157679', '0.12287000', '0.12281000', '0.12268400', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.1240, SAR: 0.1230)'),
(3718, 'ORCA/USDT', '2025-05-31 17:15:59', '2.68100000', '71.11', '0.00961172', '0.00699681', '2.68431710', '2.65420000', '2.62408290', '2.65420000', '2.64534000', '2.64143000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3719, 'MKR/USDT', '2025-06-01 16:15:46', '1560.80000000', '59.70', '1.31394571', '0.94006693', '1563.17607453', '1558.75500000', '1554.33392547', '1558.75500000', '1557.69200000', '1559.98100000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 90.48, %D: 92.22)'),
(3720, 'BANANAS31/USDT', '2025-06-01 16:16:27', '0.00640600', '57.55', '0.00000527', '0.00000458', '0.00641654', '0.00639780', '0.00637906', '0.00639780', '0.00639094', '0.00639311', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3721, 'LOKA/USDT', '2025-05-31 17:16:22', '0.05690000', '65.09', '0.00014626', '0.00010023', '0.05702598', '0.05651500', '0.05600402', '0.05651500', '0.05640600', '0.05641700', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0569, SAR: 0.0566)'),
(3722, 'DOGS/USDT', '2025-05-31 17:16:22', '0.00014760', '59.94', '0.00000025', '0.00000018', '0.00014805', '0.00014702', '0.00014599', '0.00014702', '0.00014688', '0.00014675', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3723, 'ALPHA/USDT', '2025-05-31 17:16:22', '0.02530000', '76.69', '0.00011567', '0.00007611', '0.02533094', '0.02496000', '0.02458906', '0.02496000', '0.02487200', '0.02482300', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3724, 'APE/USDT', '2025-05-31 17:16:22', '0.60410000', '56.57', '0.00027182', '-0.00003742', '0.60503951', '0.60265500', '0.60027049', '0.60265500', '0.60357600', '0.60497500', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3725, 'CAKE/USDT', '2025-05-31 17:16:22', '2.30600000', '63.23', '0.00392182', '0.00297723', '2.31215248', '2.29650000', '2.28084752', '2.29650000', '2.29358000', '2.28888000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3726, 'AUCTION/USDT', '2025-05-31 17:16:46', '10.05000000', '58.07', '0.01464400', '0.00832169', '10.08738627', '10.01600000', '9.94461373', '10.01600000', '10.01940000', '10.03750000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.86, %D: 86.60)'),
(3727, 'VANA/USDT', '2025-06-01 16:26:50', '6.53000000', '61.33', '0.01279003', '0.01130425', '6.55420291', '6.50485000', '6.45549709', '6.50485000', '6.49710000', '6.44369000', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Ichimoku: Fiyat ye?il bulutun üstünde, TK çapraz? yukar?'),
(3728, 'HIFI/USDT', '2025-05-31 17:16:46', '0.08470000', '43.41', '-0.00001716', '-0.00002026', '0.08528299', '0.08493500', '0.08458701', '0.08493500', '0.08504400', '0.08530600', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3729, 'FORM/USDT', '2025-06-01 16:18:08', '2.79270000', '63.19', '0.00102823', '0.00006634', '2.79292513', '2.78759000', '2.78225487', '2.78759000', '2.78796600', '2.79467700', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 84.33, %D: 84.72)'),
(3730, 'FTT/USDT', '2025-05-31 17:16:46', '1.09030000', '45.78', '-0.00242671', '-0.00295409', '1.09561587', '1.09033000', '1.08504413', '1.09033000', '1.09721000', '1.10133300', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 43.01, -DI: 32.22)'),
(3731, 'COTI/USDT', '2025-05-31 17:17:09', '0.05810000', '58.82', '0.00011298', '0.00010827', '0.05839525', '0.05797250', '0.05754975', '0.05797250', '0.05782380', '0.05769430', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3732, 'SFP/USDT', '2025-05-31 17:17:09', '0.48290000', '61.25', '0.00047986', '0.00037774', '0.48398308', '0.48218500', '0.48038692', '0.48218500', '0.48189200', '0.48170500', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 80.59, %D: 85.79)'),
(3733, 'XVG/USDT', '2025-06-01 16:24:06', '0.00667760', '62.19', '0.00002040', '0.00002019', '0.00670392', '0.00665236', '0.00660081', '0.00665236', '0.00660593', '0.00662176', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3734, 'KAITO/USDT', '2025-05-31 17:17:09', '1.93100000', '49.32', '0.00096905', '0.00135751', '1.94179957', '1.93224500', '1.92269043', '1.93224500', '1.93067000', '1.92184600', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 1.9310, SAR: 1.9425)'),
(3735, 'D/USDT', '2025-05-31 17:17:09', '0.03497000', '60.21', '0.00003517', '0.00002231', '0.03502085', '0.03488050', '0.03474015', '0.03488050', '0.03486320', '0.03487180', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3736, 'TWT/USDT', '2025-05-31 17:17:31', '0.78190000', '72.67', '0.00097094', '0.00086434', '0.78313776', '0.78011500', '0.77709224', '0.78011500', '0.77861600', '0.77860000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3737, 'WLD/USDT', '2025-05-31 17:17:31', '1.15100000', '50.92', '0.00183621', '0.00185357', '1.15864066', '1.15115000', '1.14365934', '1.15115000', '1.14858000', '1.15004000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 1.1510, SAR: 1.1570)'),
(3738, 'DCR/USDT', '2025-05-31 17:17:31', '14.36000000', '63.13', '0.01721298', '0.01127518', '14.37910662', '14.30050000', '14.22189338', '14.30050000', '14.28760000', '14.29920000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.19, %D: 86.30)'),
(3739, 'TON/USDT', '2025-05-31 17:17:31', '3.08000000', '42.46', '-0.00134625', '-0.00080901', '3.09362562', '3.08605000', '3.07847438', '3.08605000', '3.08730000', '3.09317000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3740, 'CETUS/USDT', '2025-05-31 17:17:31', '0.13430000', '53.62', '0.00044045', '0.00045540', '0.13579965', '0.13417500', '0.13255035', '0.13417500', '0.13335600', '0.13394100', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.1343, SAR: 0.1353)'),
(3741, 'TUT/USDT', '2025-05-31 17:17:54', '0.02277000', '55.13', '0.00004571', '0.00002819', '0.02292632', '0.02269200', '0.02245768', '0.02269200', '0.02269940', '0.02283940', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0228, SAR: 0.0229)'),
(3742, 'NIL/USDT', '2025-05-31 17:17:54', '0.42720000', '53.94', '0.00044672', '0.00021801', '0.42891187', '0.42623000', '0.42354813', '0.42623000', '0.42611200', '0.42953500', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3743, 'PUNDIX/USDT', '2025-05-31 17:17:54', '0.32020000', '49.59', '0.00036108', '0.00036933', '0.32194560', '0.32030000', '0.31865440', '0.32030000', '0.31969200', '0.32070600', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.3202, SAR: 0.3219)'),
(3744, 'QNT/USDT', '2025-06-01 16:27:08', '110.41000000', '40.62', '-0.04900331', '-0.00797831', '110.91921286', '110.60200000', '110.28478714', '110.60200000', '110.49860000', '110.15350000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 27.88, -DI: 27.44)'),
(3745, 'AGLD/USDT', '2025-05-31 17:17:54', '0.76600000', '51.19', '0.00080267', '0.00062020', '0.76926349', '0.76555000', '0.76183651', '0.76555000', '0.76530000', '0.76372000', NULL, 'BUY', 1, 0, 7, 'volatility_breakout: BUY - Ichimoku: Fiyat ye?il bulutun üstünde, TK çapraz? yukar?'),
(3746, 'NEXO/USDT', '2025-05-31 19:22:09', '1.24000000', '63.28', '0.00138444', '0.00124333', '1.24160384', '1.23770000', '1.23379616', '1.23770000', '1.23554000', '1.23483000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3747, 'BONK/USDT', '2025-05-31 17:18:18', '0.00001657', '66.30', '0.00000006', '0.00000005', '0.00001649', '0.00001649', '0.00001649', '0.00001649', '0.00001640', '0.00001637', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 84.85, %D: 88.02)'),
(3748, 'BEAMX/USDT', '2025-06-01 16:18:30', '0.00633200', '79.60', '0.00001801', '0.00001258', '0.00633927', '0.00628690', '0.00623453', '0.00628690', '0.00626736', '0.00628802', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 93.10, %D: 94.20)'),
(3749, 'RAY/USDT', '2025-05-31 17:18:18', '2.53400000', '57.22', '0.00746336', '0.00748940', '2.55403965', '2.52880000', '2.50356035', '2.52880000', '2.51672000', '2.50893000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 2.5340, SAR: 2.5476)'),
(3750, 'MAGIC/USDT', '2025-05-31 17:18:18', '0.13100000', '49.93', '0.00017776', '0.00019827', '0.13200591', '0.13103000', '0.13005409', '0.13103000', '0.13079600', '0.13092100', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 26.13, -DI: 28.82)'),
(3751, '1000CAT/USDT', '2025-05-31 17:18:18', '0.00718000', '62.85', '0.00002379', '0.00002159', '0.00722140', '0.00714200', '0.00706260', '0.00714200', '0.00711060', '0.00709380', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3752, 'ASTR/USDT', '2025-05-31 17:18:41', '0.02567000', '59.10', '0.00003037', '0.00002961', '0.02575140', '0.02564750', '0.02554360', '0.02564750', '0.02560520', '0.02558020', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3753, 'LUNA/USDT', '2025-05-31 17:18:41', '0.16840000', '51.61', '0.00017438', '0.00021241', '0.16907896', '0.16844500', '0.16781104', '0.16844500', '0.16801400', '0.16752000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3754, 'ZRO/USDT', '2025-05-31 17:18:41', '2.25700000', '57.31', '0.00488184', '0.00493401', '2.26992606', '2.25365000', '2.23737394', '2.25365000', '2.24522000', '2.24373000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3755, 'SOL/USDT', '2025-05-31 17:18:41', '155.58000000', '56.45', '0.17996938', '0.17969406', '156.05351854', '155.46700000', '154.88048146', '155.46700000', '155.15940000', '155.13630000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3756, 'SUI/USDT', '2025-05-31 19:36:09', '3.30130000', '62.31', '0.00734849', '0.00755427', '3.30562646', '3.29519000', '3.28475354', '3.29519000', '3.27370800', '3.26895600', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.23, %D: 88.68)'),
(3757, 'SUPER/USDT', '2025-05-31 20:01:17', '0.67830000', '53.48', '0.00050091', '0.00023856', '0.68100000', '0.67672000', '0.67244000', '0.67672000', '0.67794600', '0.67612700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3758, 'LAYER/USDT', '2025-05-31 17:19:04', '0.76880000', '38.60', '-0.00038226', '-0.00002010', '0.77568509', '0.77186500', '0.76804491', '0.77186500', '0.77219000', '0.77727500', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.7688, SAR: 0.7749)'),
(3759, 'SYN/USDT', '2025-05-31 17:19:04', '0.16570000', '62.69', '0.00024901', '0.00015844', '0.16604687', '0.16509000', '0.16413313', '0.16509000', '0.16489600', '0.16529400', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3760, 'STO/USDT', '2025-05-31 17:19:04', '0.09270000', '60.66', '0.00013996', '0.00008408', '0.09289780', '0.09241500', '0.09193220', '0.09241500', '0.09229200', '0.09313100', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3761, 'REQ/USDT', '2025-05-31 17:19:04', '0.13760000', '43.57', '-0.00002559', '-0.00002475', '0.13790000', '0.13770000', '0.13750000', '0.13770000', '0.13785200', '0.13808400', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3762, 'NEO/USDT', '2025-05-31 17:19:26', '5.86000000', '65.97', '0.00713302', '0.00627230', '5.86709975', '5.84700000', '5.82690025', '5.84700000', '5.83600000', '5.83040000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3763, 'VET/USDT', '2025-06-01 16:15:27', '0.02381100', '66.75', '0.00003379', '0.00002472', '0.02384833', '0.02374885', '0.02364937', '0.02374885', '0.02370894', '0.02376488', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 89.94, %D: 92.29)'),
(3764, 'COW/USDT', '2025-05-31 17:19:26', '0.37280000', '50.05', '0.00040589', '0.00055353', '0.37542841', '0.37317000', '0.37091159', '0.37317000', '0.37214600', '0.37331200', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.3728, SAR: 0.3748)'),
(3765, 'ETC/USDT', '2025-05-31 17:19:26', '16.92000000', '51.81', '0.00777045', '0.00738284', '16.95415757', '16.91600000', '16.87784243', '16.91600000', '16.90800000', '16.93290000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3766, 'THETA/USDT', '2025-06-01 16:13:42', '0.74310000', '70.90', '0.00097615', '0.00067174', '0.74321453', '0.74061000', '0.73800547', '0.74061000', '0.73928600', '0.74160500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3767, 'XLM/USDT', '2025-05-31 20:00:54', '0.26704000', '52.18', '0.00006095', '0.00002684', '0.26737520', '0.26684550', '0.26631580', '0.26684550', '0.26702140', '0.26661630', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3768, 'ZRX/USDT', '2025-05-31 17:19:49', '0.23320000', '55.59', '0.00035845', '0.00037301', '0.23418508', '0.23312000', '0.23205492', '0.23312000', '0.23246200', '0.23222400', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3769, 'IOTA/USDT', '2025-05-31 17:19:49', '0.18000000', '54.55', '0.00021348', '0.00023089', '0.18083213', '0.17989000', '0.17894787', '0.17989000', '0.17955000', '0.17942300', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3770, 'BAT/USDT', '2025-05-31 19:36:58', '0.13010000', '76.12', '0.00024417', '0.00020455', '0.13001749', '0.12955500', '0.12909251', '0.12955500', '0.12902200', '0.12897600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3771, 'LTC/USDT', '2025-06-01 16:26:11', '87.03000000', '60.94', '0.12437814', '0.13417232', '87.25481828', '86.96600000', '86.67718172', '86.96600000', '86.65440000', '86.68440000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3772, 'AAVE/USDT', '2025-06-01 16:19:11', '241.73000000', '61.01', '0.27193669', '0.24933898', '242.03618202', '241.23400000', '240.43181798', '241.23400000', '240.70220000', '240.71310000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3773, 'PAXG/USDT', '2025-05-31 17:47:18', '3300.07000000', '40.32', '-0.20247480', '-0.19961400', '3301.82515322', '3300.34000000', '3298.85484678', '3300.34000000', '3300.57420000', '3300.83050000', NULL, 'SELL', 0, 1, 7, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 25.36, -DI: 50.93)'),
(3774, 'IOTX/USDT', '2025-06-01 16:02:48', '0.02220000', '48.66', '-0.00001171', '-0.00001434', '0.02231406', '0.02221250', '0.02211094', '0.02221250', '0.02222100', '0.02226880', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3775, 'USDC/USDT', '2025-05-31 17:20:11', '0.99950000', '54.51', '-0.00000352', '-0.00000594', '0.99953798', '0.99944000', '0.99934202', '0.99944000', '0.99944600', '0.99944600', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3776, 'SOLV/USDT', '2025-06-01 16:21:18', '0.04497000', '58.06', '0.00010718', '0.00010595', '0.04518189', '0.04488250', '0.04458311', '0.04488250', '0.04466280', '0.04473050', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3777, 'AMP/USDT', '2025-05-31 19:22:14', '0.00418100', '56.59', '0.00000465', '0.00000323', '0.00418988', '0.00417205', '0.00415422', '0.00417205', '0.00417000', '0.00417490', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3778, 'PROM/USDT', '2025-05-31 17:53:24', '5.45700000', '25.86', '-0.00492557', '-0.00231824', '5.49697617', '5.48020000', '5.46342383', '5.48020000', '5.48482000', '5.49430000', NULL, 'NEUTRAL', 0, 0, 8, 'Strateji notu yok'),
(3779, 'VIRTUAL/USDT', '2025-06-01 16:18:50', '1.95660000', '67.84', '0.00537200', '0.00349453', '1.95857205', '1.94110500', '1.92363795', '1.94110500', '1.93593400', '1.94266100', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 1.9566, SAR: 1.9424)'),
(3780, 'COOKIE/USDT', '2025-06-01 16:19:11', '0.21800000', '63.58', '0.00061822', '0.00042154', '0.21876327', '0.21644000', '0.21411673', '0.21644000', '0.21599200', '0.21722900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.04, %D: 86.90)'),
(3781, 'PHA/USDT', '2025-06-01 16:18:50', '0.13070000', '72.05', '0.00037017', '0.00021353', '0.13048523', '0.12931150', '0.12813777', '0.12931150', '0.12898660', '0.12967690', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3782, 'DGB/USDT', '2025-06-01 16:19:31', '0.01922000', '0.00', '0.00000000', '0.00000000', '0.01922000', '0.01922000', '0.01922000', '0.01922000', '0.01922000', '0.01922000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3783, 'ICX/USDT', '2025-06-01 16:19:11', '0.11560000', '57.16', '0.00015349', '0.00017253', '0.11585113', '0.11546500', '0.11507887', '0.11546500', '0.11504600', '0.11492300', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Ichimoku: Fiyat ye?il bulutun üstünde, TK çapraz? yukar?'),
(3784, 'BCH/USDT', '2025-06-01 16:18:50', '400.20000000', '55.31', '0.23855388', '0.18419710', '400.61671469', '399.83000000', '399.04328531', '399.83000000', '399.51180000', '400.06160000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3785, 'AWE/USDT', '2025-06-01 16:18:50', '0.05693600', '66.38', '0.00014231', '0.00012370', '0.05701899', '0.05667265', '0.05632631', '0.05667265', '0.05640342', '0.05646088', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3786, 'TAO/USDT', '2025-06-01 16:20:58', '415.85000000', '65.78', '0.78924628', '0.55769102', '416.86615031', '413.84900000', '410.83184969', '413.84900000', '413.07580000', '414.88470000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 89.99, %D: 91.08)'),
(3787, 'EOS/USDT', '2025-05-31 19:22:04', '0.77990000', '55.13', '0.00088629', '0.00001279', '0.78297291', '0.77638000', '0.76978709', '0.77638000', '0.77995600', '0.77375200', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.7799, SAR: 0.7704)'),
(3788, 'SPELL/USDT', '2025-06-01 16:15:27', '0.00052350', '57.95', '0.00000061', '0.00000040', '0.00052435', '0.00052241', '0.00052048', '0.00052241', '0.00052173', '0.00052303', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3789, 'XRP/USDT', '2025-06-01 16:25:11', '2.13680000', '54.74', '0.00152343', '0.00163833', '2.14165755', '2.13603500', '2.13041245', '2.13603500', '2.13278200', '2.13448300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3790, 'JTO/USDT', '2025-06-01 16:16:07', '1.64430000', '68.43', '0.00338498', '0.00281218', '1.64894452', '1.63835500', '1.62776548', '1.63835500', '1.63397600', '1.63592800', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 94.61, %D: 95.31)'),
(3791, 'NEIRO/USDT', '2025-06-01 16:26:12', '0.00043990', '57.09', '0.00000109', '0.00000127', '0.00044172', '0.00043970', '0.00043767', '0.00043970', '0.00043630', '0.00043576', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3792, 'YFI/USDT', '2025-06-01 16:17:49', '5201.00000000', '66.97', '4.23277129', '2.10333193', '5200.68676855', '5185.20000000', '5169.71323145', '5185.20000000', '5182.66000000', '5205.45000000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3793, 'REN/USDT', '2025-06-01 15:39:35', '0.04800000', '0.00', '0.00000000', '0.00000000', '0.04800000', '0.04800000', '0.04800000', '0.04800000', '0.04800000', '0.04800000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3794, 'LINA/USDT', '2025-06-01 16:26:42', '0.00074200', '0.00', '0.00000000', '0.00000000', '0.00074200', '0.00074200', '0.00074200', '0.00074200', '0.00074200', '0.00074200', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3795, 'WAVES/USDT', '2025-06-01 16:09:27', '1.33550000', '0.00', '0.00000000', '0.00000000', '1.33550000', '1.33550000', '1.33550000', '1.33550000', '1.33550000', '1.33550000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3796, 'MDT/USDT', '2025-06-01 15:55:07', '0.06331000', '0.00', '0.00000000', '0.00000000', '0.06331000', '0.06331000', '0.06331000', '0.06331000', '0.06331000', '0.06331000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3797, 'BOND/USDT', '2025-06-01 01:30:54', '1.06900000', '0.00', '0.00000000', '0.00000000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3798, 'STMX/USDT', '2025-06-01 01:26:05', '0.00388000', '0.00', '0.00000000', '0.00000000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3799, 'AGIX/USDT', '2025-06-01 16:02:31', '0.70090000', '0.00', '0.00000000', '0.00000000', '0.70090000', '0.70090000', '0.70090000', '0.70090000', '0.70090000', '0.70090000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3800, 'LOOM/USDT', '2025-06-01 16:21:53', '0.08112000', '0.00', '0.00000000', '0.00000000', '0.08112000', '0.08112000', '0.08112000', '0.08112000', '0.08112000', '0.08112000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3801, 'ALPACA/USDT', '2025-06-01 16:26:36', '1.19000000', '0.00', '0.00000000', '0.00000000', '1.19000000', '1.19000000', '1.19000000', '1.19000000', '1.19000000', '1.19000000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3802, 'OCEAN/USDT', '2025-06-01 16:21:34', '0.70700000', '0.00', '0.00000000', '0.00000000', '0.70700000', '0.70700000', '0.70700000', '0.70700000', '0.70700000', '0.70700000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3803, 'VTHO/USDT', '2025-06-01 16:17:49', '0.00212500', '77.43', '0.00000393', '0.00000308', '0.00212427', '0.00211390', '0.00210353', '0.00211390', '0.00210830', '0.00211064', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3804, 'LQTY/USDT', '2025-06-01 16:20:35', '0.81940000', '63.78', '0.00122061', '0.00121614', '0.81942220', '0.81645000', '0.81347780', '0.81645000', '0.81265000', '0.81128200', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.8194, SAR: 0.8145)'),
(3805, 'BNX/USDT', '2025-06-01 15:59:50', '2.00000000', '0.00', '0.00000000', '0.00000000', '2.00000000', '2.00000000', '2.00000000', '2.00000000', '2.00000000', '2.00000000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3806, 'AVAX/USDT', '2025-05-31 19:36:18', '20.95900000', '68.81', '0.04800608', '0.04862833', '20.98987308', '20.91410000', '20.83832692', '20.91410000', '20.78792000', '20.75471000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 88.61, %D: 91.34)'),
(3807, 'ETHFI/USDT', '2025-05-31 19:33:55', '1.15060000', '69.41', '0.00558131', '0.00534889', '1.15479420', '1.14235000', '1.12990580', '1.14235000', '1.12927600', '1.13225200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.38, %D: 84.88)'),
(3808, 'HBAR/USDT', '2025-06-01 16:26:45', '0.16581000', '64.70', '0.00019992', '0.00018027', '0.16599829', '0.16549600', '0.16499371', '0.16549600', '0.16508980', '0.16548930', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3809, 'ALGO/USDT', '2025-06-01 16:25:11', '0.19240000', '59.50', '0.00030268', '0.00029635', '0.19295093', '0.19214000', '0.19132907', '0.19214000', '0.19151000', '0.19178300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3810, 'BLZ/USDT', '2025-06-01 16:27:01', '0.06836000', '0.00', '0.00000000', '0.00000000', '0.06836000', '0.06836000', '0.06836000', '0.06836000', '0.06836000', '0.06836000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3811, 'ONE/USDT', '2025-05-31 19:39:29', '0.01202000', '44.55', '0.00001254', '0.00001867', '0.01208330', '0.01205150', '0.01201970', '0.01205150', '0.01199660', '0.01198410', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0120, SAR: 0.0121)'),
(3812, 'LIT/USDT', '2025-06-01 01:33:41', '0.59200000', '0.00', '0.00000000', '0.00000000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3813, 'DOT/USDT', '2025-06-01 16:25:31', '4.02000000', '59.63', '0.00455720', '0.00442229', '4.02760478', '4.01610000', '4.00459522', '4.01610000', '4.00590000', '4.01303000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3814, 'PEOPLE/USDT', '2025-05-31 19:36:29', '0.02015000', '58.21', '0.00004931', '0.00005233', '0.02019316', '0.02012200', '0.02005084', '0.02012200', '0.01996460', '0.01994860', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3815, 'S/USDT', '2025-05-31 20:35:32', '0.39330000', '40.42', '-0.00035626', '-0.00030770', '0.39524744', '0.39398000', '0.39271256', '0.39398000', '0.39444000', '0.39484100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 25.40, -DI: 32.51)'),
(3816, 'SXP/USDT', '2025-05-31 19:36:55', '0.17370000', '59.70', '0.00021271', '0.00020981', '0.17391423', '0.17352500', '0.17313577', '0.17352500', '0.17296800', '0.17311600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3817, 'ONDO/USDT', '2025-06-01 16:25:11', '0.81890000', '59.43', '0.00123109', '0.00122463', '0.82071425', '0.81762000', '0.81452575', '0.81762000', '0.81491400', '0.81590000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3818, 'MANA/USDT', '2025-05-31 20:33:04', '0.27320000', '37.80', '-0.00011204', '-0.00000683', '0.27469514', '0.27396500', '0.27323486', '0.27396500', '0.27366000', '0.27377300', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3819, 'RSR/USDT', '2025-05-31 20:50:30', '0.00716300', '46.02', '-0.00000170', '-0.00000123', '0.00717895', '0.00716645', '0.00715395', '0.00716645', '0.00717342', '0.00717227', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3820, 'COMP/USDT', '2025-06-01 16:18:30', '40.14000000', '72.15', '0.05276933', '0.03178359', '40.12922299', '39.96150000', '39.79377701', '39.96150000', '39.91440000', '40.01350000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3821, 'GPS/USDT', '2025-06-01 16:17:27', '0.02339000', '69.18', '0.00002749', '0.00001139', '0.02335432', '0.02325100', '0.02314768', '0.02325100', '0.02323860', '0.02330970', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0234, SAR: 0.0232)'),
(3822, 'SCRT/USDT', '2025-05-31 20:01:20', '0.17940000', '53.20', '0.00006469', '0.00003087', '0.17973604', '0.17917000', '0.17860396', '0.17917000', '0.17933600', '0.17883400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3823, 'ACT/USDT', '2025-06-01 16:14:02', '0.05215000', '75.16', '0.00012386', '0.00008588', '0.05219088', '0.05185650', '0.05152212', '0.05185650', '0.05171220', '0.05181310', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 96.68, %D: 96.94)'),
(3824, 'KEY/USDT', '2025-06-01 16:27:04', '0.00251770', '0.00', '0.00000000', '0.00000000', '0.00251770', '0.00251770', '0.00251770', '0.00251770', '0.00251770', '0.00251770', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3825, 'ACH/USDT', '2025-06-01 16:14:22', '0.02151400', '62.11', '0.00003164', '0.00002612', '0.02153989', '0.02145515', '0.02137041', '0.02145515', '0.02139806', '0.02146842', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0215, SAR: 0.0214)'),
(3826, 'BSV/USDT', '2025-06-01 16:18:30', '33.00000000', '71.60', '0.04581383', '0.03222417', '33.00846863', '32.87700000', '32.74553137', '32.87700000', '32.82280000', '32.84960000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3827, 'PNUT/USDT', '2025-06-01 16:24:50', '0.25946000', '67.20', '0.00069449', '0.00067924', '0.26003893', '0.25856950', '0.25710007', '0.25856950', '0.25698720', '0.25727980', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3828, 'AIXBT/USDT', '2025-06-01 16:25:11', '0.19130000', '56.38', '0.00072397', '0.00080659', '0.19230025', '0.19118700', '0.19007375', '0.19118700', '0.18909500', '0.18855300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3829, 'JASMY/USDT', '2025-05-31 19:56:29', '0.01550300', '49.24', '-0.00001039', '-0.00001092', '0.01556986', '0.01550520', '0.01544054', '0.01550520', '0.01551182', '0.01550499', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3830, 'CGPT/USDT', '2025-06-01 16:17:28', '0.11361000', '76.18', '0.00038062', '0.00026675', '0.11363868', '0.11250400', '0.11136932', '0.11250400', '0.11207780', '0.11251510', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3831, 'HOT/USDT', '2025-06-01 16:18:30', '0.00098500', '73.96', '0.00000178', '0.00000113', '0.00098469', '0.00097900', '0.00097331', '0.00097900', '0.00097720', '0.00097972', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3832, 'RUNE/USDT', '2025-05-31 20:35:38', '1.68700000', '41.45', '-0.00213226', '-0.00198715', '1.69778707', '1.69035000', '1.68291293', '1.69035000', '1.69332000', '1.69167000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 30.00, -DI: 32.70)'),
(3833, 'CHZ/USDT', '2025-05-31 20:05:32', '0.03900000', '53.02', '0.00000934', '0.00000654', '0.03910996', '0.03895550', '0.03880104', '0.03895550', '0.03900600', '0.03891530', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3834, 'TNSR/USDT', '2025-06-01 16:20:58', '0.12910000', '67.19', '0.00036045', '0.00029453', '0.12933595', '0.12840000', '0.12746405', '0.12840000', '0.12781200', '0.12805200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.55, %D: 84.47)'),
(3835, 'GRT/USDT', '2025-06-01 16:14:22', '0.09401000', '63.32', '0.00010905', '0.00008035', '0.09410122', '0.09380250', '0.09350378', '0.09380250', '0.09366160', '0.09389820', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3836, 'CHR/USDT', '2025-06-01 16:18:30', '0.08400000', '72.44', '0.00021228', '0.00013137', '0.08384908', '0.08323500', '0.08262092', '0.08323500', '0.08299000', '0.08316800', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3837, 'ZEC/USDT', '2025-06-01 16:24:28', '51.90000000', '59.91', '0.12045660', '0.08296647', '52.13273102', '51.67000000', '51.20726898', '51.67000000', '51.54860000', '51.66910000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.99, %D: 90.57)'),
(3838, 'JOE/USDT', '2025-06-01 16:17:48', '0.15972000', '78.45', '0.00032529', '0.00021688', '0.15957405', '0.15865050', '0.15772695', '0.15865050', '0.15824240', '0.15881080', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 95.29, %D: 95.38)'),
(3839, 'PARTI/USDT', '2025-06-01 16:22:01', '0.23230000', '70.66', '0.00065578', '0.00046896', '0.23243250', '0.23049500', '0.22855750', '0.23049500', '0.22964600', '0.23036100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 91.95, %D: 92.52)'),
(3840, 'TST/USDT', '2025-06-01 16:24:06', '0.04520000', '59.70', '0.00003757', '0.00000514', '0.04519153', '0.04493000', '0.04466847', '0.04493000', '0.04489400', '0.04498800', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0452, SAR: 0.0447)'),
(3841, 'BANANA/USDT', '2025-06-01 16:18:09', '20.98600000', '63.98', '0.03388156', '0.03094019', '21.01705898', '20.91965000', '20.82224102', '20.91965000', '20.85506000', '20.89987000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3842, 'DEGO/USDT', '2025-06-01 16:17:08', '2.62470000', '43.66', '-0.00008503', '0.00012349', '2.62781510', '2.62597000', '2.62412490', '2.62597000', '2.62562000', '2.62602500', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3843, 'KAITO/USDT', '2025-06-01 16:26:39', '1.93530000', '72.99', '0.00996910', '0.01003295', '1.94382081', '1.92370500', '1.90358919', '1.92370500', '1.90017800', '1.89662800', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3844, 'BEL/USDT', '2025-06-01 16:18:09', '0.28000000', '72.09', '0.00061828', '0.00048126', '0.28041818', '0.27862500', '0.27683182', '0.27862500', '0.27778600', '0.27846900', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3845, 'PYTH/USDT', '2025-06-01 16:21:47', '0.11649000', '71.57', '0.00024504', '0.00019952', '0.11662456', '0.11598050', '0.11533644', '0.11598050', '0.11553960', '0.11596480', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3846, 'PROM/USDT', '2025-06-01 16:18:09', '5.37100000', '61.52', '0.00125075', '0.00048571', '5.37253563', '5.36480000', '5.35706437', '5.36480000', '5.36564000', '5.36931000', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Ichimoku: Fiyat ye?il bulutun üstünde, TK çapraz? yukar?'),
(3847, 'FIL/USDT', '2025-06-01 16:24:50', '2.53200000', '66.91', '0.00360170', '0.00295855', '2.53542680', '2.52590000', '2.51637320', '2.52590000', '2.51978000', '2.52591000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.97, %D: 83.07)'),
(3848, '1000SATS/USDT', '2025-06-01 16:20:36', '0.00004540', '66.98', '0.00000011', '0.00000008', '0.00004542', '0.00004510', '0.00004478', '0.00004510', '0.00004493', '0.00004509', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.78, %D: 91.85)'),
(3849, 'EDU/USDT', '2025-06-01 16:24:46', '0.13620000', '74.38', '0.00060650', '0.00055930', '0.13687265', '0.13538500', '0.13389735', '0.13538500', '0.13424000', '0.13398900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 89.60, %D: 90.48)'),
(3850, 'FORTH/USDT', '2025-06-01 16:18:09', '2.28800000', '74.04', '0.00413295', '0.00320948', '2.29041635', '2.27940000', '2.26838365', '2.27940000', '2.27330000', '2.28014000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 88.85, %D: 89.51)'),
(3851, 'FIDA/USDT', '2025-06-01 16:14:22', '0.06871000', '65.91', '0.00009832', '0.00006165', '0.06873406', '0.06845700', '0.06817994', '0.06845700', '0.06834560', '0.06861260', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 92.65, %D: 95.31)'),
(3852, 'AXL/USDT', '2025-06-01 16:17:48', '0.31840000', '76.92', '0.00080226', '0.00058036', '0.31833533', '0.31623000', '0.31412467', '0.31623000', '0.31516600', '0.31556100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 94.40, %D: 95.57)'),
(3853, 'TUT/USDT', '2025-06-01 16:22:44', '0.02737000', '73.67', '0.00012198', '0.00009963', '0.02741213', '0.02709600', '0.02677987', '0.02709600', '0.02690780', '0.02691780', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 90.28, %D: 91.53)'),
(3854, 'ATOM/USDT', '2025-06-01 16:22:44', '4.29800000', '72.60', '0.00585247', '0.00488514', '4.30320216', '4.28755000', '4.27189784', '4.28755000', '4.27730000', '4.28432000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 90.14, %D: 90.63)');
INSERT INTO `price_analysis` (`id`, `symbol`, `analysis_time`, `price`, `rsi`, `macd`, `macd_signal`, `bollinger_upper`, `bollinger_middle`, `bollinger_lower`, `ma20`, `ma50`, `ma100`, `ma200`, `trade_signal`, `buy_signals`, `sell_signals`, `neutral_signals`, `notes`) VALUES
(3855, 'ANIME/USDT', '2025-06-01 16:21:43', '0.02524000', '63.38', '0.00009568', '0.00009181', '0.02541435', '0.02520550', '0.02499665', '0.02520550', '0.02500980', '0.02491840', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0252, SAR: 0.0254)'),
(3856, 'IMX/USDT', '2025-06-01 01:55:24', '0.55130000', '38.77', '-0.00028799', '-0.00024245', '0.55316188', '0.55223000', '0.55129812', '0.55223000', '0.55268200', '0.55434500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3857, 'ICP/USDT', '2025-06-01 16:26:12', '4.87100000', '60.87', '0.00812386', '0.00816065', '4.88354528', '4.86420000', '4.84485472', '4.86420000', '4.84618000', '4.85321000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3858, 'ZEN/USDT', '2025-06-01 16:24:50', '10.51900000', '64.69', '0.12462672', '0.10682787', '10.70590651', '10.33800000', '9.97009349', '10.33800000', '10.14686000', '10.09366000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 10.5190, SAR: 10.9465)'),
(3859, 'CHZ/USDT', '2025-06-01 16:17:27', '0.03864000', '73.67', '0.00005755', '0.00004327', '0.03864229', '0.03848850', '0.03833471', '0.03848850', '0.03840240', '0.03847630', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3860, '1000CAT/USDT', '2025-06-01 01:39:04', '0.00722600', '53.40', '-0.00000263', '-0.00000601', '0.00723940', '0.00721675', '0.00719410', '0.00721675', '0.00723064', '0.00725799', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3861, 'BNT/USDT', '2025-06-01 16:17:28', '0.62780000', '69.10', '0.00082724', '0.00051208', '0.62756781', '0.62485550', '0.62214319', '0.62485550', '0.62412800', '0.62579030', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3862, 'OP/USDT', '2025-06-01 16:15:04', '0.63940000', '75.40', '0.00126444', '0.00077726', '0.63981314', '0.63581500', '0.63181686', '0.63581500', '0.63488400', '0.63765600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3863, 'RSR/USDT', '2025-06-01 01:44:25', '0.00722800', '49.46', '-0.00000531', '-0.00000676', '0.00724033', '0.00722400', '0.00720767', '0.00722400', '0.00723920', '0.00725056', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 33.72, -DI: 30.66)'),
(3864, 'SCRT/USDT', '2025-06-01 01:12:25', '0.18230000', '44.92', '-0.00010658', '-0.00003418', '0.18334924', '0.18260500', '0.18186076', '0.18260500', '0.18250600', '0.18185800', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3865, 'EIGEN/USDT', '2025-06-01 16:23:46', '1.31500000', '61.39', '0.00310287', '0.00283531', '1.32113414', '1.31057500', '1.30001586', '1.31057500', '1.30540200', '1.30896800', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3866, 'EGLD/USDT', '2025-06-01 02:11:36', '15.48800000', '66.78', '0.00979740', '0.00188102', '15.48735639', '15.44060000', '15.39384361', '15.44060000', '15.44086000', '15.45300000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3867, 'RDNT/USDT', '2025-06-01 01:23:39', '0.02398000', '49.33', '0.00000045', '0.00000480', '0.02404855', '0.02399400', '0.02393945', '0.02399400', '0.02397120', '0.02395480', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3868, 'TURBO/USDT', '2025-06-01 16:24:20', '0.00422050', '62.40', '0.00001051', '0.00000934', '0.00423828', '0.00420720', '0.00417613', '0.00420720', '0.00418896', '0.00420277', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3869, 'REI/USDT', '2025-06-01 16:16:47', '0.01774000', '64.08', '0.00003276', '0.00002556', '0.01775831', '0.01766300', '0.01756769', '0.01766300', '0.01762520', '0.01765750', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3870, 'AVAX/USDT', '2025-06-01 16:24:49', '20.37800000', '61.55', '0.03013282', '0.02827223', '20.43317203', '20.34880000', '20.26442797', '20.34880000', '20.29000000', '20.32475000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3871, 'REZ/USDT', '2025-06-01 03:05:46', '0.01116000', '33.11', '-0.00001505', '-0.00001114', '0.01123161', '0.01119500', '0.01115839', '0.01119500', '0.01122240', '0.01125260', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 39.11, -DI: 34.31)'),
(3872, '1MBABYDOGE/USDT', '2025-06-01 16:17:07', '0.00136700', '75.19', '0.00000276', '0.00000205', '0.00136699', '0.00136009', '0.00135318', '0.00136009', '0.00135601', '0.00136084', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3873, 'CETUS/USDT', '2025-06-01 16:23:26', '0.13661000', '77.68', '0.00070609', '0.00054969', '0.13698841', '0.13495650', '0.13292459', '0.13495650', '0.13400500', '0.13400040', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3874, 'ENJ/USDT', '2025-06-01 01:49:54', '0.07373000', '41.64', '-0.00000640', '-0.00000109', '0.07397055', '0.07379300', '0.07361545', '0.07379300', '0.07382500', '0.07396090', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3875, 'ATA/USDT', '2025-06-01 01:31:12', '0.04410000', '48.16', '-0.00002677', '-0.00002494', '0.04424000', '0.04412000', '0.04400000', '0.04412000', '0.04418800', '0.04426200', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3876, 'KNC/USDT', '2025-06-01 01:36:32', '0.32310000', '55.19', '0.00001080', '-0.00004782', '0.32332942', '0.32270500', '0.32208058', '0.32270500', '0.32287000', '0.32339500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3877, 'ORDI/USDT', '2025-06-01 16:24:11', '8.42200000', '55.84', '0.01188159', '0.01427574', '8.43780771', '8.41915000', '8.40049229', '8.41915000', '8.37380000', '8.39235000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3878, 'SXP/USDT', '2025-06-01 01:33:44', '0.17390000', '42.39', '-0.00007867', '-0.00006644', '0.17432396', '0.17408500', '0.17384604', '0.17408500', '0.17424600', '0.17437600', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 26.69, -DI: 33.11)'),
(3879, 'RONIN/USDT', '2025-06-01 01:33:54', '0.60190000', '46.92', '-0.00019689', '-0.00019527', '0.60273049', '0.60204500', '0.60135951', '0.60204500', '0.60258200', '0.60308600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3880, 'ILV/USDT', '2025-06-01 01:33:57', '12.74800000', '32.94', '-0.02225471', '-0.02015288', '12.82779800', '12.78780000', '12.74780200', '12.78780000', '12.83932000', '12.85334000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 35.04, -DI: 36.81)'),
(3881, 'PEOPLE/USDT', '2025-06-01 16:23:46', '0.02018000', '72.02', '0.00005301', '0.00004156', '0.02019472', '0.02005300', '0.01991128', '0.02005300', '0.01997120', '0.02002210', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 91.30, %D: 91.95)'),
(3882, 'A/USDT', '2025-06-01 16:16:47', '0.60930000', '59.02', '0.00098078', '0.00073195', '0.61068464', '0.60756900', '0.60445336', '0.60756900', '0.60628420', '0.60994780', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3883, 'AXS/USDT', '2025-06-01 16:14:44', '2.49500000', '66.99', '0.00287913', '0.00188298', '2.49650000', '2.48790000', '2.47930000', '2.48790000', '2.48498000', '2.49009000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3884, 'SNX/USDT', '2025-06-01 01:41:51', '0.67700000', '52.17', '0.00006576', '-0.00009899', '0.67829120', '0.67645000', '0.67460880', '0.67645000', '0.67686000', '0.67806000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3885, 'OM/USDT', '2025-06-01 16:17:07', '0.30627000', '66.76', '0.00061042', '0.00059899', '0.30662823', '0.30542900', '0.30422977', '0.30542900', '0.30400320', '0.30427640', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3886, 'CFX/USDT', '2025-06-01 01:44:28', '0.07677000', '58.56', '0.00002449', '0.00001241', '0.07680596', '0.07665450', '0.07650304', '0.07665450', '0.07664860', '0.07667280', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3887, 'BAT/USDT', '2025-06-01 01:41:48', '0.12970000', '46.22', '-0.00005499', '-0.00006391', '0.12998238', '0.12975000', '0.12951762', '0.12975000', '0.12984000', '0.13032300', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3888, 'MUBARAK/USDT', '2025-06-01 16:26:53', '0.03885000', '65.32', '0.00013834', '0.00013682', '0.03903396', '0.03867900', '0.03832404', '0.03867900', '0.03838540', '0.03848850', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3889, 'DYDX/USDT', '2025-06-01 16:26:52', '0.54300000', '62.94', '0.00103645', '0.00094189', '0.54345330', '0.54080000', '0.53814670', '0.54080000', '0.53866000', '0.54039000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3890, 'SAND/USDT', '2025-06-01 16:16:07', '0.26805000', '61.59', '0.00029525', '0.00023544', '0.26855216', '0.26757950', '0.26660684', '0.26757950', '0.26722320', '0.26774750', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.45, %D: 89.03)'),
(3891, 'SAGA/USDT', '2025-06-01 16:17:07', '0.28780000', '77.22', '0.00119843', '0.00088410', '0.28784779', '0.28465500', '0.28146221', '0.28465500', '0.28301800', '0.28395500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3892, 'BROCCOLI714/USDT', '2025-06-01 16:16:42', '0.02615000', '76.04', '0.00008321', '0.00006598', '0.02621454', '0.02597200', '0.02572946', '0.02597200', '0.02585940', '0.02593930', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 92.32, %D: 92.90)'),
(3893, 'ETH/USDT', '2025-06-01 16:16:07', '2488.05000000', '59.86', '1.55720848', '1.01656634', '2490.74488319', '2484.83250000', '2478.92011681', '2484.83250000', '2483.46340000', '2490.15600000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 86.14, %D: 92.01)'),
(3894, 'SUI/USDT', '2025-06-01 16:26:32', '3.27180000', '63.85', '0.00665390', '0.00663277', '3.27811616', '3.26415500', '3.25019384', '3.26415500', '3.24863000', '3.25045500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3895, 'BNB/USDT', '2025-06-01 16:15:47', '649.36000000', '57.49', '0.35209652', '0.31440484', '650.17329486', '649.05950000', '647.94570514', '649.05950000', '648.51640000', '649.15850000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3896, 'SSV/USDT', '2025-06-01 16:20:57', '8.66800000', '67.45', '0.04344892', '0.02984827', '8.72486954', '8.55855000', '8.39223046', '8.55855000', '8.52946000', '8.56827000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 88.92, %D: 89.22)'),
(3897, 'RPL/USDT', '2025-06-01 01:46:51', '4.78700000', '38.04', '-0.00462955', '-0.00530776', '4.80411798', '4.79410000', '4.78408202', '4.79410000', '4.80780000', '4.82780000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 34.15, -DI: 36.27)'),
(3898, 'MANA/USDT', '2025-06-01 05:21:43', '0.27010000', '55.03', '-0.00013751', '-0.00030343', '0.27023102', '0.26945000', '0.26866898', '0.26945000', '0.27032400', '0.27126400', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3899, 'JASMY/USDT', '2025-06-01 01:47:07', '0.01533200', '44.36', '-0.00000351', '-0.00000502', '0.01536350', '0.01533430', '0.01530510', '0.01533430', '0.01535428', '0.01539648', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 25.42, -DI: 29.48)'),
(3900, 'STO/USDT', '2025-06-01 01:47:10', '0.09482000', '67.04', '0.00013661', '0.00006551', '0.09491800', '0.09438650', '0.09385500', '0.09438650', '0.09437100', '0.09449360', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.35, %D: 89.78)'),
(3901, 'BOME/USDT', '2025-06-01 16:23:05', '0.00180300', '68.04', '0.00000581', '0.00000514', '0.00180990', '0.00179510', '0.00178030', '0.00179510', '0.00178408', '0.00179062', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.14, %D: 90.09)'),
(3902, 'STRK/USDT', '2025-06-01 16:14:44', '0.13300000', '74.11', '0.00037070', '0.00027044', '0.13299532', '0.13209000', '0.13118468', '0.13209000', '0.13157200', '0.13184600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3903, 'FLOW/USDT', '2025-06-01 16:15:47', '0.35800000', '46.60', '0.00018706', '0.00016580', '0.35979455', '0.35865000', '0.35750545', '0.35865000', '0.35822000', '0.35921000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 27.24, -DI: 24.53)'),
(3904, 'MANTA/USDT', '2025-06-01 01:55:21', '0.24050000', '34.73', '-0.00029607', '-0.00027329', '0.24201907', '0.24109500', '0.24017093', '0.24109500', '0.24151000', '0.24226100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 37.19, -DI: 43.30)'),
(3905, 'LAYER/USDT', '2025-06-01 16:16:27', '0.78240000', '64.30', '0.00147848', '0.00122072', '0.78373954', '0.78010500', '0.77647046', '0.78010500', '0.77749400', '0.77553100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 81.48, %D: 88.81)'),
(3906, 'BMT/USDT', '2025-06-01 16:27:12', '0.08870000', '64.76', '0.00036002', '0.00036803', '0.08892363', '0.08833000', '0.08773637', '0.08833000', '0.08731600', '0.08670100', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0887, SAR: 0.0880)'),
(3907, 'PIXEL/USDT', '2025-06-01 05:21:46', '0.04320000', '51.57', '-0.00007244', '-0.00011608', '0.04323943', '0.04306400', '0.04288857', '0.04306400', '0.04338080', '0.04365410', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 48.60, -DI: 25.72)'),
(3908, 'MEME/USDT', '2025-06-01 16:21:40', '0.00189500', '72.09', '0.00000570', '0.00000435', '0.00189948', '0.00188240', '0.00186532', '0.00188240', '0.00187450', '0.00188407', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3909, 'AUCTION/USDT', '2025-06-01 16:24:40', '10.38300000', '62.51', '0.01766702', '0.01530064', '10.40789789', '10.35745000', '10.30700211', '10.35745000', '10.32708000', '10.34923000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.08, %D: 85.25)'),
(3910, 'XAI/USDT', '2025-06-01 16:17:07', '0.06948000', '74.30', '0.00022518', '0.00018008', '0.06953944', '0.06898050', '0.06842156', '0.06898050', '0.06863740', '0.06876430', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3911, 'JUP/USDT', '2025-06-01 16:23:05', '0.51480000', '68.12', '0.00100008', '0.00085864', '0.51589072', '0.51320000', '0.51050928', '0.51320000', '0.51136800', '0.51358700', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 86.09, %D: 86.86)'),
(3912, 'BIO/USDT', '2025-06-01 16:24:28', '0.06545000', '53.31', '0.00006830', '0.00007931', '0.06562991', '0.06545250', '0.06527509', '0.06545250', '0.06519920', '0.06541680', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0654, SAR: 0.0651)'),
(3913, 'DOGE/USDT', '2025-06-01 16:24:28', '0.18912000', '62.38', '0.00029582', '0.00027618', '0.18951628', '0.18879200', '0.18806772', '0.18879200', '0.18816580', '0.18857900', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3914, 'ETHFI/USDT', '2025-06-01 16:26:32', '1.11680000', '60.79', '0.00359371', '0.00391196', '1.12273010', '1.11435000', '1.10596990', '1.11435000', '1.10530400', '1.10613800', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3915, 'FET/USDT', '2025-06-01 16:26:32', '0.73730000', '56.30', '0.00116838', '0.00122797', '0.73985171', '0.73667500', '0.73349829', '0.73667500', '0.73388400', '0.73590400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3916, 'S/USDT', '2025-06-01 16:25:31', '0.38700000', '61.24', '0.00082727', '0.00078645', '0.38833876', '0.38621000', '0.38408124', '0.38621000', '0.38454800', '0.38543100', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3917, 'ARPA/USDT', '2025-06-01 16:16:47', '0.02187000', '70.94', '0.00004007', '0.00002543', '0.02192920', '0.02177250', '0.02161580', '0.02177250', '0.02175680', '0.02184030', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 94.20, %D: 97.10)'),
(3918, 'TIA/USDT', '2025-06-01 16:14:44', '2.16900000', '65.72', '0.00208766', '0.00102781', '2.16978331', '2.16224000', '2.15469669', '2.16224000', '2.16120200', '2.16795200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 93.56, %D: 93.61)'),
(3919, 'ARB/USDT', '2025-06-01 16:16:07', '0.33380000', '61.17', '0.00044611', '0.00031565', '0.33454424', '0.33294000', '0.33133576', '0.33294000', '0.33255400', '0.33383700', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.18, %D: 92.66)'),
(3920, 'HAEDAL/USDT', '2025-06-01 16:23:05', '0.13068400', '68.68', '0.00055976', '0.00053634', '0.13145283', '0.13012145', '0.12879007', '0.13012145', '0.12900602', '0.12920191', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.1307, SAR: 0.1313)'),
(3921, 'CAKE/USDT', '2025-06-01 16:21:19', '2.29900000', '68.30', '0.00414101', '0.00367249', '2.30131294', '2.29184500', '2.28237706', '2.29184500', '2.28302400', '2.28956000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3922, 'NOT/USDT', '2025-06-01 16:22:44', '0.00220600', '68.73', '0.00000519', '0.00000435', '0.00221214', '0.00219750', '0.00218286', '0.00219750', '0.00218904', '0.00219544', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.49, %D: 88.31)'),
(3923, 'VOXEL/USDT', '2025-06-01 02:08:32', '0.05723000', '35.82', '-0.00013467', '-0.00008952', '0.05815432', '0.05756950', '0.05698468', '0.05756950', '0.05757800', '0.05776940', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 33.52, -DI: 39.23)'),
(3924, 'ZRX/USDT', '2025-06-01 16:14:02', '0.23130000', '68.72', '0.00027559', '0.00019971', '0.23136746', '0.23067500', '0.22998254', '0.23067500', '0.23027600', '0.23084800', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 97.22, %D: 99.07)'),
(3925, 'WLD/USDT', '2025-06-01 16:16:27', '1.11520000', '66.64', '0.00203529', '0.00145432', '1.11722105', '1.11058500', '1.10394895', '1.11058500', '1.10859600', '1.11192200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 86.94, %D: 90.59)'),
(3926, 'LDO/USDT', '2025-06-01 16:26:12', '0.84660000', '51.43', '0.00129433', '0.00145340', '0.85114941', '0.84677500', '0.84240059', '0.84677500', '0.84366800', '0.84545800', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.8466, SAR: 0.8511)'),
(3927, 'BABY/USDT', '2025-06-01 16:21:56', '0.06531000', '66.98', '0.00019105', '0.00015925', '0.06553544', '0.06497650', '0.06441756', '0.06497650', '0.06468540', '0.06466290', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 86.79, %D: 90.47)'),
(3928, 'LISTA/USDT', '2025-06-01 02:11:33', '0.20910000', '56.85', '-0.00005452', '-0.00015622', '0.20922534', '0.20867000', '0.20811466', '0.20867000', '0.20905200', '0.20973100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3929, 'BERA/USDT', '2025-06-01 16:18:46', '2.28800000', '71.99', '0.00450503', '0.00315501', '2.29006580', '2.27645000', '2.26283420', '2.27645000', '2.27136000', '2.27797000', NULL, 'SELL', 0, 2, 5, 'breakout_detection: SELL - Volatilite patlamas? ve dü?en mum | volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 84.22, %D: 86.51)'),
(3930, 'ORCA/USDT', '2025-06-01 16:15:47', '2.62100000', '60.39', '0.00289480', '0.00169261', '2.62300000', '2.61380000', '2.60460000', '2.61380000', '2.61062000', '2.62127000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.14, %D: 89.47)'),
(3931, 'SOL/USDT', '2025-06-01 16:15:47', '151.46000000', '58.21', '0.13152713', '0.08930780', '151.72629532', '151.24700000', '150.76770468', '151.24700000', '151.14440000', '151.53160000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.84, %D: 90.61)'),
(3932, 'ADA/USDT', '2025-06-01 16:25:31', '0.66400000', '62.80', '0.00108851', '0.00105213', '0.66537694', '0.66296000', '0.66054306', '0.66296000', '0.66054000', '0.66150400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3933, 'ENA/USDT', '2025-06-01 16:25:52', '0.30570000', '55.04', '0.00057342', '0.00060811', '0.30702203', '0.30557000', '0.30411797', '0.30557000', '0.30412600', '0.30512500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3934, 'SEI/USDT', '2025-06-01 16:26:32', '0.19090000', '67.89', '0.00032892', '0.00033577', '0.19129139', '0.19057000', '0.18984861', '0.19057000', '0.18977800', '0.18996600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3935, 'MOVE/USDT', '2025-06-01 16:25:52', '0.13610000', '55.75', '0.00024180', '0.00023360', '0.13661529', '0.13597000', '0.13532471', '0.13597000', '0.13549600', '0.13570900', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3936, 'TON/USDT', '2025-06-01 16:24:28', '3.11940000', '64.31', '0.00465606', '0.00414940', '3.12607982', '3.11296000', '3.09984018', '3.11296000', '3.10457200', '3.11064400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3937, 'PENGU/USDT', '2025-06-01 16:24:07', '0.01036800', '72.17', '0.00003540', '0.00003052', '0.01040349', '0.01031230', '0.01022111', '0.01031230', '0.01025016', '0.01028103', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3938, 'INIT/USDT', '2025-06-01 16:24:06', '0.73320000', '62.15', '0.00106238', '0.00049299', '0.73360902', '0.72934500', '0.72508098', '0.72934500', '0.72836600', '0.73414500', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.7332, SAR: 0.7281)'),
(3939, 'ZRO/USDT', '2025-06-01 16:26:32', '2.24510000', '65.47', '0.00456892', '0.00474096', '2.25115331', '2.24066000', '2.23016669', '2.24066000', '2.22966600', '2.23278000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3940, 'RUNE/USDT', '2025-06-01 14:28:11', '1.63000000', '57.32', '0.00147668', '0.00119839', '1.63335142', '1.62720000', '1.62104858', '1.62720000', '1.62560000', '1.62681000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3941, 'SXT/USDT', '2025-06-01 16:14:44', '0.10069000', '67.71', '0.00017939', '0.00012591', '0.10080492', '0.10025550', '0.09970608', '0.10025550', '0.10001840', '0.10035690', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3942, 'DOGS/USDT', '2025-06-01 02:38:58', '0.00014760', '31.61', '-0.00000029', '-0.00000023', '0.00014909', '0.00014827', '0.00014744', '0.00014827', '0.00014861', '0.00014894', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 28.64, -DI: 42.91)'),
(3943, 'CRV/USDT', '2025-06-01 16:23:46', '0.65700000', '58.81', '0.00123863', '0.00107691', '0.65899390', '0.65490000', '0.65080610', '0.65490000', '0.65288000', '0.65494000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3944, 'GMT/USDT', '2025-06-01 03:05:35', '0.04911000', '41.13', '-0.00003160', '-0.00002942', '0.04921753', '0.04915300', '0.04908847', '0.04915300', '0.04924780', '0.04930360', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3945, 'DF/USDT', '2025-06-01 16:24:17', '0.04530000', '71.54', '0.00012349', '0.00009014', '0.04541391', '0.04502550', '0.04463709', '0.04502550', '0.04491040', '0.04478270', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.84, %D: 87.15)'),
(3946, 'GALA/USDT', '2025-06-01 16:23:25', '0.01653000', '69.68', '0.00003477', '0.00002750', '0.01654654', '0.01645000', '0.01635346', '0.01645000', '0.01639220', '0.01642710', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3947, 'USUAL/USDT', '2025-06-01 16:22:44', '0.10190000', '58.89', '0.00022563', '0.00019511', '0.10230753', '0.10164000', '0.10097247', '0.10164000', '0.10122600', '0.10166000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.22, %D: 89.51)'),
(3948, 'SIGN/USDT', '2025-06-01 03:02:49', '0.07381000', '54.67', '0.00005296', '0.00003544', '0.07398795', '0.07371050', '0.07343305', '0.07371050', '0.07372480', '0.07381230', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3949, 'APT/USDT', '2025-06-01 16:25:51', '4.69540000', '62.72', '0.00871401', '0.00850368', '4.70806943', '4.68755500', '4.66704057', '4.68755500', '4.66827600', '4.67596000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3950, 'NIL/USDT', '2025-06-01 16:23:25', '0.44820000', '66.95', '0.00106214', '0.00088411', '0.44917043', '0.44627500', '0.44337957', '0.44627500', '0.44426400', '0.44628900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.75, %D: 85.86)'),
(3951, 'XLM/USDT', '2025-06-01 16:14:02', '0.26355000', '68.21', '0.00013922', '0.00003894', '0.26338010', '0.26280800', '0.26223590', '0.26280800', '0.26277720', '0.26326560', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3952, 'INJ/USDT', '2025-06-01 16:16:27', '11.82400000', '53.69', '0.00980849', '0.00739241', '11.84939821', '11.81310000', '11.77680179', '11.81310000', '11.79046000', '11.89507000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3953, 'ETC/USDT', '2025-06-01 16:25:31', '16.77900000', '60.83', '0.02077630', '0.02078321', '16.81445807', '16.76380000', '16.71314193', '16.76380000', '16.71852000', '16.74171000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3954, 'XMR/USDT', '2025-06-01 03:24:42', '322.85000000', '36.02', '-0.30021024', '-0.25962987', '324.49819264', '323.36450000', '322.23080736', '323.36450000', '323.71940000', '323.32240000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3955, 'ARKM/USDT', '2025-06-01 16:24:28', '0.53980000', '62.84', '0.00117105', '0.00112363', '0.54175986', '0.53836500', '0.53497014', '0.53836500', '0.53591600', '0.53746600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3956, 'SUSHI/USDT', '2025-06-01 04:55:01', '0.63500000', '27.26', '-0.00099496', '-0.00064623', '0.64193976', '0.63921000', '0.63648024', '0.63921000', '0.64076600', '0.64066300', NULL, 'SELL', 0, 1, 6, 'breakout_detection: SELL - Volatilite patlamas? ve dü?en mum'),
(3957, 'MASK/USDT', '2025-06-01 16:21:27', '2.18560000', '78.93', '0.00821002', '0.00336897', '2.16574223', '2.13314000', '2.10053777', '2.13314000', '2.12604600', '2.10988400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3958, 'NEAR/USDT', '2025-06-01 16:24:50', '2.39800000', '65.07', '0.00472460', '0.00427367', '2.40322979', '2.39180000', '2.38037021', '2.39180000', '2.38220000', '2.38651000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 80.78, %D: 83.32)'),
(3959, 'KERNEL/USDT', '2025-06-01 16:26:58', '0.15500000', '60.70', '0.00019362', '0.00005827', '0.15499215', '0.15390500', '0.15281785', '0.15390500', '0.15399800', '0.15532400', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.1550, SAR: 0.1540)'),
(3960, 'ENS/USDT', '2025-06-01 16:25:51', '20.31900000', '60.72', '0.03564706', '0.03350458', '20.38022897', '20.28840000', '20.19657103', '20.28840000', '20.21926000', '20.26688000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3961, 'TRX/USDT', '2025-06-01 16:15:26', '0.26779000', '54.49', '0.00006275', '0.00004664', '0.26793792', '0.26773150', '0.26752508', '0.26773150', '0.26763420', '0.26799170', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.54, %D: 86.45)'),
(3962, 'PORTAL/USDT', '2025-06-01 04:55:08', '0.04710000', '35.98', '-0.00008211', '-0.00005444', '0.04763537', '0.04736500', '0.04709463', '0.04736500', '0.04748600', '0.04750700', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0471, SAR: 0.0475)'),
(3963, 'SYRUP/USDT', '2025-06-01 16:13:41', '0.33491000', '48.43', '-0.00010682', '-0.00018491', '0.33601718', '0.33490650', '0.33379582', '0.33490650', '0.33545180', '0.33682720', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3964, 'NEO/USDT', '2025-06-01 03:49:12', '5.84700000', '53.55', '0.00319725', '0.00209592', '5.86164849', '5.84175000', '5.82185151', '5.84175000', '5.84092000', '5.86906000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3965, 'APE/USDT', '2025-06-01 16:14:10', '0.62560000', '64.59', '0.00089054', '0.00069617', '0.62538477', '0.62316000', '0.62093523', '0.62316000', '0.62162200', '0.62258900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 91.89, %D: 94.89)'),
(3966, 'IO/USDT', '2025-06-01 16:16:07', '0.80230000', '57.55', '0.00110792', '0.00088839', '0.80454411', '0.80081000', '0.79707589', '0.80081000', '0.79937200', '0.80287100', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3967, 'UNI/USDT', '2025-06-01 16:25:51', '6.14500000', '48.73', '0.00507187', '0.00517273', '6.16848529', '6.14810000', '6.12771471', '6.14810000', '6.13686000', '6.15780000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3968, 'CATI/USDT', '2025-06-01 16:16:47', '0.09190000', '57.43', '0.00039564', '0.00046250', '0.09305059', '0.09178000', '0.09050941', '0.09178000', '0.09077800', '0.09059600', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0919, SAR: 0.0927)'),
(3969, 'WIF/USDT', '2025-06-01 16:23:25', '0.82740000', '62.28', '0.00200096', '0.00186826', '0.83010743', '0.82452500', '0.81894257', '0.82452500', '0.82048600', '0.82376400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3970, 'HUMA/USDT', '2025-06-01 16:13:20', '0.03653300', '51.31', '-0.00000978', '-0.00001820', '0.03673040', '0.03652120', '0.03631200', '0.03652120', '0.03650404', '0.03660693', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0365, SAR: 0.0363)'),
(3971, 'PUNDIX/USDT', '2025-06-01 16:15:04', '0.32020000', '65.93', '0.00023652', '0.00004099', '0.32011994', '0.31903500', '0.31795006', '0.31903500', '0.31918400', '0.32064500', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.3202, SAR: 0.3185)'),
(3972, 'EPIC/USDT', '2025-06-01 16:16:47', '1.19400000', '68.98', '0.00136773', '0.00080704', '1.19399834', '1.18975500', '1.18551166', '1.18975500', '1.18840400', '1.19448500', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 93.55, %D: 95.35)'),
(3973, 'GUN/USDT', '2025-06-01 16:15:04', '0.03915000', '67.27', '0.00007272', '0.00005352', '0.03920465', '0.03900550', '0.03880635', '0.03900550', '0.03890680', '0.03904880', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3974, 'COW/USDT', '2025-06-01 06:51:37', '0.36980000', '52.53', '0.00058232', '0.00061323', '0.37108977', '0.36996500', '0.36884023', '0.36996500', '0.36848400', '0.36877900', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3975, 'STX/USDT', '2025-06-01 16:13:42', '0.72910000', '71.98', '0.00095950', '0.00047147', '0.72894779', '0.72575500', '0.72256221', '0.72575500', '0.72510800', '0.72705700', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 97.28, %D: 97.51)'),
(3976, 'VOXEL/USDT', '2025-06-01 16:24:33', '0.05906000', '72.40', '0.00033526', '0.00032114', '0.05954481', '0.05858250', '0.05762019', '0.05858250', '0.05796920', '0.05788780', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3977, 'XMR/USDT', '2025-06-01 10:32:17', '326.03000000', '55.13', '0.12167647', '0.07047425', '326.34568473', '325.82050000', '325.29531527', '325.82050000', '325.70480000', '325.88040000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.35, %D: 84.20)'),
(3978, 'DOGS/USDT', '2025-06-01 16:22:01', '0.00014860', '74.13', '0.00000042', '0.00000034', '0.00014893', '0.00014784', '0.00014675', '0.00014784', '0.00014712', '0.00014757', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3979, 'MANTA/USDT', '2025-06-01 16:21:40', '0.24030000', '73.87', '0.00080294', '0.00068346', '0.24093256', '0.23884500', '0.23675744', '0.23884500', '0.23746200', '0.23866700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3980, 'REZ/USDT', '2025-06-01 16:21:19', '0.01138000', '80.55', '0.00005049', '0.00003531', '0.01138244', '0.01124300', '0.01110356', '0.01124300', '0.01118160', '0.01120040', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3981, 'NEO/USDT', '2025-06-01 14:14:08', '5.76100000', '49.85', '0.00016437', '0.00066256', '5.77531371', '5.76400000', '5.75268629', '5.76400000', '5.76040000', '5.75450000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3982, 'PORTAL/USDT', '2025-06-01 16:26:52', '0.04880000', '67.76', '0.00018458', '0.00015598', '0.04898945', '0.04851500', '0.04804055', '0.04851500', '0.04822000', '0.04820000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3983, 'MANA/USDT', '2025-06-01 11:12:41', '0.27130000', '45.05', '-0.00011116', '-0.00006523', '0.27224630', '0.27155500', '0.27086370', '0.27155500', '0.27149200', '0.27152400', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3984, 'OGN/USDT', '2025-06-01 16:14:22', '0.05760000', '76.58', '0.00008931', '0.00001683', '0.05720405', '0.05677000', '0.05633595', '0.05677000', '0.05671600', '0.05700500', NULL, 'BUY', 1, 0, 6, 'breakout_detection: BUY - Volatilite patlamas? ve yükselen mum'),
(3985, '1INCH/USDT', '2025-06-01 10:37:40', '0.20760000', '39.02', '-0.00006072', '-0.00000710', '0.20832643', '0.20797500', '0.20762357', '0.20797500', '0.20795200', '0.20822700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3986, 'CELO/USDT', '2025-06-01 13:38:10', '0.31800000', '40.47', '-0.00018892', '0.00001550', '0.32080258', '0.31935000', '0.31789742', '0.31935000', '0.31902000', '0.31980000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 34.39, -DI: 22.51)'),
(3987, 'BIGTIME/USDT', '2025-06-01 10:45:42', '0.06179000', '48.47', '-0.00003289', '-0.00004135', '0.06199316', '0.06180200', '0.06161084', '0.06180200', '0.06184480', '0.06195820', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3988, 'PAXG/USDT', '2025-06-01 16:24:30', '3305.51000000', '53.82', '0.23876218', '0.09348026', '3306.62865117', '3304.86300000', '3303.09734883', '3304.86300000', '3305.20900000', '3305.44070000', NULL, 'SELL', 0, 2, 5, 'breakout_detection: SELL - Volatilite patlamas? ve dü?en mum | volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.51, %D: 86.20)'),
(3989, 'JASMY/USDT', '2025-06-01 11:01:43', '0.01530400', '61.16', '0.00001222', '0.00001015', '0.01530924', '0.01527125', '0.01523326', '0.01527125', '0.01525602', '0.01526804', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3990, 'SNX/USDT', '2025-06-01 10:56:24', '0.68200000', '52.95', '0.00020398', '0.00010002', '0.68298324', '0.68150000', '0.68001676', '0.68150000', '0.68144000', '0.68313000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3991, 'RVN/USDT', '2025-06-01 10:56:27', '0.01080000', '50.02', '-0.00000153', '-0.00000381', '0.01080658', '0.01079350', '0.01078042', '0.01079350', '0.01080460', '0.01081330', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(3992, 'FUN/USDT', '2025-06-01 10:56:30', '0.00326800', '57.63', '0.00000130', '0.00000094', '0.00326983', '0.00326460', '0.00325937', '0.00326460', '0.00326184', '0.00326360', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3993, 'W/USDT', '2025-06-01 16:19:08', '0.07807000', '69.99', '0.00020189', '0.00016899', '0.07822204', '0.07767350', '0.07712496', '0.07767350', '0.07734940', '0.07750500', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.99, %D: 89.12)'),
(3994, 'XTZ/USDT', '2025-06-01 16:14:02', '0.55900000', '53.18', '0.00045606', '0.00027163', '0.56033907', '0.55840000', '0.55646093', '0.55840000', '0.55796000', '0.55954000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3995, 'ME/USDT', '2025-06-01 16:24:49', '0.81400000', '67.34', '0.00213954', '0.00184957', '0.81622266', '0.81075000', '0.80527734', '0.81075000', '0.80698000', '0.80837000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3996, 'ZK/USDT', '2025-06-01 16:22:09', '0.05249000', '64.23', '0.00012098', '0.00010720', '0.05265629', '0.05231550', '0.05197471', '0.05231550', '0.05209580', '0.05229190', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3997, 'NXPC/USDT', '2025-06-01 16:24:01', '1.36473000', '71.02', '0.00393462', '0.00318104', '1.36749842', '1.35705600', '1.34661358', '1.35705600', '1.35064200', '1.35079440', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3998, 'SUSHI/USDT', '2025-06-01 15:20:58', '0.63690000', '47.50', '0.00013721', '0.00015907', '0.63908829', '0.63696000', '0.63483171', '0.63696000', '0.63729400', '0.63532700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(3999, 'SOPH/USDT', '2025-06-01 16:26:12', '0.05176300', '54.22', '0.00016573', '0.00019656', '0.05214574', '0.05176735', '0.05138896', '0.05176735', '0.05128634', '0.05123374', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4000, 'SIGN/USDT', '2025-06-01 16:22:01', '0.07712000', '63.70', '0.00031925', '0.00027210', '0.07762876', '0.07668900', '0.07574924', '0.07668900', '0.07624740', '0.07589350', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.42, %D: 90.46)'),
(4001, 'ASR/USDT', '2025-06-01 16:19:18', '2.13400000', '59.70', '0.00300366', '0.00283271', '2.13781391', '2.13035000', '2.12288609', '2.13035000', '2.12458000', '2.12494000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4002, 'ALICE/USDT', '2025-06-01 16:26:52', '0.39700000', '57.52', '0.00090400', '0.00091552', '0.39880000', '0.39640000', '0.39400000', '0.39640000', '0.39440000', '0.39474000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4003, 'ENJ/USDT', '2025-06-01 12:14:42', '0.07276000', '44.76', '-0.00005525', '-0.00005117', '0.07304744', '0.07282950', '0.07261156', '0.07282950', '0.07292020', '0.07311370', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(4004, 'FXS/USDT', '2025-06-01 16:22:19', '2.84560000', '66.78', '0.00992387', '0.00934114', '2.85747649', '2.83351500', '2.80955351', '2.83351500', '2.81382400', '2.81378400', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 88.90, %D: 91.84)'),
(4005, 'KMNO/USDT', '2025-06-01 16:13:21', '0.05316000', '50.40', '0.00002222', '0.00001753', '0.05329972', '0.05315250', '0.05300528', '0.05315250', '0.05314400', '0.05322590', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4006, 'ALPHA/USDT', '2025-06-01 15:48:13', '0.02460000', '41.79', '-0.00003427', '-0.00004064', '0.02474772', '0.02462150', '0.02449528', '0.02462150', '0.02472180', '0.02474850', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 26.45, -DI: 35.12)'),
(4007, 'LIT/USDT', '2025-06-01 16:14:26', '0.59200000', '0.00', '0.00000000', '0.00000000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', '0.59200000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4008, 'BAKE/USDT', '2025-06-01 15:45:37', '0.10830000', '39.83', '-0.00016399', '-0.00017073', '0.10907953', '0.10852500', '0.10797047', '0.10852500', '0.10886000', '0.10889100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(4009, 'BSW/USDT', '2025-06-01 16:22:23', '0.02628000', '62.64', '0.00028506', '0.00029051', '0.02697120', '0.02592800', '0.02488480', '0.02592800', '0.02542300', '0.02521320', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Parabolic SAR trend de?i?imi a?a?? (fiyat: 0.0263, SAR: 0.0267)'),
(4010, 'ANKR/USDT', '2025-06-01 12:36:21', '0.01569000', '35.81', '-0.00003002', '-0.00002972', '0.01576601', '0.01571650', '0.01566699', '0.01571650', '0.01578620', '0.01584200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 40.88, -DI: 33.41)'),
(4011, 'BOND/USDT', '2025-06-01 16:09:43', '1.06900000', '0.00', '0.00000000', '0.00000000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', '1.06900000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4012, 'ROSE/USDT', '2025-06-01 16:03:01', '0.02841000', '52.76', '0.00000011', '-0.00001169', '0.02845702', '0.02836950', '0.02828198', '0.02836950', '0.02843300', '0.02850120', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4013, 'PHB/USDT', '2025-06-01 13:19:25', '0.51090000', '51.48', '0.00001819', '-0.00008133', '0.51275732', '0.51064500', '0.50853268', '0.51064500', '0.51057600', '0.51391100', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4014, 'API3/USDT', '2025-06-01 16:16:39', '0.69990000', '63.83', '0.00153640', '0.00135120', '0.70152564', '0.69763500', '0.69374436', '0.69763500', '0.69504800', '0.69531300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4015, 'AR/USDT', '2025-06-01 12:54:34', '6.25900000', '35.39', '-0.01163190', '-0.01171051', '6.30743370', '6.27730000', '6.24716630', '6.27730000', '6.30298000', '6.32858000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 36.88, -DI: 32.95)'),
(4016, 'COTI/USDT', '2025-06-01 16:12:02', '0.05831000', '64.44', '0.00004727', '0.00002267', '0.05832403', '0.05814900', '0.05797397', '0.05814900', '0.05811760', '0.05830600', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4017, 'THE/USDT', '2025-06-01 16:09:58', '0.24910000', '58.77', '0.00014048', '0.00004896', '0.24924235', '0.24856000', '0.24787765', '0.24856000', '0.24855200', '0.24940100', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4018, 'UMA/USDT', '2025-06-01 12:54:46', '1.06500000', '45.36', '-0.00150915', '-0.00198126', '1.06794833', '1.06495000', '1.06195167', '1.06495000', '1.07058000', '1.07588000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 38.30, -DI: 29.27)'),
(4019, 'ILV/USDT', '2025-06-01 16:21:39', '12.80700000', '62.10', '0.02850201', '0.02759311', '12.85603419', '12.78070000', '12.70536581', '12.78070000', '12.71848000', '12.74776000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4020, 'EGLD/USDT', '2025-06-01 15:45:30', '15.10100000', '35.55', '-0.01886478', '-0.01787142', '15.20460430', '15.14325000', '15.08189570', '15.14325000', '15.17442000', '15.18842000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(4021, 'ALT/USDT', '2025-06-01 13:11:04', '0.02659000', '54.18', '0.00000448', '-0.00000328', '0.02665572', '0.02653400', '0.02641228', '0.02653400', '0.02655680', '0.02672560', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4022, 'CFX/USDT', '2025-06-01 16:24:56', '0.07516000', '70.10', '0.00022172', '0.00020804', '0.07544190', '0.07491750', '0.07439310', '0.07491750', '0.07448280', '0.07453200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 84.17, %D: 88.77)'),
(4023, 'HYPER/USDT', '2025-06-01 16:22:01', '0.12810000', '64.02', '0.00026417', '0.00019121', '0.12831792', '0.12743000', '0.12654208', '0.12743000', '0.12709200', '0.12755600', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.1281, SAR: 0.1274)'),
(4024, 'IMX/USDT', '2025-06-01 16:26:52', '0.54420000', '62.43', '0.00097572', '0.00094545', '0.54553589', '0.54316500', '0.54079411', '0.54316500', '0.54108000', '0.54203200', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4025, 'IOTA/USDT', '2025-06-01 13:32:49', '0.17790000', '58.25', '0.00012259', '0.00009960', '0.17802113', '0.17763500', '0.17724887', '0.17763500', '0.17740600', '0.17770400', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.71, %D: 88.89)'),
(4026, 'SUPER/USDT', '2025-06-01 13:32:52', '0.64920000', '49.71', '0.00000147', '0.00005581', '0.65068178', '0.64942500', '0.64816822', '0.64942500', '0.64889000', '0.65046700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4027, 'RSR/USDT', '2025-06-01 16:22:12', '0.00713900', '67.20', '0.00001340', '0.00001205', '0.00715577', '0.00711895', '0.00708213', '0.00711895', '0.00709198', '0.00711007', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 87.20, %D: 88.25)'),
(4028, 'COW/USDT', '2025-06-01 16:19:12', '0.37720000', '76.39', '0.00108574', '0.00087231', '0.37717283', '0.37464500', '0.37211717', '0.37464500', '0.37270400', '0.37330100', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 92.03, %D: 92.09)'),
(4029, 'XMR/USDT', '2025-06-01 15:47:51', '327.15000000', '49.17', '0.04616925', '0.05127498', '327.97708947', '327.31200000', '326.64691053', '327.31200000', '327.17200000', '327.17560000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4030, 'TRB/USDT', '2025-06-01 16:20:35', '44.03700000', '64.41', '0.21065102', '0.20019906', '44.14423401', '43.63560000', '43.12696599', '43.63560000', '43.25284000', '42.47543000', NULL, 'BUY', 2, 0, 5, 'breakout_detection: BUY - Volatilite patlamas? ve yükselen mum | volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 44.0370, SAR: 43.2214)'),
(4031, 'STO/USDT', '2025-06-01 15:37:34', '0.09501000', '19.36', '-0.00028191', '-0.00021718', '0.09631905', '0.09565800', '0.09499695', '0.09565800', '0.09608580', '0.09610300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4032, 'MAGIC/USDT', '2025-06-01 16:22:06', '0.13140000', '69.80', '0.00035300', '0.00030425', '0.13176690', '0.13084500', '0.12992310', '0.13084500', '0.13023600', '0.13041500', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 86.03, %D: 89.41)'),
(4033, '1INCH/USDT', '2025-06-01 15:40:09', '0.20460000', '37.68', '-0.00031884', '-0.00027634', '0.20587555', '0.20496500', '0.20405445', '0.20496500', '0.20545400', '0.20547900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 28.51, -DI: 41.97)'),
(4034, '1000CAT/USDT', '2025-06-01 16:21:40', '0.00711400', '61.84', '0.00000789', '0.00000452', '0.00712035', '0.00708455', '0.00704875', '0.00708455', '0.00706882', '0.00708993', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.0071, SAR: 0.0070)'),
(4035, 'ZIL/USDT', '2025-06-01 15:40:16', '0.01115000', '35.96', '-0.00001581', '-0.00001194', '0.01122836', '0.01117900', '0.01112964', '0.01117900', '0.01119880', '0.01117840', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 28.26, -DI: 34.25)'),
(4036, 'RPL/USDT', '2025-06-01 16:22:23', '4.85400000', '62.43', '0.01667022', '0.01564088', '4.87862139', '4.83840000', '4.79817861', '4.83840000', '4.80206000', '4.80041000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 82.50, %D: 89.28)'),
(4037, 'PIXEL/USDT', '2025-06-01 16:24:52', '0.04397000', '66.79', '0.00014291', '0.00014329', '0.04415249', '0.04383250', '0.04351251', '0.04383250', '0.04350420', '0.04353300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4038, 'WOO/USDT', '2025-06-01 15:50:33', '0.07329000', '34.15', '-0.00016150', '-0.00016292', '0.07380289', '0.07345600', '0.07310911', '0.07345600', '0.07384300', '0.07402330', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 36.82, -DI: 38.20)'),
(4039, 'GTC/USDT', '2025-06-01 16:22:23', '0.25400000', '58.54', '0.00050295', '0.00045413', '0.25498523', '0.25330000', '0.25161477', '0.25330000', '0.25244000', '0.25297000', NULL, 'BUY', 1, 0, 6, 'volatility_breakout: BUY - Parabolic SAR trend de?i?imi yukar? (fiyat: 0.2540, SAR: 0.2525)'),
(4040, 'NTRN/USDT', '2025-06-01 15:50:41', '0.09720000', '43.75', '-0.00013275', '-0.00013003', '0.09770497', '0.09730000', '0.09689503', '0.09730000', '0.09763400', '0.09777200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 28.61, -DI: 33.62)'),
(4041, 'SCRT/USDT', '2025-06-01 16:16:57', '0.18050000', '70.55', '0.00029437', '0.00019336', '0.18062567', '0.17960000', '0.17857433', '0.17960000', '0.17936800', '0.17965900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 93.33, %D: 93.51)'),
(4042, 'COS/USDT', '2025-06-01 15:50:47', '0.00308900', '41.37', '-0.00000551', '-0.00000568', '0.00310461', '0.00309160', '0.00307859', '0.00309160', '0.00310440', '0.00311122', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 38.75, -DI: 42.27)'),
(4043, 'STMX/USDT', '2025-06-01 16:19:24', '0.00388000', '0.00', '0.00000000', '0.00000000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', '0.00388000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4044, 'ATA/USDT', '2025-06-01 16:03:07', '0.04370000', '43.71', '-0.00003601', '-0.00004451', '0.04387662', '0.04376000', '0.04364338', '0.04376000', '0.04390800', '0.04406300', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4045, 'HEI/USDT', '2025-06-01 16:22:22', '0.31970000', '65.61', '0.00058085', '0.00053295', '0.32032109', '0.31891500', '0.31750891', '0.31891500', '0.31766200', '0.31805700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4046, 'HIGH/USDT', '2025-06-01 15:53:21', '0.53760000', '51.91', '-0.00045386', '-0.00076697', '0.53782621', '0.53638500', '0.53494379', '0.53638500', '0.53869400', '0.53998600', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(4047, 'METIS/USDT', '2025-06-01 16:14:42', '17.22000000', '70.72', '0.02174973', '0.01033731', '17.22370724', '17.14700000', '17.07029276', '17.14700000', '17.14000000', '17.20170000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok');
INSERT INTO `price_analysis` (`id`, `symbol`, `analysis_time`, `price`, `rsi`, `macd`, `macd_signal`, `bollinger_upper`, `bollinger_middle`, `bollinger_lower`, `ma20`, `ma50`, `ma100`, `ma200`, `trade_signal`, `buy_signals`, `sell_signals`, `neutral_signals`, `notes`) VALUES
(4048, 'SCR/USDT', '2025-06-01 15:55:26', '0.27460000', '51.30', '-0.00014741', '-0.00028595', '0.27490608', '0.27408000', '0.27325392', '0.27408000', '0.27516400', '0.27608300', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Güçlü dü?en trend (ADX: 26.27, -DI: 28.56)'),
(4049, 'RDNT/USDT', '2025-06-01 16:21:40', '0.02379000', '64.00', '0.00004461', '0.00003926', '0.02383556', '0.02371400', '0.02359244', '0.02371400', '0.02362900', '0.02367280', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4050, 'ACE/USDT', '2025-06-01 16:12:12', '0.55600000', '72.32', '0.00114425', '0.00073106', '0.55642101', '0.55309000', '0.54975899', '0.55309000', '0.55195000', '0.55332900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 96.45, %D: 97.55)'),
(4051, 'USTC/USDT', '2025-06-01 16:14:39', '0.01164000', '64.96', '0.00001553', '0.00001074', '0.01165302', '0.01160495', '0.01155688', '0.01160495', '0.01158976', '0.01160390', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 85.23, %D: 88.33)'),
(4052, 'POWR/USDT', '2025-06-01 15:58:15', '0.15660000', '51.81', '-0.00000984', '-0.00005933', '0.15674594', '0.15644000', '0.15613406', '0.15644000', '0.15673800', '0.15681000', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Ichimoku: Fiyat k?rm?z? bulutun alt?nda, TK çapraz? a?a??'),
(4053, 'RARE/USDT', '2025-06-01 16:22:15', '0.05480000', '65.17', '0.00010011', '0.00008273', '0.05491908', '0.05463600', '0.05435292', '0.05463600', '0.05446760', '0.05459490', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 80.72, %D: 85.87)'),
(4054, 'LEVER/USDT', '2025-06-01 16:10:01', '0.00046200', '75.16', '0.00000089', '0.00000065', '0.00046200', '0.00045974', '0.00045747', '0.00045974', '0.00045842', '0.00045932', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4055, 'DASH/USDT', '2025-06-01 16:07:37', '21.90000000', '56.53', '0.00915239', '0.00261912', '21.91451886', '21.86150000', '21.80848114', '21.86150000', '21.86660000', '21.90470000', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4056, 'SYN/USDT', '2025-06-01 16:07:40', '0.16840000', '68.12', '0.00032650', '0.00026379', '0.16899083', '0.16773500', '0.16647917', '0.16773500', '0.16747600', '0.16748500', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4057, 'BICO/USDT', '2025-06-01 16:12:08', '0.10170000', '77.46', '0.00026575', '0.00015467', '0.10179716', '0.10089500', '0.09999284', '0.10089500', '0.10073200', '0.10085700', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok'),
(4058, 'MAV/USDT', '2025-06-01 16:14:36', '0.05442000', '66.34', '0.00010273', '0.00007613', '0.05451096', '0.05421850', '0.05392604', '0.05421850', '0.05410200', '0.05431090', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 96.49, %D: 97.15)'),
(4059, 'NFP/USDT', '2025-06-01 16:20:35', '0.07150000', '69.61', '0.00014950', '0.00012169', '0.07151933', '0.07112500', '0.07073067', '0.07112500', '0.07085600', '0.07102200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 80.16, %D: 80.56)'),
(4060, 'SHELL/USDT', '2025-06-01 16:19:32', '0.17500000', '69.67', '0.00043652', '0.00030847', '0.17517399', '0.17387000', '0.17256601', '0.17387000', '0.17339400', '0.17387200', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 90.38, %D: 90.66)'),
(4061, 'ONT/USDT', '2025-06-01 16:19:35', '0.12940000', '75.46', '0.00020772', '0.00015862', '0.12947886', '0.12891000', '0.12834114', '0.12891000', '0.12861000', '0.12885900', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 92.96, %D: 94.20)'),
(4062, 'JST/USDT', '2025-06-01 16:19:38', '0.03266600', '62.36', '0.00001281', '0.00000990', '0.03267090', '0.03263880', '0.03260670', '0.03263880', '0.03261142', '0.03264657', NULL, 'SELL', 0, 1, 6, 'volatility_breakout: SELL - Stochastic a??r? al?m bölgesinden dönü? (%K: 83.01, %D: 84.66)'),
(4063, 'GMT/USDT', '2025-06-01 16:24:43', '0.04906000', '68.70', '0.00012680', '0.00011466', '0.04922078', '0.04887650', '0.04853222', '0.04887650', '0.04864900', '0.04866580', NULL, 'NEUTRAL', 0, 0, 7, 'Strateji notu yok');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `risk_management_settings`
--

CREATE TABLE IF NOT EXISTS `risk_management_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enabled` tinyint(1) DEFAULT '1',
  `dynamic_position_sizing` tinyint(1) DEFAULT '1',
  `position_size_method` enum('fixed','percent','risk-based') DEFAULT 'percent',
  `max_risk_per_trade` float DEFAULT '2',
  `auto_adjust_risk` tinyint(1) DEFAULT '1',
  `volatility_based_stops` tinyint(1) DEFAULT '1',
  `adaptive_take_profit` tinyint(1) DEFAULT '1',
  `max_open_positions` int(11) DEFAULT '5',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `risk_management_settings`
--

INSERT INTO `risk_management_settings` (`id`, `user_id`, `enabled`, `dynamic_position_sizing`, `position_size_method`, `max_risk_per_trade`, `auto_adjust_risk`, `volatility_based_stops`, `adaptive_take_profit`, `max_open_positions`, `created_at`, `updated_at`) VALUES
(2, 1, 1, 1, 'fixed', 1, 0, 0, 0, 3, '2025-05-11 00:34:22', '2025-05-15 22:33:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `smart_trend_settings`
--

CREATE TABLE IF NOT EXISTS `smart_trend_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `use_smart_trend` tinyint(1) DEFAULT '1',
  `trend_detection_method` enum('supertrend','adx','combined') DEFAULT 'combined',
  `trend_sensitivity` float DEFAULT '3',
  `trend_lookback_period` int(11) DEFAULT '20',
  `trend_confirmation_period` int(11) DEFAULT '3',
  `signal_quality_threshold` float DEFAULT '0.7',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `smart_trend_settings`
--

INSERT INTO `smart_trend_settings` (`id`, `user_id`, `use_smart_trend`, `trend_detection_method`, `trend_sensitivity`, `trend_lookback_period`, `trend_confirmation_period`, `signal_quality_threshold`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'combined', 3, 20, 3, 0.7, '2025-05-11 00:34:22', NULL),
(2, 1, 1, '', 0.5, 100, 3, 0.7, '2025-05-11 00:34:22', '2025-05-15 22:33:22');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `theme_settings`
--

CREATE TABLE IF NOT EXISTS `theme_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text,
  `setting_type` enum('color','text','number','boolean','select') DEFAULT 'text',
  `setting_category` varchar(30) DEFAULT 'general',
  `setting_description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `theme_settings`
--

INSERT INTO `theme_settings` (`id`, `setting_name`, `setting_value`, `setting_type`, `setting_category`, `setting_description`, `created_at`, `updated_at`) VALUES
(1, 'site_title', 'Trading Bot Panel', 'text', 'general', 'Site ba?l???', '2025-05-30 22:27:48', NULL),
(2, 'site_logo', 'assets/img/logo.png', 'text', 'general', 'Site logosu yolu', '2025-05-30 22:27:48', NULL),
(3, 'favicon', 'assets/img/favicon.ico', 'text', 'general', 'Favicon yolu', '2025-05-30 22:27:48', NULL),
(4, 'primary_color', '#007bff', 'color', 'colors', 'Ana renk', '2025-05-30 22:27:48', NULL),
(5, 'secondary_color', '#6c757d', 'color', 'colors', '?kincil renk', '2025-05-30 22:27:48', NULL),
(6, 'success_color', '#28a745', 'color', 'colors', 'Ba?ar? rengi', '2025-05-30 22:27:48', NULL),
(7, 'danger_color', '#dc3545', 'color', 'colors', 'Hata rengi', '2025-05-30 22:27:48', NULL),
(8, 'warning_color', '#ffc107', 'color', 'colors', 'Uyar? rengi', '2025-05-30 22:27:48', NULL),
(9, 'info_color', '#17a2b8', 'color', 'colors', 'Bilgi rengi', '2025-05-30 22:27:48', NULL),
(10, 'sidebar_bg', '#343a40', 'color', 'colors', 'Sidebar arkaplan rengi', '2025-05-30 22:27:48', NULL),
(11, 'navbar_bg', '#007bff', 'color', 'colors', 'Navbar arkaplan rengi', '2025-05-30 22:27:48', NULL),
(12, 'dark_mode', '0', 'boolean', 'theme', 'Karanl?k mod', '2025-05-30 22:27:48', NULL),
(13, 'compact_mode', '0', 'boolean', 'theme', 'Kompakt mod', '2025-05-30 22:27:48', NULL),
(14, 'sidebar_fixed', '1', 'boolean', 'theme', 'Sabit sidebar', '2025-05-30 22:27:48', NULL),
(15, 'navbar_fixed', '1', 'boolean', 'theme', 'Sabit navbar', '2025-05-30 22:27:48', NULL),
(16, 'font_family', 'Roboto', 'select', 'typography', 'Font ailesi', '2025-05-30 22:27:48', NULL),
(17, 'font_size', '14', 'number', 'typography', 'Temel font boyutu (px)', '2025-05-30 22:27:48', NULL),
(18, 'heading_font', 'Roboto', 'select', 'typography', 'Ba?l?k fontu', '2025-05-30 22:27:48', NULL),
(19, 'container_width', 'fluid', 'select', 'layout', 'Container geni?li?i', '2025-05-30 22:27:48', NULL),
(20, 'sidebar_width', '250', 'number', 'layout', 'Sidebar geni?li?i (px)', '2025-05-30 22:27:48', NULL),
(21, 'card_border_radius', '8', 'number', 'layout', 'Kart kö?e yuvarlama (px)', '2025-05-30 22:27:48', NULL),
(22, 'card_shadow', 'medium', 'select', 'layout', 'Kart gölge efekti', '2025-05-30 22:27:48', NULL),
(23, 'enable_animations', '1', 'boolean', 'animations', 'Animasyonlar? etkinle?tir', '2025-05-30 22:27:48', NULL),
(24, 'transition_speed', '300', 'number', 'animations', 'Geçi? h?z? (ms)', '2025-05-30 22:27:48', NULL),
(25, 'hover_effects', '1', 'boolean', 'animations', 'Hover efektleri', '2025-05-30 22:27:48', NULL),
(26, 'chart_theme', 'light', 'select', 'charts', 'Grafik temas?', '2025-05-30 22:27:48', NULL),
(27, 'chart_colors', '#007bff,#28a745,#dc3545,#ffc107,#17a2b8', 'text', 'charts', 'Grafik renkleri (virgülle ayr?lm??)', '2025-05-30 22:27:48', NULL),
(28, 'chart_grid', '1', 'boolean', 'charts', 'Grafik ?zgaras?', '2025-05-30 22:27:48', NULL),
(29, 'lazy_loading', '1', 'boolean', 'performance', 'Lazy loading', '2025-05-30 22:27:48', NULL),
(30, 'minimize_css', '0', 'boolean', 'performance', 'CSS minimize et', '2025-05-30 22:27:48', NULL),
(31, 'minimize_js', '0', 'boolean', 'performance', 'JS minimize et', '2025-05-30 22:27:48', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `trades`
--

CREATE TABLE IF NOT EXISTS `trades` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `type` enum('BUY','SELL') NOT NULL,
  `price` decimal(20,8) NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `order_id` varchar(50) NOT NULL,
  `profit_loss` decimal(20,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `trade_history`
--

CREATE TABLE IF NOT EXISTS `trade_history` (
  `id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `symbol` varchar(20) NOT NULL,
  `position_type` varchar(10) NOT NULL,
  `entry_price` decimal(20,8) NOT NULL,
  `exit_price` decimal(20,8) NOT NULL,
  `amount` decimal(20,8) NOT NULL,
  `entry_time` datetime DEFAULT NULL,
  `exit_time` datetime DEFAULT NULL,
  `profit_loss_pct` decimal(10,2) DEFAULT NULL,
  `close_reason` varchar(20) DEFAULT NULL,
  `strategy` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `trade_type` varchar(10) DEFAULT 'LONG',
  `trade_mode` varchar(20) DEFAULT 'paper',
  `status` varchar(20) DEFAULT 'COMPLETED'
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `trade_history`
--

INSERT INTO `trade_history` (`id`, `position_id`, `symbol`, `position_type`, `entry_price`, `exit_price`, `amount`, `entry_time`, `exit_time`, `profit_loss_pct`, `close_reason`, `strategy`, `notes`, `created_at`, `trade_type`, `trade_mode`, `status`) VALUES
(1, 7, 'SYRUP/USDT', 'LONG', '0.44650000', '0.42100000', '12.31802912', '2025-05-26 14:40:43', '2025-05-26 22:26:21', '-5.71', 'manual_required', 'breakout_detection', 'Kapat?ld?: manual_required - -5.71%', '2025-05-26 22:26:21', 'LONG', 'paper', 'COMPLETED'),
(2, 2, 'VIRTUAL/USDT', 'LONG', '2.22630000', '2.11220000', '2.47046669', '2025-05-26 14:36:46', '2025-05-26 23:04:54', '-5.13', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -5.13%', '2025-05-26 23:04:54', 'LONG', 'paper', 'COMPLETED'),
(3, 4, 'PEPE/USDT', 'LONG', '0.00001412', '0.00001375', '77903.68271955', '2025-05-26 14:37:46', '2025-05-26 23:20:34', '-2.62', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -2.62%', '2025-05-26 23:20:34', 'LONG', 'paper', 'COMPLETED'),
(4, 5, 'RUNE/USDT', 'LONG', '1.97300000', '1.91100000', '2.78763305', '2025-05-26 14:38:06', '2025-05-26 23:20:54', '-3.14', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -3.14%', '2025-05-26 23:20:54', 'LONG', 'paper', 'COMPLETED'),
(5, 8, 'EPIC/USDT', 'LONG', '1.43200000', '1.38200000', '3.84078212', '2025-05-26 14:40:44', '2025-05-26 23:23:37', '-3.49', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -3.49%', '2025-05-26 23:23:37', 'LONG', 'paper', 'COMPLETED'),
(6, 9, 'JUV/USDT', 'LONG', '1.17900000', '1.18500000', '4.66497031', '2025-05-26 14:40:45', '2025-05-26 23:23:57', '0.51', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - +0.51%', '2025-05-26 23:23:57', 'LONG', 'paper', 'COMPLETED'),
(7, 10, 'CVC/USDT', 'LONG', '0.14420000', '0.14000000', '38.14147018', '2025-05-26 14:42:24', '2025-05-27 00:17:05', '-2.91', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -2.91%', '2025-05-27 00:17:05', 'LONG', 'paper', 'COMPLETED'),
(8, 6, 'POL/USDT', 'LONG', '0.23770000', '0.23100000', '23.13840976', '2025-05-26 14:39:24', '2025-05-27 00:26:35', '-2.82', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -2.82%', '2025-05-27 00:26:35', 'LONG', 'paper', 'COMPLETED'),
(9, 3, 'SHIB/USDT', 'LONG', '0.00001445', '0.00001434', '76124.56747405', '2025-05-26 14:37:06', '2025-05-27 00:49:31', '-0.76', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -0.76%', '2025-05-27 00:49:31', 'LONG', 'paper', 'COMPLETED'),
(10, 1, 'TRX/USDT', 'LONG', '0.27370000', '0.27340000', '20.09499452', '2025-05-26 14:36:07', '2025-05-27 01:01:25', '-0.11', 'manual_required', 'volatility_breakout', 'Kapat?ld?: manual_required - -0.11%', '2025-05-27 01:01:25', 'LONG', 'paper', 'COMPLETED');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `api_key` varchar(64) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `role`, `api_key`, `remember_token`, `token_expiry`, `created_at`, `updated_at`, `last_login`, `is_admin`) VALUES
(1, 'admin', '$2y$10$Rm4m2VyVEoZFM9v6CYJO9.9eDOCUYedB6i5ComHi.6C.FvWsjGwLC', 'admin@example.com', 'admin', NULL, NULL, NULL, '2025-04-17 19:00:05', NULL, '2025-06-01 13:15:16', 1),
(2, 'abuzer', '$2y$10$Rm4m2VyVEoZFM9v6CYJO9.9eDOCUYedB6i5ComHi.6C.FvWsjGwLC', 'abuzerr', 'user', NULL, NULL, NULL, '2025-04-30 21:54:18', NULL, '2025-04-30 22:40:11', 0),
(3, 'isaa', '$2y$10$Rm4m2VyVEoZFM9v6CYJO9.9eDOCUYedB6i5ComHi.6C.FvWsjGwLC', 'admin', 'user', NULL, NULL, NULL, '2025-05-08 16:03:17', NULL, '2025-05-09 09:50:36', 0);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `account_balance`
--
ALTER TABLE `account_balance`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `active_coins`
--
ALTER TABLE `active_coins`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `adaptive_parameter_settings`
--
ALTER TABLE `adaptive_parameter_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `api_optimization_settings`
--
ALTER TABLE `api_optimization_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `bot_settings`
--
ALTER TABLE `bot_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_smart_trend_settings` (`smart_trend_settings_id`),
  ADD KEY `fk_risk_management_settings` (`risk_management_settings_id`),
  ADD KEY `fk_adaptive_parameter_settings` (`adaptive_parameter_settings_id`),
  ADD KEY `fk_api_optimization_settings` (`api_optimization_settings_id`);

--
-- Tablo için indeksler `bot_settings_individual`
--
ALTER TABLE `bot_settings_individual`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `bot_status`
--
ALTER TABLE `bot_status`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `coin_analysis`
--
ALTER TABLE `coin_analysis`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `discovered_coins`
--
ALTER TABLE `discovered_coins`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `open_positions`
--
ALTER TABLE `open_positions`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `price_analysis`
--
ALTER TABLE `price_analysis`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `risk_management_settings`
--
ALTER TABLE `risk_management_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `smart_trend_settings`
--
ALTER TABLE `smart_trend_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `theme_settings`
--
ALTER TABLE `theme_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Tablo için indeksler `trades`
--
ALTER TABLE `trades`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `trade_history`
--
ALTER TABLE `trade_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_symbol` (`symbol`),
  ADD KEY `idx_exit_time` (`exit_time`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `account_balance`
--
ALTER TABLE `account_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- Tablo için AUTO_INCREMENT değeri `active_coins`
--
ALTER TABLE `active_coins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1543;
--
-- Tablo için AUTO_INCREMENT değeri `adaptive_parameter_settings`
--
ALTER TABLE `adaptive_parameter_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Tablo için AUTO_INCREMENT değeri `api_optimization_settings`
--
ALTER TABLE `api_optimization_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Tablo için AUTO_INCREMENT değeri `bot_settings`
--
ALTER TABLE `bot_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- Tablo için AUTO_INCREMENT değeri `bot_settings_individual`
--
ALTER TABLE `bot_settings_individual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=70;
--
-- Tablo için AUTO_INCREMENT değeri `bot_status`
--
ALTER TABLE `bot_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=72;
--
-- Tablo için AUTO_INCREMENT değeri `coin_analysis`
--
ALTER TABLE `coin_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Tablo için AUTO_INCREMENT değeri `discovered_coins`
--
ALTER TABLE `discovered_coins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=569;
--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Tablo için AUTO_INCREMENT değeri `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- Tablo için AUTO_INCREMENT değeri `open_positions`
--
ALTER TABLE `open_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=44;
--
-- Tablo için AUTO_INCREMENT değeri `price_analysis`
--
ALTER TABLE `price_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4064;
--
-- Tablo için AUTO_INCREMENT değeri `risk_management_settings`
--
ALTER TABLE `risk_management_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Tablo için AUTO_INCREMENT değeri `smart_trend_settings`
--
ALTER TABLE `smart_trend_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Tablo için AUTO_INCREMENT değeri `theme_settings`
--
ALTER TABLE `theme_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=94;
--
-- Tablo için AUTO_INCREMENT değeri `trades`
--
ALTER TABLE `trades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Tablo için AUTO_INCREMENT değeri `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=11;
--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `adaptive_parameter_settings`
--
ALTER TABLE `adaptive_parameter_settings`
  ADD CONSTRAINT `adaptive_parameter_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `api_optimization_settings`
--
ALTER TABLE `api_optimization_settings`
  ADD CONSTRAINT `api_optimization_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `bot_settings`
--
ALTER TABLE `bot_settings`
  ADD CONSTRAINT `fk_api_optimization_settings` FOREIGN KEY (`api_optimization_settings_id`) REFERENCES `api_optimization_settings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_adaptive_parameter_settings` FOREIGN KEY (`adaptive_parameter_settings_id`) REFERENCES `adaptive_parameter_settings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_management_settings` FOREIGN KEY (`risk_management_settings_id`) REFERENCES `risk_management_settings` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_smart_trend_settings` FOREIGN KEY (`smart_trend_settings_id`) REFERENCES `smart_trend_settings` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `risk_management_settings`
--
ALTER TABLE `risk_management_settings`
  ADD CONSTRAINT `risk_management_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `smart_trend_settings`
--
ALTER TABLE `smart_trend_settings`
  ADD CONSTRAINT `smart_trend_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
