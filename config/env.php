<?php
/**
 * Simple Environment Loader for Love Cakes POS
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        static $env_vars = null;
        if ($env_vars === null) {
            $env_vars = [];
            $env_path = __DIR__ . '/../.env';
            if (file_exists($env_path)) {
                $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || str_starts_with($line, '#')) continue;
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value);
                        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                            $value = substr($value, 1, -1);
                        }
                        $env_vars[$name] = $value;
                    }
                }
            }
        }
        return array_key_exists($key, $env_vars) ? $env_vars[$key] : $default;
    }
}

// Define BASE_URL from env or dynamic detection.
// Jika BASE_URL dikosongkan, URL akan mengikuti host dan lokasi folder aplikasi.
if (!defined('BASE_URL')) {
    $env_url = trim((string) env('BASE_URL', ''));
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $request_hostname = strtolower((string) (parse_url('http://' . $host, PHP_URL_HOST) ?: $host));
    $env_hostname = strtolower((string) (parse_url($env_url, PHP_URL_HOST) ?: ''));
    $local_hosts = ['localhost', '127.0.0.1', '::1'];
    $is_local_request = in_array($request_hostname, $local_hosts, true);
    $env_points_to_localhost = in_array($env_hostname, $local_hosts, true);

    // Jangan biarkan .env lokal yang tertinggal mengarahkan pengguna production
    // kembali ke komputer mereka sendiri.
    $use_env_url = $env_url !== '' && !($env_points_to_localhost && !$is_local_request);

    if ($use_env_url) {
        define('BASE_URL', rtrim($env_url, '/') . '/');
    } else {
        $forwarded_protocol = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $is_https = $forwarded_protocol === 'https'
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $protocol = $is_https ? 'https://' : 'http://';

        // Turunkan folder aplikasi dari DOCUMENT_ROOT agar bekerja baik saat
        // dipasang di root domain maupun di subfolder seperti /pos-lovecakes/.
        $app_root = realpath(dirname(__DIR__));
        $document_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
        $folder = '/';

        if ($app_root && $document_root) {
            $normalized_app_root = str_replace('\\', '/', $app_root);
            $normalized_document_root = rtrim(str_replace('\\', '/', $document_root), '/');
            $document_prefix = $normalized_document_root . '/';

            if ($normalized_app_root === $normalized_document_root) {
                $folder = '/';
            } elseif (str_starts_with($normalized_app_root . '/', $document_prefix)) {
                $relative_path = substr($normalized_app_root, strlen($normalized_document_root));
                $folder = '/' . trim($relative_path, '/') . '/';
            } else {
                $folder = '/' . trim(basename($normalized_app_root), '/') . '/';
            }
        } elseif ($is_local_request) {
            $folder = '/' . trim(basename(dirname(__DIR__)), '/') . '/';
        }

        define('BASE_URL', $protocol . $host . $folder);
    }
}
