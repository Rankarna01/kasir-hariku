<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/../config/env.php';

if (!defined('BASE_URL')) { 
    $is_localhost = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $base_sub = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/pos/') !== false ? trim(explode('/pos/', $_SERVER['SCRIPT_NAME'])[0], '/') : 'kasir-hariku';
    $folder = $is_localhost ? ($base_sub !== '' ? '/' . $base_sub . '/' : '/') : '/';
    define('BASE_URL', $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $folder); 
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php
$raw_title = $page_title ?? 'Ayam Goreng Hariku - POS Kasir';
$display_title = str_ireplace('Love Cakes', 'Ayam Goreng Hariku', $raw_title);
?>
<title><?= htmlspecialchars($display_title) ?></title>

<link rel="manifest" href="<?= BASE_URL ?>manifest.json">

<meta name="theme-color" content="#FF3870">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/img/logo-hariku.png">
<link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/logo-hariku.png">

<!-- Web Fonts: Avenir / Avenir Next with high-quality fallback -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.cdnfonts.com/css/avenir" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/avenir-next-lt-pro" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: { preflight: true },
        theme: {
            extend: {
                fontFamily: { 
                    sans: ['Avenir', 'Avenir Next', 'Plus Jakarta Sans', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'] 
                },
                fontWeight: {
                    light: '400',
                    normal: '500',
                    medium: '500',
                    semibold: '600',
                    bold: '600',
                    extrabold: '700',
                    black: '700'
                },
                colors: {
                    surface: '#FFFFFF',
                    background: '#FFFFFF',
                    primary: {
                        DEFAULT: '#FF3870', // Ayam Goreng Hariku Pink
                        50: '#FFF0F5',
                        100: '#FFE2EC',
                        200: '#FFC5D8',
                        300: '#FF97B6',
                        400: '#FF6492',
                        500: '#FF3870',
                        600: '#E62058',
                        700: '#C01344',
                        800: '#991138',
                        900: '#7F1332'
                    },
                    chocolate: {
                        DEFAULT: '#5C2D16', // Ayam Goreng Hariku Coklat
                        50: '#FAF5F1',
                        100: '#F4ECE4',
                        200: '#E7D5C4',
                        300: '#D5B79F',
                        400: '#B6886A',
                        500: '#8C5638',
                        600: '#6E3E24',
                        700: '#5C2D16',
                        800: '#4A2311',
                        900: '#33170B'
                    },
                    // Map blue to Hariku Pink & Coklat to neutralize remaining blue classes
                    blue: {
                        50: '#FFF0F5',
                        100: '#FFE2EC',
                        200: '#FFC5D8',
                        300: '#FF97B6',
                        400: '#FF6492',
                        500: '#FF3870',
                        600: '#FF3870',
                        700: '#E02360',
                        800: '#5C2D16',
                        900: '#4A2311',
                    },
                    hariku: {
                        pink: '#FF3870',
                        pinkDark: '#E02360',
                        pinkLight: '#FFF0F5',
                        chocolate: '#5C2D16',
                        chocolateDark: '#4A2311',
                        chocolateLight: '#FDF6F2',
                        gold: '#F59E0B'
                    },
                    secondary: '#64748B',
                    accent: '#F59E0B',
                    danger: '#EF4444',
                    success: '#10B981'
                }
            }
        }
    }
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/fontawesome-free-6.4.2-web/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fontawesome.css">

<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>sweetalert2.all.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/localforage.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/pos_db.js"></script>

<style>
    /* ===== GLOBAL FONT: AVENIR (STANDARD 500) ===== */
    html, body, button, input, select, textarea, div, p, span, h1, h2, h3, h4, h5, h6, a, table, td, th, label, li {
        font-family: 'Avenir', 'Avenir Next', 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 500;
    }
    strong, b, .font-bold, .font-black, .font-extrabold {
        font-weight: 600 !important;
    }

    /* ===== CRITICAL: PROTECT FONT AWESOME ICONS DARI OVERRIDE FONT ===== */
    .fa, .fa-solid, .fa-regular, .fa-brands, .fas, .far, .fab, 
    i[class*="fa-"], span[class*="fa-"], [class^="fa-"], [class*=" fa-"] {
        font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands" !important;
        font-style: normal;
        font-weight: 900 !important;
        display: inline-block;
        text-rendering: auto;
        -webkit-font-smoothing: antialiased;
    }
    .fa-brands, .fab {
        font-family: "Font Awesome 6 Brands" !important;
        font-weight: 400 !important;
    }
    .fa-regular, .far {
        font-family: "Font Awesome 6 Free" !important;
        font-weight: 400 !important;
    }
    body { 
        background-color: #FFFFFF !important; 
        color: #4A2311;
    }
    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #E7D5C4; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #FF97B6; }
    
    /* INI KUNCI ANTI KEDAP-KEDIP */
    [x-cloak] { display: none !important; }
    
    /* SweetAlert standard font & always on top of all modals/drawers */
    div:where(.swal2-container),
    .swal2-container { 
        font-family: 'Avenir', 'Avenir Next', 'Plus Jakarta Sans', sans-serif !important; 
        z-index: 100000 !important; 
    }

    /* ===== DESKTOP HAMBURGER BUTTON IN TOPBAR ===== */
    header.bg-primary button[onclick*="toggleSidebar"] {
        display: inline-flex !important;
    }

    /* ===== COLLAPSIBLE SIDEBAR RULES ===== */
    #main-sidebar {
        transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease;
    }
    #main-sidebar.sidebar-collapsed {
        width: 72px !important;
        min-width: 72px !important;
        max-width: 72px !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-text,
    #main-sidebar.sidebar-collapsed .sidebar-chevron,
    #main-sidebar.sidebar-collapsed .sidebar-section-title,
    #main-sidebar.sidebar-collapsed .outlet-filter-container,
    #main-sidebar.sidebar-collapsed .sidebar-user-info {
        display: none !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-collapsed-outlet {
        display: flex !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-collapsed-divider {
        display: block !important;
    }
    #main-sidebar.sidebar-collapsed .nav-item {
        justify-content: center !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        width: 44px !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    #main-sidebar.sidebar-collapsed .nav-item i {
        margin: 0 !important;
        font-size: 1.15rem !important;
    }
    #main-sidebar.sidebar-collapsed .submenu-container {
        display: none !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-header-box {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
        justify-content: center !important;
        gap: 0 !important;
    }
    #main-sidebar.sidebar-collapsed .sidebar-brand-link {
        display: none !important;
    }

    /* ===== HARIKU BRAND HEADER / TOPBAR (PERMANENT WHITE BG) ===== */
    header.bg-primary, header.bg-primary:hover {
        background: #FFFFFF !important;
        background-color: #FFFFFF !important;
        color: #5C2D16 !important;
        border-bottom: 2px solid #FFE4EC !important;
        box-shadow: 0 4px 18px -4px rgba(255, 56, 112, 0.08) !important;
    }
    header.bg-primary h1, 
    header.bg-primary h2, 
    header.bg-primary h3 {
        color: #5C2D16 !important;
        font-weight: 600 !important;
        letter-spacing: -0.01em;
    }
    header.bg-primary p {
        color: #8C5638 !important;
    }
    header.bg-primary button.text-white,
    header.bg-primary a.text-white {
        color: #5C2D16 !important;
    }
    header.bg-primary button.text-white:hover,
    header.bg-primary a.text-white:hover {
        background-color: #FFF0F5 !important;
        color: #FF3870 !important;
    }
    header.bg-primary .text-blue-200, 
    header.bg-primary .text-blue-100 {
        color: #8C5638 !important;
    }
    header.bg-primary .hover\:text-blue-200:hover {
        color: #FF3870 !important;
    }
    header.bg-primary .hover\:bg-blue-600:hover {
        background-color: #FFF0F5 !important;
        color: #FF3870 !important;
    }
    header.bg-primary .border-blue-400 {
        border-color: #FFE4EC !important;
    }
    header.bg-primary .bg-black\/20 {
        background: #FFF0F5 !important;
        color: #5C2D16 !important;
        border: 1px solid #FFC5D8 !important;
        box-shadow: none !important;
    }
    header.bg-primary .bg-black\/20 i {
        color: #FF3870 !important;
    }

    /* ===== HOVER RULES: CLEAN PINK & SOFT TONES (NO DARK BROWN ON HOVER) ===== */
    /* 1. Teks dan Link saat di-hover: menjadi Pink */
    .text-\[\#5C2D16\], .text-\[\#4A2311\], .text-\[\#8C5638\], .text-chocolate {
        transition: color 0.15s ease, background-color 0.15s ease;
    }
    a.text-\[\#5C2D16\]:hover,
    a.text-\[\#4A2311\]:hover,
    a.text-\[\#8C5638\]:hover,
    a.text-chocolate:hover,
    button:hover > .text-\[\#5C2D16\],
    a:hover > .text-\[\#5C2D16\],
    .hover\:text-\[\#FF3870\]:hover {
        color: #FF3870 !important;
    }

    /* 2. Tombol Pink saat di-hover: menjadi Dark Pink (#E02360), BUKAN coklat */
    .bg-\[\#FF3870\], .bg-primary, .bg-pink-600, .bg-pink-500 {
        transition: background-color 0.15s ease, color 0.15s ease, transform 0.1s ease;
    }
    button.bg-\[\#FF3870\]:hover,
    button.bg-primary:hover,
    button.bg-pink-600:hover,
    a.bg-\[\#FF3870\]:hover,
    a.bg-primary:hover,
    .hover\:bg-\[\#E02360\]:hover,
    .hover\:bg-pink-700:hover {
        background-color: #E02360 !important;
        color: #FFFFFF !important;
    }

    /* 3. Menghilangkan semua warna biru dan sisa hover biru di seluruh halaman */
    .bg-blue-600, .bg-blue-700, .bg-blue-500, .bg-blue-800 {
        background-color: #FF3870 !important;
        color: #FFFFFF !important;
    }
    .bg-blue-50 {
        background-color: #FFF0F5 !important;
    }
    .bg-blue-100 {
        background-color: #FFE2EC !important;
    }
    .text-blue-600, .text-blue-700, .text-blue-500, .text-blue-400, .text-blue-800 {
        color: #FF3870 !important;
    }
    .border-blue-100, .border-blue-200, .border-blue-300, .border-blue-400 {
        border-color: #FFC5D8 !important;
    }
    .border-blue-500, .border-blue-600 {
        border-color: #FF3870 !important;
    }
    .hover\:bg-blue-600:hover, .hover\:bg-blue-700:hover, .hover\:bg-blue-800:hover, .hover\:bg-blue-500:hover {
        background-color: #E02360 !important;
        color: #FFFFFF !important;
    }
    .hover\:text-blue-600:hover, .hover\:text-blue-700:hover, .hover\:text-blue-800:hover, .hover\:text-blue-500:hover {
        color: #FF3870 !important;
    }
    .hover\:bg-blue-50:hover {
        background-color: #FFF0F5 !important;
        color: #FF3870 !important;
    }
    .hover\:bg-blue-100:hover {
        background-color: #FFC5D8 !important;
    }
    .hover\:border-blue-200:hover, .hover\:border-blue-300:hover {
        border-color: #FF3870 !important;
    }
</style>

<script>
    // 🛠️ PERBAIKAN 3: Registrasi Service Worker dihilangkan kata "pos/"-nya
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= BASE_URL ?>sw.js')
                .then(registration => {
                    console.log('ServiceWorker sukses didaftarkan dengan scope: ', registration.scope);
                })
                .catch(err => {
                    console.log('ServiceWorker gagal didaftarkan: ', err);
                });
        });
    }

    // 🛠️ PERBAIKAN: Paksa bersihkan Cache lama dari memori Safari/Chrome
    if ('caches' in window) {
        caches.keys().then(function(names) {
            for (let name of names) {
                if (name !== 'lovecakes-pos-v8') {
                    caches.delete(name);
                }
            }
        });
    }

    // --- LOGIKA ALERT CUSTOM BAWAANMU (TIDAK ADA YANG DIHAPUS) ---
    window.alert = function(message) {
        let type = 'info';
        let msgStr = String(message).toLowerCase();
        
        if(msgStr.includes('berhasil') || msgStr.includes('success') || msgStr.includes('dicatat') || msgStr.includes('disimpan')) type = 'success';
        if(msgStr.includes('gagal') || msgStr.includes('error') || msgStr.includes('maaf')) type = 'error';
        if(msgStr.includes('pilih') || msgStr.includes('wajib') || msgStr.includes('harap')) type = 'warning';

        if (type === 'success') {
            if(typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true,
                    customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                });
                Toast.fire({ icon: 'success', title: message });
            }
        } else {
            if(typeof Swal !== 'undefined') {
                Swal.fire({
                    title: type === 'error' ? 'Oops! Ada Masalah' : (type === 'warning' ? 'Perhatian' : 'Informasi'),
                    html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
                    icon: type,
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: type === 'error' ? '#EF4444' : (type === 'warning' ? '#F59E0B' : '#FF3870'),
                    customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
                });
            }
        }
    };

    window.customConfirm = function(message, callback) {
        if(typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                html: `<p style="color: #475569; font-weight: 500; font-size: 14px;">${message}</p>`,
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#FF3870', cancelButtonColor: '#94A3B8',  
                confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Ya, Lanjutkan!', cancelButtonText: 'Batal',
                reverseButtons: true, customClass: { popup: 'rounded-3xl shadow-2xl border border-slate-100', title: 'text-xl font-extrabold text-slate-800' }
            }).then((result) => { if (result.isConfirmed) { callback(); } });
        }
    };

    function eksekusiLogout() {
        if (window.dbAuth) {
            window.dbAuth.removeItem('user_session').then(() => { window.location.href = '<?= BASE_URL ?>logout_action.php'; })
            .catch(() => { window.location.href = '<?= BASE_URL ?>logout_action.php'; });
        } else {
            window.location.href = '<?= BASE_URL ?>logout_action.php';
        }
    }

    window.logoutSistem = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Yakin mau Logout?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8', confirmButtonText: 'Ya, Keluar!', cancelButtonText: 'Batal'
            }).then((result) => { if (result.isConfirmed) { eksekusiLogout(); } });
        } else {
            if (confirm('Yakin mau Logout?')) { eksekusiLogout(); }
        }
    };
    window.doLogout = window.logoutSistem;
</script>
