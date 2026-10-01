/**
 * LoveCakes POS - Device Authorization & Lock Guard (DISABLED)
 * Fitur penguncian perangkat / tab dinonaktifkan sepenuhnya sesuai permintaan.
 */

(function () {
    // Pastikan tidak ada overlay penguncian yang muncul atau tersisa
    function removeLockOverlay() {
        const overlay = document.getElementById('device-lock-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeLockOverlay);
    } else {
        removeLockOverlay();
    }

    window.checkDeviceAuthorization = function () {
        removeLockOverlay();
        return Promise.resolve({ status: 'success', is_restricted: false, is_valid: true });
    };
})();

