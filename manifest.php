<?php
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/config/database.php';

$storeName = "Ayam Goreng Hariku POS";
$shortName = "Hariku POS";
$logoPath = "assets/img/logo-hariku.png";
$logoMime = "image/png";

try {
    $stmt = $pdo->query("SELECT store_name, logo FROM store_settings_pos WHERE id = 1 LIMIT 1");
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($store) {
        if (!empty($store['store_name'])) {
            $storeName = trim($store['store_name']);
            $shortName = mb_substr($storeName, 0, 15);
        }
        if (!empty($store['logo'])) {
            $uploadedLogo = __DIR__ . '/assets/img/' . $store['logo'];
            if (file_exists($uploadedLogo)) {
                $logoPath = 'assets/img/' . $store['logo'];
                $ext = strtolower(pathinfo($store['logo'], PATHINFO_EXTENSION));
                if ($ext === 'jpg' || $ext === 'jpeg') $logoMime = 'image/jpeg';
                elseif ($ext === 'webp') $logoMime = 'image/webp';
                elseif ($ext === 'svg') $logoMime = 'image/svg+xml';
                else $logoMime = 'image/png';
            }
        }
    }
} catch (Exception $e) {
    // Fallback default jika DB bermasalah
}

$manifest = [
    "name" => $storeName,
    "short_name" => $shortName,
    "description" => "Sistem Kasir Pintar Offline-First " . $storeName,
    "start_url" => "./pos/kasir/index.php",
    "scope" => "./",
    "display" => "standalone",
    "orientation" => "any",
    "background_color" => "#ffffff",
    "theme_color" => "#FF3870",
    "icons" => [
        [
            "src" => $logoPath,
            "sizes" => "192x192",
            "type" => $logoMime,
            "purpose" => "any"
        ],
        [
            "src" => $logoPath,
            "sizes" => "512x512",
            "type" => $logoMime,
            "purpose" => "any maskable"
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
