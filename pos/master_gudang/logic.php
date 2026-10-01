<?php
require_once '../../config/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT * FROM warehouses ORDER BY id ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'save':
            $id = trim($_POST['id'] ?? '');
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');

            if (empty($code) || empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Kode dan Nama Store/Gudang wajib diisi!']);
                exit;
            }

            // Pastikan kolom type mendukung jika ada
            try {
                $checkCol = $pdo->query("SHOW COLUMNS FROM warehouses LIKE 'type'")->fetch();
                $hasTypeCol = !empty($checkCol);
            } catch (Exception $e) {
                $hasTypeCol = false;
            }

            if (empty($id)) {
                // Tambah Store
                $cek = $pdo->prepare("SELECT id FROM warehouses WHERE code = ?");
                $cek->execute([$code]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kode Store sudah digunakan!']);
                    exit;
                }

                if ($hasTypeCol) {
                    $stmt = $pdo->prepare("INSERT INTO warehouses (code, name, type) VALUES (?, ?, 'product')");
                    $stmt->execute([$code, $name]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO warehouses (code, name) VALUES (?, ?)");
                    $stmt->execute([$code, $name]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Store / Gudang berhasil ditambahkan!']);
            } else {
                // Edit Store
                $cek = $pdo->prepare("SELECT id FROM warehouses WHERE code = ? AND id != ?");
                $cek->execute([$code, $id]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kode Store sudah digunakan cabang lain!']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE warehouses SET code = ?, name = ? WHERE id = ?");
                $stmt->execute([$code, $name, $id]);

                echo json_encode(['status' => 'success', 'message' => 'Store / Gudang berhasil diperbarui!']);
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID Store tidak valid!']);
                exit;
            }

            // Cek apakah store default (ID 1)
            if ($id === 1) {
                echo json_encode(['status' => 'error', 'message' => 'Store utama (Default) tidak dapat dihapus!']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM warehouses WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'Store / Gudang berhasil dihapus!']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
