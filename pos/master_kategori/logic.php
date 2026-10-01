<?php
require_once '../../config/auth.php';
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'save':
            $id = trim($_POST['id'] ?? '');
            $name = trim($_POST['name'] ?? '');

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Nama Kategori wajib diisi!']);
                exit;
            }

            if (empty($id)) {
                // Tambah Kategori
                $cek = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $cek->execute([$name]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kategori dengan nama ini sudah ada!']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$name]);

                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil ditambahkan!']);
            } else {
                // Edit Kategori
                $cek = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                $cek->execute([$name, $id]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kategori dengan nama ini sudah digunakan!']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);

                echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil diperbarui!']);
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID Kategori tidak valid!']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus!']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
