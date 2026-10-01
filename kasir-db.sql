-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 01, 2026 at 03:34 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sim-kue`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_codes`
--

CREATE TABLE `access_codes` (
  `id` int(11) NOT NULL,
  `auth_code` varchar(10) NOT NULL,
  `created_by` int(11) NOT NULL,
  `valid_until` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_codes`
--

INSERT INTO `access_codes` (`id`, `auth_code`, `created_by`, `valid_until`, `is_used`, `created_at`) VALUES
(1, '648530', 1, '2026-04-11 16:55:34', 1, '2026-04-10 14:55:34'),
(2, '375983', 1, '2026-04-12 06:56:32', 0, '2026-04-11 04:56:32'),
(3, '900547', 1, '2026-04-12 08:33:18', 0, '2026-04-11 06:33:18'),
(4, '331410', 1, '2026-04-17 14:57:39', 1, '2026-04-16 12:57:39'),
(5, '186641', 1, '2026-04-19 19:28:15', 0, '2026-04-18 17:28:15'),
(6, '964907', 1, '2026-04-22 05:44:24', 1, '2026-04-21 03:44:24'),
(7, '148577', 1, '2026-05-10 22:20:50', 1, '2026-05-09 20:20:50'),
(8, '905594', 1, '2026-07-04 18:38:55', 1, '2026-07-03 16:38:55'),
(9, '909216', 1, '2026-07-17 21:21:17', 1, '2026-07-16 19:21:17'),
(10, '885088', 1, '2026-07-19 18:15:47', 1, '2026-07-18 16:15:47'),
(11, '009568', 1, '2026-07-21 12:54:10', 1, '2026-07-20 10:54:10'),
(12, '729724', 1, '2026-07-21 15:13:53', 1, '2026-07-20 13:13:53'),
(13, '367139', 1, '2026-07-21 15:14:01', 0, '2026-07-20 13:14:01'),
(14, '639626', 1, '2026-08-31 18:06:49', 0, '2026-08-30 16:06:49'),
(15, '346806', 1, '2026-09-02 11:10:04', 1, '2026-09-01 09:10:04');

-- --------------------------------------------------------

--
-- Table structure for table `app_migrations`
--

CREATE TABLE `app_migrations` (
  `migration_key` varchar(100) NOT NULL,
  `executed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `app_migrations`
--

INSERT INTO `app_migrations` (`migration_key`, `executed_at`) VALUES
('fix_pending_prod_stock', '2026-09-29 22:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `id` int(11) NOT NULL,
  `transaction_no` varchar(50) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `status` enum('Rusak','Expired','Lainnya') DEFAULT 'Rusak',
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `id` int(11) NOT NULL,
  `transaction_no` varchar(50) NOT NULL,
  `material_id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL,
  `source` enum('Manual','PO') DEFAULT 'Manual',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `expiry_date` date DEFAULT NULL,
  `po_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_titipan`
--

CREATE TABLE `barang_titipan` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `nama_umkm` varchar(100) NOT NULL COMMENT 'Nama penitip / supplier',
  `harga_modal` int(11) NOT NULL DEFAULT 0 COMMENT 'Harga setor ke UMKM',
  `harga_jual` int(11) NOT NULL DEFAULT 0 COMMENT 'Harga jual ke konsumen',
  `stok` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_titipan_keluar`
--

CREATE TABLE `barang_titipan_keluar` (
  `id` int(11) NOT NULL,
  `out_no` varchar(50) NOT NULL,
  `titipan_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `reason` enum('Expired','Rusak','Diretur UMKM','Konsumsi Internal') NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bom`
--

CREATE TABLE `bom` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `quantity_needed` decimal(10,2) NOT NULL,
  `unit_used` varchar(20) DEFAULT 'Gram'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bom_custom`
--

CREATE TABLE `bom_custom` (
  `id` int(11) NOT NULL,
  `custom_item_id` int(11) NOT NULL COMMENT 'FK ke saved_custom_items_pos.id',
  `material_id` int(11) NOT NULL COMMENT 'FK ke materials_stocks.id',
  `quantity_needed` decimal(10,4) NOT NULL,
  `unit_used` varchar(20) NOT NULL DEFAULT 'Gram'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='BOM untuk item custom dari POS';

-- --------------------------------------------------------

--
-- Table structure for table `bom_requests`
--

CREATE TABLE `bom_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL COMMENT 'Produk yang resepnya mau diubah/dibuat',
  `user_id` int(11) NOT NULL COMMENT 'ID Staf yang mengajukan',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL COMMENT 'Alasan perubahan resep',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bom_request_details`
--

CREATE TABLE `bom_request_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL COMMENT 'ID dari tabel bom_requests',
  `material_id` int(11) NOT NULL COMMENT 'Bahan baku dari Gudang Pilar',
  `quantity_needed` decimal(10,4) NOT NULL,
  `unit_used` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers_pos`
--

CREATE TABLE `customers_pos` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `custom_notes` text DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `kitchen_id` int(11) DEFAULT NULL,
  `pin` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `kitchen_id`, `pin`, `created_at`) VALUES
(1, 'Andi', 1, '1010', '2026-04-01 15:01:06'),
(2, 'Budi', 1, '1234', '2026-04-01 15:01:06'),
(3, 'Siti', 2, '0000', '2026-04-01 15:01:06'),
(4, 'Randy', 2, '1234', '2026-04-01 15:26:29');

-- --------------------------------------------------------

--
-- Table structure for table `food_delivery_payment_methods_pos`
--

CREATE TABLE `food_delivery_payment_methods_pos` (
  `id` int(11) NOT NULL,
  `platform_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Digital',
  `fee_percent` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_delivery_payment_methods_pos`
--

INSERT INTO `food_delivery_payment_methods_pos` (`id`, `platform_code`, `name`, `code`, `type`, `fee_percent`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'grabfood', 'Saldo GrabMerchant', 'GRAB_MERCHANT', 'E-Wallet', 0.00, 1, 0, '2026-08-25 14:10:47'),
(2, 'grabfood', 'OVO / GrabPay', 'OVO_GRAB', 'Digital', 0.00, 1, 0, '2026-08-25 14:10:47'),
(3, 'grabfood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0.00, 1, 0, '2026-08-25 14:10:47'),
(4, 'grabfood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0.00, 1, 0, '2026-08-25 14:10:47'),
(5, 'gofood', 'Saldo GoBiz / GoPay', 'GOBIZ_MERCHANT', 'E-Wallet', 0.00, 1, 0, '2026-08-25 14:10:47'),
(6, 'gofood', 'GoPay Customer', 'GOPAY_CUST', 'Digital', 0.00, 1, 0, '2026-08-25 14:10:47'),
(7, 'gofood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0.00, 1, 0, '2026-08-25 14:10:47'),
(8, 'gofood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0.00, 1, 0, '2026-08-25 14:10:47'),
(9, 'shopeefood', 'Saldo Shopee Merchant', 'SHOPEE_MERCHANT', 'E-Wallet', 0.00, 1, 0, '2026-08-25 14:10:47'),
(10, 'shopeefood', 'ShopeePay', 'SHOPEEPAY', 'Digital', 0.00, 1, 0, '2026-08-25 14:10:47'),
(11, 'shopeefood', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0.00, 1, 0, '2026-08-25 14:10:47'),
(12, 'shopeefood', 'Transfer Bank', 'TRANSFER', 'Transfer', 0.00, 1, 0, '2026-08-25 14:10:47'),
(13, 'travelokaeats', 'Saldo Traveloka Merchant', 'TRAVELOKA_MERCHANT', 'E-Wallet', 0.00, 1, 0, '2026-08-25 14:10:47'),
(14, 'travelokaeats', 'TravelokaPay', 'TRAVELOKAPAY', 'Digital', 0.00, 1, 0, '2026-08-25 14:10:47'),
(15, 'travelokaeats', 'QRIS BCA', 'QRIS_BCA', 'QRIS', 0.00, 1, 0, '2026-08-25 14:10:47'),
(16, 'travelokaeats', 'Transfer Bank', 'TRANSFER', 'Transfer', 0.00, 1, 0, '2026-08-25 14:10:47'),
(17, 'grabfood', 'QRIS GRAB', 'QRIS_GRAB', 'QRIS', 0.00, 1, 0, '2026-08-25 14:11:17');

-- --------------------------------------------------------

--
-- Table structure for table `food_delivery_platforms_pos`
--

CREATE TABLE `food_delivery_platforms_pos` (
  `id` int(11) NOT NULL,
  `platform_code` varchar(50) NOT NULL,
  `platform_name` varchar(100) NOT NULL,
  `icon_class` varchar(100) DEFAULT 'fa-solid fa-utensils',
  `color_class` varchar(50) DEFAULT 'bg-slate-500',
  `default_markup_percent` decimal(5,2) DEFAULT 30.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_delivery_platforms_pos`
--

INSERT INTO `food_delivery_platforms_pos` (`id`, `platform_code`, `platform_name`, `icon_class`, `color_class`, `default_markup_percent`, `is_active`, `created_at`) VALUES
(1, 'gofood', 'GoFood', 'fa-solid fa-utensils', 'text-rose-500 bg-rose-50 border-rose-200', 30.00, 1, '2026-08-10 16:39:09'),
(2, 'grabfood', 'GrabFood', 'fa-solid fa-motorcycle', 'text-emerald-500 bg-emerald-50 border-emerald-200', 30.00, 1, '2026-08-10 16:39:09'),
(3, 'shopeefood', 'ShopeeFood', 'fa-solid fa-bag-shopping', 'text-orange-500 bg-orange-50 border-orange-200', 30.00, 1, '2026-08-10 16:39:09'),
(4, 'travelokaeats', 'TravelokaEats', 'fa-solid fa-plane-departure', 'text-sky-500 bg-sky-50 border-sky-200', 30.00, 1, '2026-08-10 16:39:09');

-- --------------------------------------------------------

--
-- Table structure for table `food_delivery_prices_pos`
--

CREATE TABLE `food_delivery_prices_pos` (
  `id` int(11) NOT NULL,
  `platform_code` varchar(50) NOT NULL,
  `item_type` enum('product','custom_reguler','custom_po') DEFAULT 'product',
  `item_id` int(11) NOT NULL,
  `markup_percent` decimal(5,2) DEFAULT 30.00,
  `override_price` decimal(15,2) DEFAULT NULL,
  `final_price` decimal(15,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `warehouse_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_delivery_prices_pos`
--

INSERT INTO `food_delivery_prices_pos` (`id`, `platform_code`, `item_type`, `item_id`, `markup_percent`, `override_price`, `final_price`, `is_active`, `updated_at`, `warehouse_id`) VALUES
(1, 'grabfood', 'custom_reguler', 7, 30.00, NULL, 130000.00, 1, '2026-08-10 18:18:56', NULL),
(2, 'grabfood', 'custom_po', 3, 30.00, NULL, 130000.00, 1, '2026-08-10 18:19:45', NULL),
(3, 'grabfood', 'custom_reguler', 4, 30.00, NULL, 130000.00, 1, '2026-08-11 03:02:30', 1),
(4, 'grabfood', 'product', 1, 30.00, NULL, 13000.00, 1, '2026-08-11 09:54:16', 1),
(5, 'grabfood', 'product', 2, 30.00, NULL, 13000.00, 1, '2026-08-11 09:54:16', 1),
(6, 'grabfood', 'product', 3, 30.00, NULL, 13000.00, 1, '2026-08-11 09:54:16', 1),
(7, 'gofood', 'product', 1, 30.00, NULL, 13000.00, 1, '2026-08-24 01:38:39', 1),
(8, 'gofood', 'product', 2, 30.00, NULL, 13000.00, 1, '2026-08-24 01:38:39', 1),
(9, 'gofood', 'product', 3, 30.00, NULL, 13000.00, 1, '2026-08-24 01:38:39', 1),
(10, 'gofood', 'product', 7, 30.00, NULL, 15600.00, 1, '2026-08-24 01:38:39', 1),
(11, 'gofood', 'custom_reguler', 1, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(12, 'gofood', 'custom_reguler', 2, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(13, 'gofood', 'custom_reguler', 3, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(14, 'gofood', 'custom_reguler', 4, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(15, 'gofood', 'custom_reguler', 5, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(16, 'gofood', 'custom_reguler', 6, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(17, 'gofood', 'custom_reguler', 7, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(18, 'gofood', 'custom_reguler', 8, 30.00, NULL, 39000.00, 1, '2026-08-24 01:38:39', 1),
(19, 'gofood', 'custom_reguler', 9, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(20, 'gofood', 'custom_reguler', 10, 30.00, NULL, 13000.00, 1, '2026-08-24 01:38:39', 1),
(21, 'gofood', 'custom_reguler', 11, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(22, 'gofood', 'custom_po', 1, 30.00, NULL, 195000.00, 1, '2026-08-24 01:38:39', 1),
(23, 'gofood', 'custom_po', 2, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(24, 'gofood', 'custom_po', 3, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(25, 'gofood', 'custom_po', 4, 30.00, NULL, 195000.00, 1, '2026-08-24 01:38:39', 1),
(26, 'gofood', 'custom_po', 5, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(27, 'gofood', 'custom_po', 6, 30.00, NULL, 130000.00, 1, '2026-08-24 01:38:39', 1),
(28, 'gofood', 'custom_po', 7, 30.00, NULL, 195000.00, 1, '2026-08-24 01:38:39', 1),
(29, 'gofood', 'custom_po', 8, 30.00, NULL, 156000.00, 1, '2026-08-24 01:38:39', 1),
(30, 'gofood', 'custom_po', 9, 30.00, NULL, 156000.00, 1, '2026-08-24 01:38:39', 1),
(31, 'gofood', 'custom_po', 10, 30.00, NULL, 156000.00, 1, '2026-08-24 01:38:39', 1),
(32, 'gofood', 'custom_po', 11, 30.00, NULL, 195000.00, 1, '2026-08-24 01:38:39', 1),
(33, 'gofood', 'custom_po', 12, 30.00, NULL, 195000.00, 1, '2026-08-24 01:38:39', 1),
(34, 'grabfood', 'product', 7, 30.00, NULL, 15600.00, 1, '2026-08-27 08:15:35', 1),
(35, 'grabfood', 'custom_reguler', 1, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(36, 'grabfood', 'custom_reguler', 2, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(37, 'grabfood', 'custom_reguler', 3, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(38, 'grabfood', 'custom_reguler', 5, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(39, 'grabfood', 'custom_reguler', 6, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(40, 'grabfood', 'custom_reguler', 8, 30.00, NULL, 39000.00, 1, '2026-08-27 08:15:35', 1),
(41, 'grabfood', 'custom_reguler', 9, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(42, 'grabfood', 'custom_reguler', 10, 30.00, NULL, 13000.00, 1, '2026-08-27 08:15:35', 1),
(43, 'grabfood', 'custom_reguler', 11, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(44, 'grabfood', 'custom_po', 1, 30.00, NULL, 195000.00, 1, '2026-08-27 08:15:35', 1),
(45, 'grabfood', 'custom_po', 2, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(46, 'grabfood', 'custom_po', 4, 30.00, NULL, 195000.00, 1, '2026-08-27 08:15:35', 1),
(47, 'grabfood', 'custom_po', 5, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(48, 'grabfood', 'custom_po', 6, 30.00, NULL, 130000.00, 1, '2026-08-27 08:15:35', 1),
(49, 'grabfood', 'custom_po', 7, 30.00, NULL, 195000.00, 1, '2026-08-27 08:15:35', 1),
(50, 'grabfood', 'custom_po', 8, 30.00, NULL, 156000.00, 1, '2026-08-27 08:15:35', 1),
(51, 'grabfood', 'custom_po', 9, 30.00, NULL, 156000.00, 1, '2026-08-27 08:15:35', 1),
(52, 'grabfood', 'custom_po', 10, 30.00, NULL, 156000.00, 1, '2026-08-27 08:15:35', 1),
(53, 'grabfood', 'custom_po', 11, 30.00, NULL, 195000.00, 1, '2026-08-27 08:15:35', 1),
(54, 'grabfood', 'custom_po', 12, 30.00, NULL, 195000.00, 1, '2026-08-27 08:15:35', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gudang_roles`
--

CREATE TABLE `gudang_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `role_slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gudang_roles`
--

INSERT INTO `gudang_roles` (`id`, `role_name`, `role_slug`, `description`, `created_at`) VALUES
(1, 'Owner Produksi', 'owner_produksi', NULL, '2026-04-22 07:15:48'),
(2, 'Owner Gudang Pilar', 'owner_gudang', NULL, '2026-04-22 07:15:48'),
(3, 'Admin Gudang Utama', 'admin_gudang', NULL, '2026-04-22 07:15:48'),
(4, 'admin-gudang2', 'admin_gudang2', NULL, '2026-04-22 07:24:28'),
(5, 'spv gudang', 'spv_gudang', NULL, '2026-09-17 10:28:32');

-- --------------------------------------------------------

--
-- Table structure for table `gudang_role_permissions`
--

CREATE TABLE `gudang_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_slug` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gudang_role_permissions`
--

INSERT INTO `gudang_role_permissions` (`role_id`, `permission_slug`) VALUES
(2, 'cetak_barcode'),
(2, 'dashboard'),
(2, 'data_opname'),
(2, 'lap_barang_keluar'),
(2, 'lap_barang_masuk'),
(2, 'lap_kartu_stok'),
(2, 'lap_pembayaran_po'),
(2, 'lap_perbandingan_harga'),
(2, 'lap_po'),
(2, 'lap_stok_menipis'),
(2, 'lap_stok_opname'),
(2, 'lap_stok_terbanyak'),
(2, 'lap_supplier'),
(2, 'manage_roles'),
(2, 'manage_users'),
(2, 'master_inventory'),
(2, 'master_kategori'),
(2, 'master_lokasi'),
(2, 'master_satuan'),
(2, 'otorisasi_opname'),
(2, 'pengaturan_karyawan'),
(2, 'pengaturan_pembayaran'),
(2, 'pengaturan_profil'),
(2, 'persetujuan'),
(2, 'persetujuan_izin_cetak'),
(2, 'persetujuan_keluar_manual'),
(2, 'persetujuan_masuk_manual'),
(2, 'persetujuan_po'),
(2, 'persetujuan_pr'),
(2, 'scanner_opname'),
(2, 'trx_barang_keluar'),
(2, 'trx_barang_masuk'),
(2, 'trx_pembayaran'),
(2, 'trx_permintaan_dapur'),
(2, 'trx_po'),
(2, 'trx_supplier'),
(3, 'dashboard'),
(4, 'cetak_barcode'),
(4, 'dashboard'),
(4, 'lap_barang_keluar'),
(4, 'lap_kartu_stok'),
(4, 'lap_perbandingan_harga'),
(4, 'lap_stok_terbanyak'),
(4, 'lap_supplier'),
(4, 'manage_roles'),
(4, 'manage_users'),
(4, 'master_lokasi'),
(4, 'master_satuan'),
(4, 'pengaturan_pembayaran'),
(4, 'pengaturan_profil'),
(4, 'persetujuan_histori'),
(4, 'persetujuan_izin_cetak'),
(4, 'trx_supplier'),
(5, 'dashboard'),
(5, 'lap_barang_keluar'),
(5, 'lap_barang_masuk'),
(5, 'lap_kartu_stok'),
(5, 'lap_perbandingan_harga'),
(5, 'lap_stok_opname'),
(5, 'lap_supplier'),
(5, 'scanner_opname');

-- --------------------------------------------------------

--
-- Table structure for table `gudang_stok_opnames`
--

CREATE TABLE `gudang_stok_opnames` (
  `id` int(11) NOT NULL,
  `opname_no` varchar(50) NOT NULL,
  `opname_date` datetime NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gudang_stok_opname_details`
--

CREATE TABLE `gudang_stok_opname_details` (
  `id` int(11) NOT NULL,
  `opname_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `system_stock` decimal(10,2) NOT NULL,
  `physical_stock` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history_pos`
--

CREATE TABLE `inventory_history_pos` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `type` enum('Masuk','Keluar') NOT NULL,
  `qty` int(11) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kitchens`
--

CREATE TABLE `kitchens` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kitchens`
--

INSERT INTO `kitchens` (`id`, `name`, `location`, `created_at`) VALUES
(1, 'Dapur 1', 'Medan', '2026-04-13 15:23:27'),
(2, 'dapur 2', 'medan petisahj', '2026-04-13 15:23:42');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_settings_pos`
--

CREATE TABLE `loyalty_settings_pos` (
  `id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `earn_point_ratio` int(11) DEFAULT 0,
  `points_required` int(11) DEFAULT 0,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('IDR','PERCENT') DEFAULT 'IDR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_settings_pos`
--

INSERT INTO `loyalty_settings_pos` (`id`, `is_active`, `earn_point_ratio`, `points_required`, `discount_amount`, `discount_type`) VALUES
(1, 1, 10000, 100, 10000.00, 'IDR');

-- --------------------------------------------------------

--
-- Table structure for table `master_lokasi_rak`
--

CREATE TABLE `master_lokasi_rak` (
  `id` int(11) NOT NULL,
  `kode_rak` varchar(50) NOT NULL,
  `nama_rak` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_shifts_pos`
--

CREATE TABLE `master_shifts_pos` (
  `id` int(11) NOT NULL,
  `shift_name` varchar(50) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_shifts_pos`
--

INSERT INTO `master_shifts_pos` (`id`, `shift_name`, `start_time`, `end_time`, `is_active`, `created_at`) VALUES
(1, 'Shift Pagi', '07:00:00', '15:00:00', 0, '2026-05-08 14:00:49'),
(2, 'Shift Malam', '15:00:00', '23:00:00', 1, '2026-05-08 14:00:49'),
(3, 'Shift Pagi', '06:00:00', '12:00:00', 1, '2026-05-08 17:33:48');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `stock` decimal(10,4) DEFAULT 0.0000,
  `min_stock` decimal(10,4) DEFAULT 0.0000,
  `warehouse_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materials_stocks`
--

CREATE TABLE `materials_stocks` (
  `id` int(11) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sku_code` varchar(50) DEFAULT NULL,
  `lokasi_rak_id` int(11) DEFAULT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `unit` varchar(20) DEFAULT NULL,
  `rack_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_categories`
--

CREATE TABLE `material_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_opnames`
--

CREATE TABLE `material_opnames` (
  `id` int(11) NOT NULL,
  `opname_no` varchar(50) DEFAULT NULL,
  `material_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `system_stock` decimal(10,2) NOT NULL,
  `actual_stock` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_requests`
--

CREATE TABLE `material_requests` (
  `id` int(11) NOT NULL,
  `header_id` int(11) DEFAULT NULL,
  `request_no` varchar(20) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `material_id` int(11) DEFAULT NULL,
  `qty_requested` decimal(15,2) DEFAULT NULL,
  `qty_approved` decimal(15,2) DEFAULT NULL,
  `status` enum('menunggu','diproses','ditolak') DEFAULT 'menunggu',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `material_requests_header`
--

CREATE TABLE `material_requests_header` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL COMMENT 'ID Dapur yang meminta',
  `user_id` int(11) NOT NULL COMMENT 'User yang membuat request',
  `status` enum('menunggu','diproses','berhasil','ditolak') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `opname_history_pos`
--

CREATE TABLE `opname_history_pos` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(50) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `system_stock` int(11) NOT NULL DEFAULT 0,
  `actual_stock` int(11) NOT NULL DEFAULT 0,
  `difference` int(11) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'Cash',
  `name` varchar(100) NOT NULL,
  `fee_name` varchar(50) DEFAULT NULL,
  `fee_percent` decimal(5,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `type`, `name`, `fee_name`, `fee_percent`, `is_active`, `created_at`) VALUES
(1, 'Cash', 'Cash', NULL, 0.00, 1, '2026-04-19 08:40:06'),
(2, 'Cash', 'Giro/Cek', NULL, 0.00, 1, '2026-04-19 08:40:06'),
(3, 'Cash', 'QRIS', NULL, 0.00, 1, '2026-04-19 08:40:06'),
(4, 'Cash', 'Transfer Bank', NULL, 0.00, 1, '2026-04-19 08:40:06'),
(5, 'Cash', 'QRIS BCA', '', 0.00, 1, '2026-05-19 12:19:19');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `pesan`, `is_active`, `created_at`, `created_by`) VALUES
(1, 'Selamat datang di Sistem ERP Gudang Pilar! Harap selalu lakukan stok opname di akhir bulan dan cek masa kadaluarsa bahan baku.', 1, '2026-04-22 06:41:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions_pos`
--

CREATE TABLE `permissions_pos` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `petty_cash_pos`
--

CREATE TABLE `petty_cash_pos` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `shift_history_id` int(11) NOT NULL,
  `jenis` enum('masuk','keluar') DEFAULT 'keluar',
  `nominal` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `petty_cash_pos`
--

INSERT INTO `petty_cash_pos` (`id`, `warehouse_id`, `user_id`, `shift_history_id`, `jenis`, `nominal`, `keterangan`, `created_at`) VALUES
(1, 1, 2, 1, 'keluar', 5000.00, 'Konsumsi Toko', '2026-09-28 21:36:36');

-- --------------------------------------------------------

--
-- Table structure for table `pos_registered_devices`
--

CREATE TABLE `pos_registered_devices` (
  `id` int(11) NOT NULL,
  `device_token` varchar(64) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `warehouse_id` int(11) DEFAULT 1,
  `registered_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_active_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_registered_devices`
--

INSERT INTO `pos_registered_devices` (`id`, `device_token`, `device_name`, `warehouse_id`, `registered_ip`, `user_agent`, `is_active`, `last_active_at`, `created_at`) VALUES
(2, '5052f79aa5ffdfa4d3838a166c3aa611ec778b9c4eaf4b82fa8fba27634b623d', 'Mac Kasir Toko', 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36', 1, '2026-09-29 17:13:04', '2026-08-24 08:39:52'),
(3, '164231268402804897686ce969a1064dd4a7e8a8ae58e933df8bab73f5f0d815', 'Mac Kasir Toko', 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', 1, '2026-08-25 14:22:04', '2026-08-25 13:45:47');

-- --------------------------------------------------------

--
-- Table structure for table `pos_settings`
--

CREATE TABLE `pos_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pos_settings`
--

INSERT INTO `pos_settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'markup_grab', '30'),
(2, 'markup_gojek', '25'),
(3, 'pin_supervisor', '1234'),
(4, 'wa_gateway_api', ''),
(5, 'wa_number_sender', ''),
(16, 'hide_old_history_cashier', '1'),
(17, 'default_start_cash', '500000'),
(48, 'barcode_format', 'CODE128'),
(49, 'barcode_height', '30'),
(50, 'barcode_width', '1'),
(51, 'barcode_paper_size', 'custom'),
(52, 'barcode_paper_custom_w', '40'),
(53, 'barcode_paper_custom_h', '30'),
(54, 'barcode_per_row', '3'),
(55, 'barcode_show_name', ''),
(56, 'barcode_name_position', 'bottom'),
(57, 'barcode_show_sku', '1'),
(58, 'barcode_show_price', '1'),
(59, 'barcode_show_expired', '0'),
(60, 'barcode_show_category', '0'),
(100, 'enable_device_restriction', '0'),
(101, 'device_reg_passcode', '889900');

-- --------------------------------------------------------

--
-- Table structure for table `po_returns`
--

CREATE TABLE `po_returns` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty_return` decimal(10,2) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productions`
--

CREATE TABLE `productions` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `status` enum('pending','masuk_gudang','expired','ditolak','dibatalkan') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_details`
--

CREATE TABLE `production_details` (
  `id` int(11) NOT NULL,
  `production_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `expired_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_plans`
--

CREATE TABLE `production_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `karyawan_id` int(11) NOT NULL,
  `plan_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `production_plan_details`
--

CREATE TABLE `production_plan_details` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `target_qty` int(11) NOT NULL DEFAULT 0,
  `est_adonan_kg` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `modal_price` decimal(15,2) DEFAULT 0.00,
  `price` decimal(10,2) DEFAULT 0.00,
  `online_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_custom_price` tinyint(1) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `warehouse_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_mutations`
--

CREATE TABLE `product_mutations` (
  `id` int(11) NOT NULL,
  `mutation_no` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `from_warehouse_id` int(11) NOT NULL,
  `to_warehouse_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_outs`
--

CREATE TABLE `product_outs` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `origin_invoice` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_warehouse_stocks`
--

CREATE TABLE `product_warehouse_stocks` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_auto_discounts`
--

CREATE TABLE `promo_auto_discounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `min_purchase` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('PERCENT','NOMINAL') NOT NULL DEFAULT 'PERCENT',
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promo_buy_x_get_y`
--

CREATE TABLE `promo_buy_x_get_y` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `buy_product_id` int(11) NOT NULL,
  `buy_qty` int(11) NOT NULL DEFAULT 1,
  `get_product_id` int(11) NOT NULL,
  `get_qty` int(11) NOT NULL DEFAULT 1,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_no` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `shipping_date` date NOT NULL,
  `status` enum('waiting_approval','approved','received','rejected','cancelled') DEFAULT 'waiting_approval',
  `print_po_status` enum('unlocked','locked','pending_approval') NOT NULL DEFAULT 'unlocked',
  `print_po_count` int(11) NOT NULL DEFAULT 0,
  `print_terima_status` enum('unlocked','locked','pending_approval') NOT NULL DEFAULT 'unlocked',
  `print_terima_count` int(11) NOT NULL DEFAULT 0,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

CREATE TABLE `purchase_order_details` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `price` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_payments`
--

CREATE TABLE `purchase_order_payments` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Transfer Bank',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_payments`
--

CREATE TABLE `purchase_payments` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` int(11) NOT NULL,
  `request_no` varchar(50) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `po_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `racks`
--

CREATE TABLE `racks` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `racks`
--

INSERT INTO `racks` (`id`, `name`, `description`) VALUES
(1, 'A-01', ''),
(2, 'A-02', ''),
(3, 'A-03', '');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_slug`, `role_name`) VALUES
(1, 'owner', 'Owner / Pemilik'),
(2, 'admin', 'Admin Gudang'),
(3, 'produksi', 'Tim Produksi'),
(4, 'auditor', 'Auditor'),
(5, 'supervisor_gudang', 'Supervisor Gudang'),
(7, 'pegawai_gudang', 'Pegawai Gudang'),
(10, 'otorisasi', 'Otorisasi'),
(11, 'gudang_pilar', 'Admin Gudang Pilar'),
(14, 'admin_dapur_1', 'admin dapur 1'),
(15, 'admin_dapur_2', 'admin dapur 2'),
(16, 'spvproduksi', 'spv-produksi');

-- --------------------------------------------------------

--
-- Table structure for table `roles_pos`
--

CREATE TABLE `roles_pos` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles_pos`
--

INSERT INTO `roles_pos` (`id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'Kasir');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `permission_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_slug`, `permission_name`) VALUES
(24, 'supervisor_gudang', 'master_gudang'),
(25, 'supervisor_gudang', 'master_produk'),
(26, 'supervisor_gudang', 'master_kategori'),
(27, 'supervisor_gudang', 'master_bahan'),
(28, 'supervisor_gudang', 'master_satuan'),
(29, 'supervisor_gudang', 'master_resep'),
(30, 'supervisor_gudang', 'view_dashboard'),
(31, 'supervisor_gudang', 'stok_opname'),
(229, 'pegawai_gudang', 'master_gudang'),
(230, 'pegawai_gudang', 'edit_master_gudang'),
(231, 'pegawai_gudang', 'hapus_master_gudang'),
(232, 'pegawai_gudang', 'master_produk'),
(233, 'pegawai_gudang', 'edit_master_produk'),
(234, 'pegawai_gudang', 'master_kategori'),
(235, 'pegawai_gudang', 'edit_master_kategori'),
(236, 'pegawai_gudang', 'hapus_master_kategori'),
(237, 'pegawai_gudang', 'master_bahan'),
(238, 'pegawai_gudang', 'edit_master_bahan'),
(239, 'pegawai_gudang', 'hapus_master_bahan'),
(240, 'pegawai_gudang', 'master_satuan'),
(241, 'pegawai_gudang', 'edit_master_satuan'),
(242, 'pegawai_gudang', 'hapus_master_satuan'),
(243, 'pegawai_gudang', 'view_dashboard'),
(244, 'pegawai_gudang', 'stok_opname'),
(245, 'pegawai_gudang', 'otorisasi'),
(350, 'admin_dapur_2', 'akses_dapur_2'),
(351, 'admin_dapur_2', 'manajemen_dapur'),
(352, 'admin_dapur_2', 'edit_manajemen_dapur'),
(353, 'admin_dapur_2', 'hapus_manajemen_dapur'),
(354, 'admin_dapur_2', 'master_bahan'),
(355, 'admin_dapur_2', 'edit_master_bahan'),
(356, 'admin_dapur_2', 'hapus_master_bahan'),
(357, 'admin_dapur_2', 'view_dashboard'),
(443, 'auditor', 'view_dashboard'),
(444, 'auditor', 'audit_logs'),
(445, 'auditor', 'analisa_produk'),
(446, 'auditor', 'laporan_bahan'),
(447, 'auditor', 'laporan_produk_jadi'),
(448, 'auditor', 'laporan_bom'),
(449, 'auditor', 'laporan_opname'),
(450, 'admin_dapur_1', 'akses_dapur_1'),
(451, 'admin_dapur_1', 'manajemen_dapur'),
(452, 'admin_dapur_1', 'edit_manajemen_dapur'),
(453, 'admin_dapur_1', 'hapus_manajemen_dapur'),
(454, 'admin_dapur_1', 'master_bahan'),
(455, 'admin_dapur_1', 'edit_master_bahan'),
(456, 'admin_dapur_1', 'hapus_master_bahan'),
(457, 'admin_dapur_1', 'master_resep'),
(458, 'admin_dapur_1', 'view_dashboard'),
(577, 'otorisasi', 'manajemen_dapur'),
(578, 'otorisasi', 'edit_manajemen_dapur'),
(579, 'otorisasi', 'view_dashboard'),
(580, 'otorisasi', 'stok_opname'),
(581, 'otorisasi', 'otorisasi'),
(582, 'otorisasi', 'laporan_produk_jadi'),
(583, 'otorisasi', 'laporan_bom'),
(584, 'otorisasi', 'laporan_opname'),
(625, 'owner', 'manajemen_dapur'),
(626, 'owner', 'edit_manajemen_dapur'),
(627, 'owner', 'hapus_manajemen_dapur'),
(628, 'owner', 'master_gudang'),
(629, 'owner', 'edit_master_gudang'),
(630, 'owner', 'hapus_master_gudang'),
(631, 'owner', 'master_produk'),
(632, 'owner', 'edit_master_produk'),
(633, 'owner', 'hapus_master_produk'),
(634, 'owner', 'master_kategori'),
(635, 'owner', 'edit_master_kategori'),
(636, 'owner', 'hapus_master_kategori'),
(637, 'owner', 'master_bahan'),
(638, 'owner', 'edit_master_bahan'),
(639, 'owner', 'hapus_master_bahan'),
(640, 'owner', 'master_titipan'),
(641, 'owner', 'edit_master_titipan'),
(642, 'owner', 'hapus_master_titipan'),
(643, 'owner', 'pesanan_custom'),
(644, 'owner', 'edit_pesanan_custom'),
(645, 'owner', 'hapus_pesanan_custom'),
(646, 'owner', 'master_satuan'),
(647, 'owner', 'edit_master_satuan'),
(648, 'owner', 'hapus_master_satuan'),
(649, 'owner', 'master_resep'),
(650, 'owner', 'master_user'),
(651, 'owner', 'master_stok_pusat'),
(652, 'owner', 'edit_master_stok_pusat'),
(653, 'owner', 'hapus_master_stok_pusat'),
(654, 'owner', 'view_dashboard'),
(655, 'owner', 'persetujuan_owner'),
(656, 'owner', 'stok_opname'),
(657, 'owner', 'otorisasi'),
(658, 'owner', 'laporan_produksi'),
(659, 'owner', 'laporan_keluar'),
(660, 'owner', 'lap_keluar_titipan'),
(661, 'owner', 'audit_logs'),
(662, 'owner', 'analisa_produk'),
(663, 'owner', 'laporan_bahan'),
(664, 'owner', 'lapo ran_produk_jadi'),
(665, 'owner', 'laporan_bom'),
(666, 'owner', 'laporan_titipan'),
(667, 'owner', 'lap_target_produksi'),
(668, 'spvproduksi', 'view_dashboard'),
(669, 'spvproduksi', 'stok_opname'),
(670, 'spvproduksi', 'laporan_keluar'),
(671, 'spvproduksi', 'lap_keluar_titipan'),
(672, 'spvproduksi', 'analisa_produk'),
(673, 'spvproduksi', 'laporan_bahan');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions_pos`
--

CREATE TABLE `role_permissions_pos` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_pos`
--

CREATE TABLE `sales_pos` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `external_order_id` varchar(100) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_type` enum('offline','online') DEFAULT 'offline',
  `channel` varchar(50) DEFAULT 'toko',
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(50) DEFAULT NULL,
  `discount_voucher` decimal(10,2) DEFAULT 0.00,
  `voucher_code` varchar(50) DEFAULT NULL,
  `discount_points` decimal(10,2) DEFAULT 0.00,
  `discount_manual` decimal(10,2) DEFAULT 0.00,
  `discount_auto` decimal(15,2) DEFAULT 0.00,
  `points_used` int(11) DEFAULT 0,
  `points_earned` int(11) DEFAULT 0,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_fee_name` varchar(50) DEFAULT NULL,
  `payment_fee_amount` decimal(10,2) DEFAULT 0.00,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_status` enum('lunas','dp') DEFAULT 'lunas',
  `order_status` varchar(20) DEFAULT 'new',
  `cancellation_status` enum('none','partial','full') DEFAULT 'none',
  `cancelled_amount` decimal(10,2) DEFAULT 0.00,
  `production_status` enum('pending','diproses','selesai') DEFAULT 'pending',
  `amount_paid` decimal(10,2) NOT NULL,
  `dp_amount` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `is_po` tinyint(1) DEFAULT 0,
  `settled_at` datetime DEFAULT NULL COMMENT 'Waktu pelunasan piutang/DP'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_pos`
--

INSERT INTO `sales_pos` (`id`, `warehouse_id`, `invoice_no`, `external_order_id`, `customer_id`, `order_type`, `channel`, `subtotal`, `shipping_cost`, `notes`, `driver_name`, `driver_phone`, `discount_voucher`, `voucher_code`, `discount_points`, `discount_manual`, `discount_auto`, `points_used`, `points_earned`, `total_amount`, `payment_method`, `payment_fee_name`, `payment_fee_amount`, `payment_reference`, `payment_status`, `order_status`, `cancellation_status`, `cancelled_amount`, `production_status`, `amount_paid`, `dp_amount`, `change_amount`, `created_at`, `pickup_date`, `pickup_time`, `is_po`, `settled_at`) VALUES
(1, 1, 'INV-20260929001039-984', NULL, NULL, 'offline', 'toko', 100000.00, 0.00, NULL, NULL, NULL, 0.00, NULL, 0.00, 0.00, 0.00, 0, 0, 100000.00, 'Cash', NULL, 0.00, NULL, 'lunas', 'new', 'none', 0.00, 'pending', 100000.00, 0.00, 0.00, '2026-09-28 22:10:39', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sale_cancellations_pos`
--

CREATE TABLE `sale_cancellations_pos` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `cancellation_type` enum('partial','full') NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_cash_deducted` tinyint(1) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `authorized_by_pin` varchar(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_cancellation_items_pos`
--

CREATE TABLE `sale_cancellation_items_pos` (
  `id` int(11) NOT NULL,
  `cancellation_id` int(11) NOT NULL,
  `sale_detail_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_details_pos`
--

CREATE TABLE `sale_details_pos` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `is_custom` tinyint(1) DEFAULT 0,
  `custom_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `cancelled_qty` int(11) DEFAULT 0,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_type` varchar(20) DEFAULT 'none',
  `discount_value` decimal(15,2) DEFAULT 0.00,
  `created_by_user` int(11) DEFAULT NULL COMMENT 'ID kasir yang menambahkan item custom ini ke transaksi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_details_pos`
--

INSERT INTO `sale_details_pos` (`id`, `sale_id`, `product_id`, `is_custom`, `custom_name`, `price`, `qty`, `cancelled_qty`, `subtotal`, `discount_type`, `discount_value`, `created_by_user`) VALUES
(1, 1, 0, 1, 'kue bolu', 100000.00, 1, 0, 100000.00, 'none', 0.00, 2);

-- --------------------------------------------------------

--
-- Table structure for table `sale_payments_pos`
--

CREATE TABLE `sale_payments_pos` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `payment_type` enum('full','dp','pelunasan') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_payments_pos`
--

INSERT INTO `sale_payments_pos` (`id`, `sale_id`, `amount`, `payment_method`, `payment_type`, `created_at`) VALUES
(1, 1, 100000.00, 'Cash', 'full', '2026-09-28 22:10:39');

-- --------------------------------------------------------

--
-- Table structure for table `saved_custom_items_pos`
--

CREATE TABLE `saved_custom_items_pos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID user/kasir yang membuat item custom ini',
  `is_custom_price` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_custom_reguler_pos`
--

CREATE TABLE `saved_custom_reguler_pos` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'ID user/kasir yang membuat item custom reguler ini',
  `is_custom_price` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_custom_reguler_pos`
--

INSERT INTO `saved_custom_reguler_pos` (`id`, `name`, `price`, `created_at`, `created_by`, `is_custom_price`) VALUES
(1, 'kue bolu', 100000.00, '2026-09-28 22:10:36', 2, 0);

-- --------------------------------------------------------

--
-- Table structure for table `shifts_history_pos`
--

CREATE TABLE `shifts_history_pos` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `start_cash` decimal(15,2) DEFAULT 0.00,
  `start_qris` decimal(15,2) DEFAULT 0.00,
  `end_cash` decimal(15,2) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shifts_history_pos`
--

INSERT INTO `shifts_history_pos` (`id`, `warehouse_id`, `user_id`, `shift_id`, `start_time`, `end_time`, `start_cash`, `start_qris`, `end_cash`, `status`) VALUES
(1, 1, 2, 0, '2026-09-14 00:39:18', '2026-09-29 04:43:23', 500000.00, 0.00, 100000.00, 'closed'),
(2, 1, 2, 0, '2026-09-29 04:46:12', NULL, 500000.00, 0.00, NULL, 'open');

-- --------------------------------------------------------

--
-- Table structure for table `stok_opname`
--

CREATE TABLE `stok_opname` (
  `id` int(11) NOT NULL,
  `opname_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_opname_details`
--

CREATE TABLE `stok_opname_details` (
  `id` int(11) NOT NULL,
  `opname_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `system_stock` decimal(10,2) NOT NULL,
  `physical_stock` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stok_opname_keys`
--

CREATE TABLE `stok_opname_keys` (
  `id` int(11) NOT NULL,
  `access_code` varchar(10) NOT NULL,
  `valid_until` datetime NOT NULL,
  `status` enum('active','used','expired') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stok_opname_keys`
--

INSERT INTO `stok_opname_keys` (`id`, `access_code`, `valid_until`, `status`, `created_by`, `created_at`) VALUES
(1, '059803', '2026-09-26 17:31:12', 'active', 20, '2026-09-25 15:31:12');

-- --------------------------------------------------------

--
-- Table structure for table `store_profile`
--

CREATE TABLE `store_profile` (
  `id` int(11) NOT NULL,
  `dashboard_announcement` varchar(255) DEFAULT NULL,
  `store_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `req_approval_in` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Barang Masuk Manual',
  `req_approval_out` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Barang Keluar Manual',
  `req_approval_po` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Purchase Order',
  `req_approval_pr` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Permintaan Barang',
  `req_approval_print` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Izin Cetak',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_profile`
--

INSERT INTO `store_profile` (`id`, `dashboard_announcement`, `store_name`, `phone`, `email`, `address`, `logo_path`, `req_approval_in`, `req_approval_out`, `req_approval_po`, `req_approval_pr`, `req_approval_print`, `updated_at`) VALUES
(1, 'Stok opname', 'ROTIKU ERP', '(061) 1234567', 'logistik@rotiku.com', 'Jl. Gudang Utama No. 123, Medan, Sumatera Utara', 'uploads/logo_toko_1776839134.png', 1, 1, 1, 1, 1, '2026-04-30 16:28:26');

-- --------------------------------------------------------

--
-- Table structure for table `store_settings_pos`
--

CREATE TABLE `store_settings_pos` (
  `id` int(11) NOT NULL,
  `store_name` varchar(100) NOT NULL,
  `store_address` text DEFAULT NULL,
  `store_phone` varchar(20) DEFAULT NULL,
  `receipt_footer` varchar(255) DEFAULT 'Terima Kasih Atas Kunjungan Anda!',
  `logo` varchar(255) DEFAULT NULL,
  `default_start_cash` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_settings_pos`
--

INSERT INTO `store_settings_pos` (`id`, `store_name`, `store_address`, `store_phone`, `receipt_footer`, `logo`, `default_start_cash`) VALUES
(1, 'Love Cakes', 'Jl. Merdeka No. 1, Medan', '081234567890', 'Terima Kasih Atas Kunjungan Anda!', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `store_titipan_stocks`
--

CREATE TABLE `store_titipan_stocks` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL COMMENT 'Lokasi Store/Gudang',
  `titipan_id` int(11) NOT NULL COMMENT 'ID dari master barang_titipan',
  `stock` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_pins`
--

CREATE TABLE `supervisor_pins` (
  `id` int(11) NOT NULL,
  `pin_type` varchar(50) DEFAULT 'delete_production',
  `pin_code` varchar(10) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_pins`
--

INSERT INTO `supervisor_pins` (`id`, `pin_type`, `pin_code`, `updated_at`) VALUES
(1, 'delete_production', '123456', '2026-04-10 16:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor_pins_pos`
--

CREATE TABLE `supervisor_pins_pos` (
  `id` int(11) NOT NULL,
  `pin` varchar(6) NOT NULL,
  `note` varchar(100) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor_pins_pos`
--

INSERT INTO `supervisor_pins_pos` (`id`, `pin`, `note`, `is_used`, `used_at`, `created_at`) VALUES
(19, '280939', NULL, 1, '2026-07-25 19:53:35', '2026-07-09 07:59:59'),
(21, '868945', NULL, 0, NULL, '2026-07-25 12:58:41'),
(22, '434707', NULL, 0, NULL, '2026-07-25 12:58:41'),
(23, '411076', NULL, 0, NULL, '2026-07-25 12:58:41'),
(24, '085817', NULL, 0, NULL, '2026-07-25 12:58:41'),
(25, '484092', NULL, 1, '2026-07-25 20:01:41', '2026-07-25 12:58:41');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `menu` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `menu`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'reset_database', 'Reset Data', 'Inisialisasi Go-Live: RESET TOTAL (Termasuk Master)', '::1', '2026-09-13 17:37:41'),
(2, 1, 'get_stats', 'Reset Data', 'Eksekusi [get_stats] di menu [Reset Data]. Data: []', '::1', '2026-09-13 17:37:42'),
(3, 20, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-13 17:38:55'),
(4, 20, 'init', 'Pembayaran-po', 'Eksekusi [init] di menu [Pembayaran-po]. Data: []', '::1', '2026-09-13 17:39:01'),
(5, 20, 'init_form', 'Inventory', 'Eksekusi [init_form] di menu [Inventory]. Data: []', '::1', '2026-09-13 17:39:03'),
(6, 1, 'get_roles', 'Master User', 'Eksekusi [get_roles] di menu [Master User]. Data: []', '::1', '2026-09-17 10:09:15'),
(7, 1, 'read_users', 'Master User', 'Eksekusi [read_users] di menu [Master User]. Data: []', '::1', '2026-09-17 10:09:15'),
(8, 1, 'read_employees', 'Master User', 'Eksekusi [read_employees] di menu [Master User]. Data: []', '::1', '2026-09-17 10:09:15'),
(9, 1, 'save', 'Manajemen Role', 'Eksekusi [save] di menu [Manajemen Role]. Data: {\"mode\":\"add\",\"old_slug\":\"\",\"role_name\":\"spv-produksi\",\"role_slug\":\"spvproduksi\",\"permissions\":[\"view_dashboard\",\"stok_opname\",\"laporan_keluar\",\"lap_keluar_titipan\",\"analisa_produk\",\"laporan_bahan\"]}', '::1', '2026-09-17 10:10:08'),
(10, 1, 'get_roles', 'Master User', 'Eksekusi [get_roles] di menu [Master User]. Data: []', '::1', '2026-09-17 10:10:11'),
(11, 1, 'read_users', 'Master User', 'Eksekusi [read_users] di menu [Master User]. Data: []', '::1', '2026-09-17 10:10:11'),
(12, 1, 'read_employees', 'Master User', 'Eksekusi [read_employees] di menu [Master User]. Data: []', '::1', '2026-09-17 10:10:11'),
(13, 1, 'save_user', 'Master User', 'Eksekusi [save_user] di menu [Master User]. Data: {\"id\":\"\",\"name\":\"spv-dapur\",\"username\":\"spvdapur\",\"password\":\"******\",\"role\":\"spvproduksi\",\"kitchen_id\":\"\"}', '::1', '2026-09-17 10:10:29'),
(14, 1, 'read_users', 'Master User', 'Eksekusi [read_users] di menu [Master User]. Data: []', '::1', '2026-09-17 10:10:29'),
(15, 20, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-17 10:28:07'),
(16, 20, 'save', 'Manajemen-role', 'Eksekusi [save] di menu [Manajemen-role]. Data: {\"role_id\":\"\",\"role_name\":\"spv gudang\",\"role_slug\":\"spv_gudang\",\"permissions\":[\"dashboard\",\"scanner_opname\",\"lap_barang_masuk\",\"lap_barang_keluar\",\"lap_stok_opname\",\"lap_kartu_stok\",\"lap_perbandingan_harga\",\"lap_supplier\"],\"action\":\"save\"}', '::1', '2026-09-17 10:28:32'),
(17, 20, 'get_roles', 'User-management', 'Eksekusi [get_roles] di menu [User-management]. Data: []', '::1', '2026-09-17 10:28:35'),
(18, 20, 'save', 'User-management', 'Eksekusi [save] di menu [User-management]. Data: {\"user_id\":\"\",\"name\":\"randy\",\"username\":\"spv-gudang\",\"password\":\"******\",\"role\":\"spv_gudang\",\"status\":\"active\",\"action\":\"save\"}', '::1', '2026-09-17 10:28:48'),
(19, 20, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-17 10:28:57'),
(20, 20, 'get_roles', 'User-management', 'Eksekusi [get_roles] di menu [User-management]. Data: []', '::1', '2026-09-17 10:29:00'),
(21, 28, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-17 10:29:10'),
(22, 20, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-25 15:28:28'),
(23, 20, 'init', 'Pembayaran-po', 'Eksekusi [init] di menu [Pembayaran-po]. Data: []', '::1', '2026-09-25 15:29:42'),
(24, 20, 'init', 'Kartu-stok', 'Eksekusi [init] di menu [Kartu-stok]. Data: []', '::1', '2026-09-25 15:29:45'),
(25, 20, 'init', 'Supplier-terakhir', 'Eksekusi [init] di menu [Supplier-terakhir]. Data: []', '::1', '2026-09-25 15:29:52'),
(26, 20, 'init_form', 'Inventory', 'Eksekusi [init_form] di menu [Inventory]. Data: []', '::1', '2026-09-25 15:29:59'),
(27, 20, 'read_racks', 'Monitoring Rak', 'Eksekusi [read_racks] di menu [Monitoring Rak]. Data: []', '::1', '2026-09-25 15:30:08'),
(28, 20, 'init_form', 'Barang Masuk', 'Eksekusi [init_form] di menu [Barang Masuk]. Data: []', '::1', '2026-09-25 15:30:09'),
(29, 20, 'init_form', 'Barang Keluar', 'Eksekusi [init_form] di menu [Barang Keluar]. Data: []', '::1', '2026-09-25 15:30:16'),
(30, 20, 'init_data', 'Scanner', 'Eksekusi [init_data] di menu [Scanner]. Data: []', '::1', '2026-09-25 15:31:06'),
(31, 20, 'verify_pin', 'Scanner', 'Eksekusi [verify_pin] di menu [Scanner]. Data: {\"action\":\"verify_pin\",\"pin\":\"398038\"}', '::1', '2026-09-25 15:31:09'),
(32, 20, 'generate', 'Otorisasi', 'Eksekusi [generate] di menu [Otorisasi]. Data: []', '::1', '2026-09-25 15:31:12'),
(33, 20, 'init_data', 'Scanner', 'Eksekusi [init_data] di menu [Scanner]. Data: []', '::1', '2026-09-25 15:31:16'),
(34, 20, 'verify_pin', 'Scanner', 'Eksekusi [verify_pin] di menu [Scanner]. Data: {\"action\":\"verify_pin\",\"pin\":\"059803\"}', '::1', '2026-09-25 15:31:17'),
(35, 1, 'get_stats', 'Reset Data', 'Eksekusi [get_stats] di menu [Reset Data]. Data: []', '::1', '2026-09-25 15:32:15'),
(36, 20, 'get_dashboard_data', 'Dashboard', 'Eksekusi [get_dashboard_data] di menu [Dashboard]. Data: []', '::1', '2026-09-25 15:42:07'),
(37, 20, 'init_form', 'Inventory', 'Eksekusi [init_form] di menu [Inventory]. Data: []', '::1', '2026-09-25 15:42:12'),
(38, 18, 'init_form', 'Input Produksi', 'Eksekusi [init_form] di menu [Input Produksi]. Data: []', '::1', '2026-09-29 15:38:36'),
(39, 18, 'init', 'Rencana Harian', 'Eksekusi [init] di menu [Rencana Harian]. Data: []', '::1', '2026-09-29 15:38:39'),
(40, 18, 'read_today', 'Rencana Harian', 'Eksekusi [read_today] di menu [Rencana Harian]. Data: []', '::1', '2026-09-29 15:38:39');

-- --------------------------------------------------------

--
-- Table structure for table `titipan_productions`
--

CREATE TABLE `titipan_productions` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `status` enum('pending','received','ditolak','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `titipan_production_details`
--

CREATE TABLE `titipan_production_details` (
  `id` int(11) NOT NULL,
  `titipan_production_id` int(11) NOT NULL,
  `titipan_id` int(11) NOT NULL COMMENT 'ID dari tabel barang_titipan',
  `quantity` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`) VALUES
(1, 'Gram'),
(2, 'Kg'),
(4, 'Liter'),
(8, 'Ml'),
(3, 'Ons');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL,
  `gudang_role_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `kitchen_id` int(11) DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `gudang_role_id`, `status`, `kitchen_id`, `warehouse_id`, `created_at`) VALUES
(1, 'Bapak Owner', 'owner-produksi', '$2y$10$zL1ZzrWsObkz8kfMeJGCB.IHUXB9dirvia9Y/iMG9rTPY3qpLqJsO', 'owner', NULL, 'active', NULL, NULL, '2026-03-26 09:36:51'),
(2, 'pegawai produksi', 'produksi', '$2y$10$9vrDYAu2zA/HZ.TGZ0hd0eSl4JopCmcj0u70V1IuaNmBDZF4zIZvi', 'produksi', NULL, 'active', 1, NULL, '2026-03-26 09:36:51'),
(3, 'Citra Admin', 'admin', '$2y$10$uQ.zKHEj05TBw4W/9l2a6.pqeTRvILO47OshcGypVK3pK5vqsazv6', 'admin', NULL, 'active', NULL, NULL, '2026-03-26 09:36:51'),
(4, 'Randy', 'randy', '$2y$10$GzrBP4d8/A4n1ZtKWh4zF.fCF2oFK6jDY6CcgVFBLgk1gEfzylv4O', 'produksi', NULL, 'active', NULL, NULL, '2026-03-28 12:56:33'),
(7, 'karna', 'karna', '$2y$10$0JZy1qXn2M1i31fSgHHlJuNZqligtI0qlBCK9P8av5HlKCdBONr4S', 'supervisor_gudang', NULL, 'active', NULL, NULL, '2026-04-09 09:05:15'),
(16, 'Admin Dapur 1', 'dapur1', '$2y$10$Fv357vO5DRE1VgvlrBjT4u84L6aW3/uU/oV1a2twRvspozvCx/RTW', 'admin_dapur_1', NULL, 'active', 1, NULL, '2026-04-13 16:04:20'),
(17, 'admin dapur 2', 'dapur2', '$2y$10$.O8fl7TZ9dOVjp4kn/0HDeGAl/RXSVESyWlsEPpdcr1z78ffl80pi', 'admin_dapur_2', NULL, 'active', 2, NULL, '2026-04-13 16:05:14'),
(18, 'admin produksi dapur 1', 'produksi1', '$2y$10$a5azpqqPBdZTu3/tL9IEC.kVL8FyboNbJy5KHqYSBZibR8QRsrMbC', 'produksi', NULL, 'active', 1, NULL, '2026-04-14 17:37:40'),
(19, 'admin produksi dapur 2', 'produksi2', '$2y$10$f4jtgpbaWJbt0oCyF8xV.OceN.0Uck.yK/njnRN4ASBHmZkjyLxr.', 'produksi', NULL, 'active', 2, NULL, '2026-04-14 17:38:09'),
(20, 'Randy admin gudang', 'owner-gudang', '$2y$10$FhrDdo8uQtIYxdMV5sci9.v4pSVOUqXxIx4d/DswXMjGwepvoV0r6', 'owner_gudang', 1, 'active', NULL, NULL, '2026-04-19 14:20:51'),
(23, 'admin-gudang', 'admin-gudang', '$2y$10$yiDR1dGiJpPHrGfz5seAremH3qdDDCHa6zCkTueCwXD4I74.jdKMm', 'admin', NULL, 'active', NULL, NULL, '2026-04-22 07:23:36'),
(27, 'spv-dapur', 'spvdapur', '$2y$10$1tF4TW.axf7IrbpSdrk1KuF4o.Zv71refSH/HfedRBWkThgNJfO/q', 'spvproduksi', NULL, 'active', NULL, NULL, '2026-09-17 10:10:29'),
(28, 'randy', 'spv-gudang', '$2y$10$5A6skTX2Fq1EKWYftA6q5.s.rhal9VsqA7UEKm3NvSPTZ7.adtyOm', 'spv_gudang', NULL, 'active', NULL, NULL, '2026-09-17 10:28:48');

-- --------------------------------------------------------

--
-- Table structure for table `users_pos`
--

CREATE TABLE `users_pos` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_pos`
--

INSERT INTO `users_pos` (`id`, `name`, `username`, `password`, `role_id`, `warehouse_id`, `created_at`) VALUES
(1, 'Owner Backoffice', 'admin', '$2y$10$VdU4APqMaC8KLLinT7KvTe0Bw1RY8s2EWjzF41FNfG3pmZvBj3xhe', 1, NULL, '2026-05-05 21:09:53'),
(2, 'Kasir Utama', 'kasir1', '$2y$10$Tyx0fVTa7r50CkWTpNozf.rYbuo1UYpkKfFw5tY/YNIFhku.N6Hxm', 2, 1, '2026-05-13 14:52:49'),
(3, 'Pegawai Toko', 'pegawai', '$2y$10$tmPyuZ.UXrcwvhGnR04pJO2Bf5R9267HElGyeoQ1gdWpXUomIm8Rq', 2, NULL, '2026-06-28 14:40:21'),
(5, 'randy', 'kasir2', '$2y$10$I0svjk8DLND23smomtWDjuKUz2QX09Id2QnOQbLpLlKzARRLPeq9a', 2, 2, '2026-06-29 21:54:44');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers_pos`
--

CREATE TABLE `vouchers_pos` (
  `id` int(11) NOT NULL,
  `voucher_code` varchar(50) NOT NULL,
  `voucher_name` varchar(100) NOT NULL,
  `discount_type` enum('IDR','PERCENT') DEFAULT 'IDR',
  `discount_amount` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `max_usage` int(11) DEFAULT 0 COMMENT '0 = unlimited',
  `used_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('material','product') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `code`, `name`, `type`) VALUES
(1, 'GDG-01', 'gudang 01', 'material'),
(2, 'GDG-02', 'Gudang 02', 'material');

-- --------------------------------------------------------

--
-- Table structure for table `wa_templates_pos`
--

CREATE TABLE `wa_templates_pos` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `template_text` text NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wa_templates_pos`
--

INSERT INTO `wa_templates_pos` (`id`, `title`, `template_text`, `category`, `is_default`, `created_at`) VALUES
(1, '🎂 Ucapan Selamat Ulang Tahun & Voucher', 'Halo Kak {nama}! 🎉🎂\n\nSelamat Ulang Tahun dari segenap keluarga besar *{toko}*! 🥳\nSemoga panjang umur, sehat selalu, dan dilancarkan segala urusannya.\n\nSpesial di hari bahagia Kakak, kami memberikan Voucher Diskon Spesial Ulang Tahun untuk pembelian cake favoritmu! Total Poin Loyalitas Kakak saat ini: *{poin} Poin* ✨\n\nYuk rayakan hari manismu bersama kami di *{toko}*! 🍰🎂', 'birthday', 1, '2026-08-25 07:39:12'),
(2, '🎁 Promo Loyalitas & Reminder Poin Member', 'Halo Kak {nama} dari *{toko}*! 👋\n\nKami menginfokan bahwa Kakak saat ini memiliki *{poin} Poin Loyalitas* aktif yang bisa ditukarkan dengan diskon langsung saat berbelanja di outlet kami lho! 🎁\n\nAda banyak pilihan cake dan pastry fresh baru yang siap dinikmati hari ini. Ditunggu kedatangannya ya Kak! 🍰✨', 'promo', 0, '2026-08-25 07:39:12'),
(3, '✨ Sapaan Hangat & Layanan Pelanggan', 'Halo Kak {nama}! 👋\n\nTerima kasih telah menjadi pelanggan setia *{toko}*. Kami selalu siap melayani pesanan cake, hampers, dan kue favorit untuk setiap momen spesial Kakak.\n\nJangan ragu untuk pesan atau tanya ketersediaan menu favoritmu melalui WhatsApp ini ya. Semoga harimu menyenangkan! 🌸🍰', 'greeting', 0, '2026-08-25 07:39:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_codes`
--
ALTER TABLE `access_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `app_migrations`
--
ALTER TABLE `app_migrations`
  ADD PRIMARY KEY (`migration_key`);

--
-- Indexes for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barang_titipan`
--
ALTER TABLE `barang_titipan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `barang_titipan_keluar`
--
ALTER TABLE `barang_titipan_keluar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bom`
--
ALTER TABLE `bom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `bom_custom`
--
ALTER TABLE `bom_custom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `custom_item_id` (`custom_item_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `bom_requests`
--
ALTER TABLE `bom_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bom_req_no` (`request_no`);

--
-- Indexes for table `bom_request_details`
--
ALTER TABLE `bom_request_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers_pos`
--
ALTER TABLE `customers_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_delivery_payment_methods_pos`
--
ALTER TABLE `food_delivery_payment_methods_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `food_delivery_platforms_pos`
--
ALTER TABLE `food_delivery_platforms_pos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_code` (`platform_code`);

--
-- Indexes for table `food_delivery_prices_pos`
--
ALTER TABLE `food_delivery_prices_pos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_platform_item` (`platform_code`,`item_type`,`item_id`);

--
-- Indexes for table `gudang_roles`
--
ALTER TABLE `gudang_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_slug` (`role_slug`);

--
-- Indexes for table `gudang_role_permissions`
--
ALTER TABLE `gudang_role_permissions`
  ADD UNIQUE KEY `gudang_role_perm_unique` (`role_id`,`permission_slug`);

--
-- Indexes for table `gudang_stok_opnames`
--
ALTER TABLE `gudang_stok_opnames`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `opname_no` (`opname_no`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `gudang_stok_opname_details`
--
ALTER TABLE `gudang_stok_opname_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opname_id` (`opname_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `inventory_history_pos`
--
ALTER TABLE `inventory_history_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kitchens`
--
ALTER TABLE `kitchens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loyalty_settings_pos`
--
ALTER TABLE `loyalty_settings_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_lokasi_rak`
--
ALTER TABLE `master_lokasi_rak`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_rak` (`kode_rak`);

--
-- Indexes for table `master_shifts_pos`
--
ALTER TABLE `master_shifts_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `materials_stocks`
--
ALTER TABLE `materials_stocks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_categories`
--
ALTER TABLE `material_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_opnames`
--
ALTER TABLE `material_opnames`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_requests`
--
ALTER TABLE `material_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `material_requests_header`
--
ALTER TABLE `material_requests_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_req_no` (`request_no`);

--
-- Indexes for table `opname_history_pos`
--
ALTER TABLE `opname_history_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions_pos`
--
ALTER TABLE `permissions_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `petty_cash_pos`
--
ALTER TABLE `petty_cash_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_registered_devices`
--
ALTER TABLE `pos_registered_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_token` (`device_token`);

--
-- Indexes for table `pos_settings`
--
ALTER TABLE `pos_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`setting_key`);

--
-- Indexes for table `po_returns`
--
ALTER TABLE `po_returns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productions`
--
ALTER TABLE `productions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `production_details`
--
ALTER TABLE `production_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `production_id` (`production_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `production_plans`
--
ALTER TABLE `production_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_plan_per_day` (`karyawan_id`,`plan_date`);

--
-- Indexes for table `production_plan_details`
--
ALTER TABLE `production_plan_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `warehouse_id` (`warehouse_id`);

--
-- Indexes for table `product_mutations`
--
ALTER TABLE `product_mutations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_outs`
--
ALTER TABLE `product_outs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_warehouse_stocks`
--
ALTER TABLE `product_warehouse_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_prod_wh` (`product_id`,`warehouse_id`);

--
-- Indexes for table `promo_auto_discounts`
--
ALTER TABLE `promo_auto_discounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promo_buy_x_get_y`
--
ALTER TABLE `promo_buy_x_get_y`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_order_payments`
--
ALTER TABLE `purchase_order_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `racks`
--
ALTER TABLE `racks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_slug` (`role_slug`);

--
-- Indexes for table `roles_pos`
--
ALTER TABLE `roles_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_slug` (`role_slug`);

--
-- Indexes for table `role_permissions_pos`
--
ALTER TABLE `role_permissions_pos`
  ADD PRIMARY KEY (`role_id`,`permission_id`);

--
-- Indexes for table `sales_pos`
--
ALTER TABLE `sales_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `customer_id_2` (`customer_id`),
  ADD KEY `customer_id_3` (`customer_id`);

--
-- Indexes for table `sale_cancellations_pos`
--
ALTER TABLE `sale_cancellations_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `sale_cancellation_items_pos`
--
ALTER TABLE `sale_cancellation_items_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cancellation_id` (`cancellation_id`),
  ADD KEY `sale_detail_id` (`sale_detail_id`);

--
-- Indexes for table `sale_details_pos`
--
ALTER TABLE `sale_details_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `sale_payments_pos`
--
ALTER TABLE `sale_payments_pos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `payment_method` (`payment_method`);

--
-- Indexes for table `saved_custom_items_pos`
--
ALTER TABLE `saved_custom_items_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `saved_custom_reguler_pos`
--
ALTER TABLE `saved_custom_reguler_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shifts_history_pos`
--
ALTER TABLE `shifts_history_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_opname`
--
ALTER TABLE `stok_opname`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_opname_details`
--
ALTER TABLE `stok_opname_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stok_opname_keys`
--
ALTER TABLE `stok_opname_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_profile`
--
ALTER TABLE `store_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_settings_pos`
--
ALTER TABLE `store_settings_pos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_titipan_stocks`
--
ALTER TABLE `store_titipan_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_store_titipan` (`warehouse_id`,`titipan_id`);

--
-- Indexes for table `supervisor_pins`
--
ALTER TABLE `supervisor_pins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supervisor_pins_pos`
--
ALTER TABLE `supervisor_pins_pos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pin` (`pin`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `titipan_productions`
--
ALTER TABLE `titipan_productions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `titipan_production_details`
--
ALTER TABLE `titipan_production_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users_pos`
--
ALTER TABLE `users_pos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vouchers_pos`
--
ALTER TABLE `vouchers_pos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_code` (`voucher_code`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wa_templates_pos`
--
ALTER TABLE `wa_templates_pos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_codes`
--
ALTER TABLE `access_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_titipan`
--
ALTER TABLE `barang_titipan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_titipan_keluar`
--
ALTER TABLE `barang_titipan_keluar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bom`
--
ALTER TABLE `bom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bom_custom`
--
ALTER TABLE `bom_custom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bom_requests`
--
ALTER TABLE `bom_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bom_request_details`
--
ALTER TABLE `bom_request_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers_pos`
--
ALTER TABLE `customers_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `food_delivery_payment_methods_pos`
--
ALTER TABLE `food_delivery_payment_methods_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `food_delivery_platforms_pos`
--
ALTER TABLE `food_delivery_platforms_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `food_delivery_prices_pos`
--
ALTER TABLE `food_delivery_prices_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `gudang_roles`
--
ALTER TABLE `gudang_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gudang_stok_opnames`
--
ALTER TABLE `gudang_stok_opnames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gudang_stok_opname_details`
--
ALTER TABLE `gudang_stok_opname_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_history_pos`
--
ALTER TABLE `inventory_history_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kitchens`
--
ALTER TABLE `kitchens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loyalty_settings_pos`
--
ALTER TABLE `loyalty_settings_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `master_lokasi_rak`
--
ALTER TABLE `master_lokasi_rak`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_shifts_pos`
--
ALTER TABLE `master_shifts_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `materials_stocks`
--
ALTER TABLE `materials_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_categories`
--
ALTER TABLE `material_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_opnames`
--
ALTER TABLE `material_opnames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_requests`
--
ALTER TABLE `material_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `material_requests_header`
--
ALTER TABLE `material_requests_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `opname_history_pos`
--
ALTER TABLE `opname_history_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permissions_pos`
--
ALTER TABLE `permissions_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petty_cash_pos`
--
ALTER TABLE `petty_cash_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pos_registered_devices`
--
ALTER TABLE `pos_registered_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pos_settings`
--
ALTER TABLE `pos_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `po_returns`
--
ALTER TABLE `po_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productions`
--
ALTER TABLE `productions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_details`
--
ALTER TABLE `production_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_plans`
--
ALTER TABLE `production_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `production_plan_details`
--
ALTER TABLE `production_plan_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_mutations`
--
ALTER TABLE `product_mutations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_outs`
--
ALTER TABLE `product_outs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_warehouse_stocks`
--
ALTER TABLE `product_warehouse_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_auto_discounts`
--
ALTER TABLE `promo_auto_discounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_buy_x_get_y`
--
ALTER TABLE `promo_buy_x_get_y`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_payments`
--
ALTER TABLE `purchase_order_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `racks`
--
ALTER TABLE `racks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `roles_pos`
--
ALTER TABLE `roles_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=674;

--
-- AUTO_INCREMENT for table `sales_pos`
--
ALTER TABLE `sales_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_cancellations_pos`
--
ALTER TABLE `sale_cancellations_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_cancellation_items_pos`
--
ALTER TABLE `sale_cancellation_items_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_details_pos`
--
ALTER TABLE `sale_details_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_payments_pos`
--
ALTER TABLE `sale_payments_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saved_custom_items_pos`
--
ALTER TABLE `saved_custom_items_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_custom_reguler_pos`
--
ALTER TABLE `saved_custom_reguler_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shifts_history_pos`
--
ALTER TABLE `shifts_history_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stok_opname`
--
ALTER TABLE `stok_opname`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_opname_details`
--
ALTER TABLE `stok_opname_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stok_opname_keys`
--
ALTER TABLE `stok_opname_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_profile`
--
ALTER TABLE `store_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_settings_pos`
--
ALTER TABLE `store_settings_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_titipan_stocks`
--
ALTER TABLE `store_titipan_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supervisor_pins`
--
ALTER TABLE `supervisor_pins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `supervisor_pins_pos`
--
ALTER TABLE `supervisor_pins_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `titipan_productions`
--
ALTER TABLE `titipan_productions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `titipan_production_details`
--
ALTER TABLE `titipan_production_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users_pos`
--
ALTER TABLE `users_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `vouchers_pos`
--
ALTER TABLE `vouchers_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wa_templates_pos`
--
ALTER TABLE `wa_templates_pos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_codes`
--
ALTER TABLE `access_codes`
  ADD CONSTRAINT `access_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bom`
--
ALTER TABLE `bom`
  ADD CONSTRAINT `bom_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gudang_role_permissions`
--
ALTER TABLE `gudang_role_permissions`
  ADD CONSTRAINT `gudang_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `gudang_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gudang_stok_opnames`
--
ALTER TABLE `gudang_stok_opnames`
  ADD CONSTRAINT `gudang_stok_opnames_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `gudang_stok_opname_details`
--
ALTER TABLE `gudang_stok_opname_details`
  ADD CONSTRAINT `gudang_stok_opname_details_ibfk_1` FOREIGN KEY (`opname_id`) REFERENCES `gudang_stok_opnames` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gudang_stok_opname_details_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials_stocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `productions`
--
ALTER TABLE `productions`
  ADD CONSTRAINT `productions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `productions_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `production_details`
--
ALTER TABLE `production_details`
  ADD CONSTRAINT `production_details_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `productions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_slug`) REFERENCES `roles` (`role_slug`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales_pos`
--
ALTER TABLE `sales_pos`
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers_pos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sale_details_pos`
--
ALTER TABLE `sale_details_pos`
  ADD CONSTRAINT `fk_details_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales_pos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
