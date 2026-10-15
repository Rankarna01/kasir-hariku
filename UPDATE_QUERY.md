```sql
-- Checkout: waktu transaksi dari server (logic_kasir.php)
SELECT created_at FROM sales_pos WHERE invoice_no = ? LIMIT 1;

-- Cetak ulang struk: nama kasir (print_receipt.php)
SELECT s.*, c.name AS customer_name, c.phone AS customer_phone, u.name AS cashier_name
FROM sales_pos s
LEFT JOIN customers_pos c ON s.customer_id = c.id
LEFT JOIN users_pos u ON s.user_id = u.id
WHERE s.invoice_no = ?;

-- Tutup kasir: total tunai shift dihitung otomatis (logic_kasir.php, action close_shift)
SELECT
    (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
      WHERE sp.created_at >= ? AND UPPER(sp.payment_method) = 'CASH')
  - (SELECT COALESCE(SUM(sc.amount), 0) FROM sale_cancellations_pos sc
      WHERE sc.created_at >= ? AND sc.is_cash_deducted = 1);

-- Riwayat shift: total pendapatan per shift (laporan_shift/logic.php, action get_shifts)
SELECT
    (SELECT COALESCE(SUM(sp.amount), 0) FROM sale_payments_pos sp
      WHERE sp.created_at BETWEEN ? AND ? AND sp.payment_type IN ('full', 'dp', 'pelunasan'))
  - (SELECT COALESCE(SUM(sc.amount), 0) FROM sale_cancellations_pos sc
      JOIN sales_pos s2 ON sc.sale_id = s2.id
      WHERE sc.created_at BETWEEN ? AND ?);

-- Migrasi tabel log cetak (opsional, sudah otomatis di pengaturan/printer/logic.php)
CREATE TABLE IF NOT EXISTS `print_logs_pos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sale_id` INT(11) DEFAULT NULL,
  `invoice_no` VARCHAR(60) NOT NULL,
  `device_name` VARCHAR(100) DEFAULT 'Tablet Kasir',
  `mode` VARCHAR(30) DEFAULT 'rawbt',
  `status` ENUM('success', 'failed', 'reported_stuck') DEFAULT 'success',
  `note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrasi kolom toggle struk (opsional, sudah otomatis di pengaturan/printer/logic.php)
ALTER TABLE `store_settings_pos`
  ADD COLUMN IF NOT EXISTS `show_logo` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `show_cashier` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `show_invoice` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `show_notes` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `show_tax_ongkir` TINYINT(1) DEFAULT 1;
```
