<?php
require_once '../../config/auth.php';
require_once '../../config/database.php';

$action = $_GET['action'] ?? '';
$uploadDir = __DIR__ . '/../../assets/img/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

try {
    switch ($action) {
        case 'download_template':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Template_Import_Produk.csv');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Kode Produk', 'Nama Produk', 'Kategori', 'Harga Modal', 'Harga Jual Offline', 'Harga Jual Online']);
            fputcsv($output, ['RCK-01', 'Roti Coklat Keju', 'Roti Manis', '3500', '5000', '6500']);
            fputcsv($output, ['RTW-01', 'Roti Tawar Premium', 'Roti Tawar', '8000', '12000', '15000']);
            fclose($output);
            exit;

        case 'import':
            header('Content-Type: application/json; charset=utf-8');
            if (!isset($_FILES['file_import']['tmp_name']) || empty($_FILES['file_import']['tmp_name'])) {
                echo json_encode(['status' => 'error', 'message' => 'File CSV tidak ditemukan!']);
                exit;
            }

            $handle = fopen($_FILES['file_import']['tmp_name'], "r");
            if (!$handle) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file CSV!']);
                exit;
            }

            $sukses = 0; 
            $row = 0;
            $pdo->beginTransaction();

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $row++; 
                if ($row == 1) continue; // Skip header
                $code = strtoupper(trim($data[0] ?? ''));
                $name = trim($data[1] ?? '');
                if (empty($code) || empty($name)) continue;

                $cat   = trim($data[2] ?? '');
                $modal = (float)($data[3] ?? 0);
                $price = (float)($data[4] ?? 0);
                $onPrice = (float)($data[5] ?? 0);

                $stmt = $pdo->prepare("INSERT INTO products (code, name, category, modal_price, price, online_price) 
                                      VALUES (?, ?, ?, ?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE 
                                      name = VALUES(name), 
                                      category = VALUES(category), 
                                      modal_price = VALUES(modal_price), 
                                      price = VALUES(price), 
                                      online_price = VALUES(online_price)");
                $stmt->execute([$code, $name, $cat, $modal, $price, $onPrice]);
                $sukses++;
            }
            fclose($handle);
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => "$sukses produk berhasil diimport!"]);
            break;

        case 'read':
            header('Content-Type: application/json; charset=utf-8');
            $stmt = $pdo->query("SELECT id, code, name, category, image, modal_price, price, online_price FROM products ORDER BY id DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'get_categories':
            header('Content-Type: application/json; charset=utf-8');
            $stmt = $pdo->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");
            $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['status' => 'success', 'data' => $cats]);
            break;

        case 'save':
            header('Content-Type: application/json; charset=utf-8');
            $id = trim($_POST['id'] ?? '');
            $code = strtoupper(trim($_POST['code'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $modal_price = (float)($_POST['modal_price'] ?? 0);
            $price = (float)($_POST['price'] ?? 0);
            $online_price = (float)($_POST['online_price'] ?? 0);
            $imageName = $_POST['old_image'] ?? '';

            if (empty($code) || empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Kode Produk dan Nama Produk wajib diisi!']);
                exit;
            }

            // PROSES UPLOAD GAMBAR BARU KE ASSETS/IMG
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowedExts)) {
                    $newImageName = time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName)) {
                        // Hapus gambar lama jika ada dan bukan placeholder
                        if (!empty($imageName) && !in_array($imageName, ['no-image.png', 'no-image.svg']) && file_exists($uploadDir . $imageName)) {
                            @unlink($uploadDir . $imageName);
                        }
                        $imageName = $newImageName;
                    }
                }
            }

            if (empty($id)) {
                // Cek duplikasi kode
                $cek = $pdo->prepare("SELECT id FROM products WHERE code = ?");
                $cek->execute([$code]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kode Produk sudah digunakan!']);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO products (code, name, category, modal_price, price, online_price, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $name, $category, $modal_price, $price, $online_price, $imageName]);
                echo json_encode(['status' => 'success', 'message' => 'Produk baru berhasil ditambahkan!']);
            } else {
                // Cek duplikasi kode selain id saat ini
                $cek = $pdo->prepare("SELECT id FROM products WHERE code = ? AND id != ?");
                $cek->execute([$code, $id]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Kode Produk sudah digunakan produk lain!']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE products SET code = ?, name = ?, category = ?, modal_price = ?, price = ?, online_price = ?, image = ? WHERE id = ?");
                $stmt->execute([$code, $name, $category, $modal_price, $price, $online_price, $imageName, $id]);
                echo json_encode(['status' => 'success', 'message' => 'Data produk berhasil diperbarui!']);
            }
            break;

        case 'delete':
            header('Content-Type: application/json; charset=utf-8');
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID produk tidak valid!']);
                exit;
            }

            // Ambil gambar sebelum hapus
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetchColumn();

            $pdo->beginTransaction();
            // Bersihkan data relasi stok gudang jika ada
            try { $pdo->prepare("DELETE FROM product_warehouse_stocks WHERE product_id = ?")->execute([$id]); } catch (Exception $e) {}
            try { $pdo->prepare("DELETE FROM production_details WHERE product_id = ?")->execute([$id]); } catch (Exception $e) {}

            $stmtDel = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmtDel->execute([$id]);
            $pdo->commit();

            if (!empty($img) && !in_array($img, ['no-image.png', 'no-image.svg']) && file_exists($uploadDir . $img)) {
                @unlink($uploadDir . $img);
            }

            echo json_encode(['status' => 'success', 'message' => 'Produk berhasil dihapus!']);
            break;

        case 'read_custom_pos':
            header('Content-Type: application/json; charset=utf-8');
            $stmt = $pdo->query("SELECT id, name, price, created_at FROM saved_custom_items_pos ORDER BY created_at DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'delete_custom_pos':
            header('Content-Type: application/json; charset=utf-8');
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID tidak valid!']);
                exit;
            }

            $pdo->beginTransaction();
            try { $pdo->prepare("DELETE FROM bom_custom WHERE custom_item_id = ?")->execute([$id]); } catch (Exception $e) {}
            $pdo->prepare("DELETE FROM saved_custom_items_pos WHERE id = ?")->execute([$id]);
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => 'Item custom POS berhasil dihapus!']);
            break;

        default:
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Action tidak valid!']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
