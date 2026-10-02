<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Hanya role admin, owner, superadmin yang diizinkan mengelola karyawan
$user_role = strtolower(trim($_SESSION['pos_role'] ?? ''));
if (!in_array($user_role, ['admin', 'owner', 'superadmin', 'backoffice'])) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Administrator yang dapat mengelola data karyawan.']);
    exit;
}

// Auto migration: Tambah kolom phone ke users_pos jika belum ada
try {
    $colPhone = $pdo->query("SHOW COLUMNS FROM users_pos LIKE 'phone'")->fetch();
    if (!$colPhone) {
        $pdo->exec("ALTER TABLE users_pos ADD COLUMN phone VARCHAR(20) NULL AFTER warehouse_id");
    }
} catch (Exception $e) {}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // 1. BACA DATA KARYAWAN & STATISTIK SHIFT
        case 'read':
            $wh_filter = "";
            $params = [];

            // Query daftar user/karyawan dengan role, outlet, dan histori shift
            $sql = "
                SELECT 
                    u.id, 
                    u.name, 
                    u.username, 
                    u.role_id, 
                    u.warehouse_id, 
                    u.phone,
                    u.created_at,
                    COALESCE(r.role_name, 'Kasir') AS role_name,
                    COALESCE(w.name, 'Semua Outlet (Global)') AS store_name,
                    COALESCE(w.code, 'GLOBAL') AS store_code,
                    (SELECT COUNT(*) FROM shifts_history_pos sh WHERE sh.user_id = u.id) AS total_shifts,
                    (SELECT MAX(start_time) FROM shifts_history_pos sh WHERE sh.user_id = u.id) AS last_shift_time,
                    (SELECT status FROM shifts_history_pos sh WHERE sh.user_id = u.id AND sh.status = 'open' ORDER BY id DESC LIMIT 1) AS active_shift_status
                FROM users_pos u
                LEFT JOIN roles_pos r ON u.role_id = r.id
                LEFT JOIN warehouses w ON u.warehouse_id = w.id
                ORDER BY u.role_id ASC, u.name ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Rapikan nama store
            foreach ($data as &$row) {
                $row['store_name'] = str_ireplace('gudang', 'Store', $row['store_name']);
                $row['is_current_login'] = ($row['id'] == ($_SESSION['pos_user_id'] ?? 0));
            }
            unset($row);

            // Ambil daftar roles
            $roles = $pdo->query("SELECT id, role_name FROM roles_pos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($roles)) {
                $roles = [
                    ['id' => 1, 'role_name' => 'Admin'],
                    ['id' => 2, 'role_name' => 'Kasir']
                ];
            }

            // Ambil daftar warehouses/outlets
            $warehouses = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($warehouses as &$wh) {
                $wh['name'] = str_ireplace('gudang', 'Store', $wh['name']);
            }
            unset($wh);

            echo json_encode([
                'status' => 'success',
                'data' => $data,
                'roles' => $roles,
                'warehouses' => $warehouses,
                'current_user_id' => $_SESSION['pos_user_id'] ?? 0
            ]);
            break;

        // 2. SIMPAN / EDIT KARYAWAN
        case 'save':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $username = strtolower(trim($_POST['username'] ?? ''));
            $role_id = intval($_POST['role_id'] ?? 2);
            $warehouse_id = !empty($_POST['warehouse_id']) ? intval($_POST['warehouse_id']) : null;
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($name)) {
                echo json_encode(['status' => 'error', 'message' => 'Nama lengkap karyawan wajib diisi!']);
                exit;
            }

            if (empty($username)) {
                echo json_encode(['status' => 'error', 'message' => 'Username login wajib diisi!']);
                exit;
            }

            // Validasi format username (hanya huruf, angka, underscore, titik)
            if (!preg_match('/^[a-z0-9_.]+$/', $username)) {
                echo json_encode(['status' => 'error', 'message' => 'Username hanya boleh huruf kecil, angka, titik, atau underscore (tanpa spasi)!']);
                exit;
            }

            if ($id <= 0) {
                // TAMBAH KARYAWAN BARU
                if (empty($password)) {
                    echo json_encode(['status' => 'error', 'message' => 'Kata sandi wajib diisi untuk karyawan baru!']);
                    exit;
                }

                if (strlen($password) < 4) {
                    echo json_encode(['status' => 'error', 'message' => 'Kata sandi minimal 4 karakter!']);
                    exit;
                }

                // Cek duplikasi username
                $cek = $pdo->prepare("SELECT id FROM users_pos WHERE LOWER(username) = ?");
                $cek->execute([$username]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Username "' . htmlspecialchars($username) . '" sudah digunakan. Gunakan username lain!']);
                    exit;
                }

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO users_pos (name, username, password, role_id, warehouse_id, phone, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $username, $hash, $role_id, $warehouse_id, $phone]);

                echo json_encode(['status' => 'success', 'message' => 'Akun karyawan ' . htmlspecialchars($name) . ' berhasil dibuat!']);
            } else {
                // EDIT KARYAWAN LAMA
                // Cek duplikasi username kecuali user ini sendiri
                $cek = $pdo->prepare("SELECT id FROM users_pos WHERE LOWER(username) = ? AND id != ?");
                $cek->execute([$username, $id]);
                if ($cek->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Username "' . htmlspecialchars($username) . '" sudah digunakan akun lain!']);
                    exit;
                }

                // Jika password diisi saat edit, perbarui password sekaligus
                if (!empty($password)) {
                    if (strlen($password) < 4) {
                        echo json_encode(['status' => 'error', 'message' => 'Kata sandi baru minimal 4 karakter!']);
                        exit;
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("
                        UPDATE users_pos 
                        SET name = ?, username = ?, password = ?, role_id = ?, warehouse_id = ?, phone = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $username, $hash, $role_id, $warehouse_id, $phone, $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users_pos 
                        SET name = ?, username = ?, role_id = ?, warehouse_id = ?, phone = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $username, $role_id, $warehouse_id, $phone, $id]);
                }

                // Update session jika mengedit akun sendiri
                if ($id == ($_SESSION['pos_user_id'] ?? 0)) {
                    $_SESSION['pos_name'] = $name;
                    $_SESSION['pos_username'] = $username;
                }

                echo json_encode(['status' => 'success', 'message' => 'Data karyawan ' . htmlspecialchars($name) . ' berhasil diperbarui!']);
            }
            break;

        // 3. RESET KATA SANDI (BISA UNTUK ADMIN ATAUPUN KARYAWAN KASIR)
        case 'reset_password':
            $id = intval($_POST['id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID Akun tidak valid!']);
                exit;
            }

            if (empty($new_password)) {
                echo json_encode(['status' => 'error', 'message' => 'Kata sandi baru wajib diisi!']);
                exit;
            }

            if (strlen($new_password) < 4) {
                echo json_encode(['status' => 'error', 'message' => 'Kata sandi minimal 4 karakter!']);
                exit;
            }

            if ($new_password !== $confirm_password) {
                echo json_encode(['status' => 'error', 'message' => 'Konfirmasi kata sandi tidak cocok!']);
                exit;
            }

            // Cek keberadaan user
            $stmtCek = $pdo->prepare("SELECT name, username FROM users_pos WHERE id = ?");
            $stmtCek->execute([$id]);
            $userTarget = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$userTarget) {
                echo json_encode(['status' => 'error', 'message' => 'Akun karyawan tidak ditemukan!']);
                exit;
            }

            $newHash = password_hash($new_password, PASSWORD_BCRYPT);
            $stmtUpdate = $pdo->prepare("UPDATE users_pos SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$newHash, $id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Kata sandi untuk ' . htmlspecialchars($userTarget['name']) . ' (' . htmlspecialchars($userTarget['username']) . ') berhasil direset!'
            ]);
            break;

        // 4. HAPUS AKUN KARYAWAN
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            $currentUserId = intval($_SESSION['pos_user_id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'ID Akun tidak valid!']);
                exit;
            }

            // Cegah menghapus akun sendiri yang sedang aktif login
            if ($id === $currentUserId) {
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan!']);
                exit;
            }

            // Cek user target
            $stmtCek = $pdo->prepare("SELECT name FROM users_pos WHERE id = ?");
            $stmtCek->execute([$id]);
            $userTarget = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$userTarget) {
                echo json_encode(['status' => 'error', 'message' => 'Data karyawan tidak ditemukan!']);
                exit;
            }

            // Hapus user
            $stmtDel = $pdo->prepare("DELETE FROM users_pos WHERE id = ?");
            $stmtDel->execute([$id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Akun karyawan ' . htmlspecialchars($userTarget['name']) . ' berhasil dihapus!'
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali!']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
}
