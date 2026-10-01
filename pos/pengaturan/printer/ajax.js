// views/pengaturan_printer/ajax.js

// Daftar UUID BLE GATT Service Thermal Printer (Termasuk iware C58MPC, MPT-II, RPP02, POS-58, OEM ESC/POS)
const KNOWN_BLE_SERVICES = [
    '000018f0-0000-1000-8000-00805f9b34fb', // Standard Chinese Thermal Printer
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // iware / Pos-58 / Rongta
    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC Transparent UART
    '0000e0ff-0000-1000-8000-00805f9b34fb', // ESC/POS BLE
    '0000ff00-0000-1000-8000-00805f9b34fb', // Feasycom / BLE Serial
    '0000fff0-0000-1000-8000-00805f9b34fb', // Common BLE UART
    '0000ae30-0000-1000-8000-00805f9b34fb', // Zhuhai / iware variant
    '0000fee7-0000-1000-8000-00805f9b34fb'  // Tencent / OEM BLE
];

async function findBlePrinterCharacteristic(device) {
    if (!device.gatt.connected) {
        await device.gatt.connect();
    }
    const server = device.gatt;

    for (const uuid of KNOWN_BLE_SERVICES) {
        try {
            const service = await server.getPrimaryService(uuid);
            if (service) {
                const chars = await service.getCharacteristics();
                for (const c of chars) {
                    if (c.properties.write || c.properties.writeWithoutResponse) {
                        return c;
                    }
                }
            }
        } catch (e) {}
    }

    // Fallback: periksa semua service yang di-expose perangkat
    try {
        const services = await server.getPrimaryServices();
        for (const s of services) {
            try {
                const chars = await s.getCharacteristics();
                for (const c of chars) {
                    if (c.properties.write || c.properties.writeWithoutResponse) {
                        return c;
                    }
                }
            } catch (e) {}
        }
    } catch (e) {}

    throw new Error('Tidak ditemukan port/karakteristik cetak ESC/POS pada printer ini.');
}

async function sendChunkedData(characteristic, dataBuffer) {
    const CHUNK_SIZE = 64;
    for (let i = 0; i < dataBuffer.length; i += CHUNK_SIZE) {
        const chunk = dataBuffer.slice(i, i + CHUNK_SIZE);
        if (characteristic.writeValueWithoutResponse) {
            await characteristic.writeValueWithoutResponse(chunk);
        } else {
            await characteristic.writeValue(chunk);
        }
        await new Promise(r => setTimeout(r, 25));
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('printerApp', () => ({
        printerName: 'Belum ada printer terhubung',
        isConnected: false,
        connectionType: 'none', // 'bluetooth' | 'usb' | 'none'
        autoPrintMode: 'manual',
        isTesting: false,

        init() {
            const savedBt = localStorage.getItem('pos_printer_name');
            const savedUsb = localStorage.getItem('pos_usb_printer_name');
            const btActive = localStorage.getItem('pos_bt_active') === '1';

            if (savedBt && btActive) {
                this.printerName = savedBt + ' (Bluetooth)';
                this.isConnected = true;
                this.connectionType = 'bluetooth';
            } else if (savedUsb) {
                this.printerName = savedUsb + ' (USB)';
                this.isConnected = true;
                this.connectionType = 'usb';
            }

            const savedMode = localStorage.getItem('pos_auto_print_mode');
            if (savedMode) this.autoPrintMode = savedMode;
        },

        saveAutoPrint() {
            localStorage.setItem('pos_auto_print_mode', this.autoPrintMode);
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: 'Pengaturan Disimpan!',
                showConfirmButton: false, timer: 1500
            });
        },

        async hubungkanPrinter() {
            if (!navigator.bluetooth) {
                Swal.fire('Tidak Mendukung', 'Browser ini tidak mendukung Web Bluetooth. Gunakan Google Chrome versi terbaru di PC/Laptop atau Android.', 'error');
                return;
            }

            try {
                // Mendukung iware C58MPC V2 & semua printer Bluetooth Thermal ESC/POS
                const device = await navigator.bluetooth.requestDevice({
                    acceptAllDevices: true,
                    optionalServices: KNOWN_BLE_SERVICES
                });

                const characteristic = await findBlePrinterCharacteristic(device);

                const pName = device.name || 'iware C58MPC';
                this.printerName = pName + ' (Bluetooth)';
                this.isConnected = true;
                this.connectionType = 'bluetooth';
                this.autoPrintMode = 'bluetooth';

                localStorage.setItem('pos_printer_name', pName);
                localStorage.setItem('pos_bt_active', '1');
                localStorage.setItem('pos_auto_print_mode', 'bluetooth');

                Swal.fire({
                    icon: 'success',
                    title: 'Printer Bluetooth Terhubung!',
                    html: `<b>${pName}</b> berhasil dihubungkan dan siap mencetak struk kasir.`,
                    confirmButtonText: 'Selesai',
                    confirmButtonColor: '#2563eb'
                });

            } catch (error) {
                console.error("Batal/Gagal Bluetooth:", error);
                if (error.name !== 'NotFoundError') {
                    Swal.fire('Koneksi Gagal', 'Gagal menyambung ke printer Bluetooth: ' + error.message, 'error');
                }
            }
        },

        async hubungkanPrinterUSB() {
            if (!navigator.usb) {
                Swal.fire('Tidak Mendukung', 'Browser ini tidak mendukung WebUSB. Silakan langsung pasang kabel USB dan cetak secara normal.', 'info');
                return;
            }

            try {
                const device = await navigator.usb.requestDevice({ filters: [] });
                const usbName = device.productName || device.manufacturerName || 'Thermal Printer USB';
                this.printerName = usbName + ' (USB)';
                this.isConnected = true;
                this.connectionType = 'usb';
                this.autoPrintMode = 'usb';

                localStorage.setItem('pos_usb_printer_name', usbName);
                localStorage.setItem('pos_auto_print_mode', 'usb');
                localStorage.removeItem('pos_bt_active');

                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: `🖨️ USB ${usbName} Terhubung!`,
                    text: 'Printer Thermal USB langsung terbaca dan otomatis aktif.',
                    showConfirmButton: false, timer: 3500
                });
            } catch(e) {
                console.error(e);
            }
        },

        async testPrint() {
            this.isTesting = true;
            try {
                const encoder = new TextEncoder();
                let testText = "\x1B\x40"; // ESC @ (Init)
                testText += "\x1B\x61\x01\x1B\x45\x01TES PRINTER KASIR\x1B\x45\x00\n";
                testText += "iware C58MPC / Thermal 58mm\n";
                testText += "--------------------------------\n";
                testText += "\x1B\x61\x00Status : Terhubung Berhasil\n";
                testText += "Waktu  : " + new Date().toLocaleString('id-ID') + "\n";
                testText += "--------------------------------\n";
                testText += "\x1B\x61\x01Siap Mencetak Struk Kasir!\n\n\n\n";
                testText += "\x1D\x56\x42\x00";

                const data = encoder.encode(testText);

                if (this.connectionType === 'bluetooth') {
                    if (!navigator.bluetooth) throw new Error('Bluetooth tidak tersedia.');
                    const savedPrinter = localStorage.getItem('pos_printer_name');
                    let device;
                    if (navigator.bluetooth.getDevices) {
                        const devices = await navigator.bluetooth.getDevices();
                        device = devices.find(d => d.name === savedPrinter);
                    }
                    if (!device) {
                        device = await navigator.bluetooth.requestDevice({
                            acceptAllDevices: true,
                            optionalServices: KNOWN_BLE_SERVICES
                        });
                    }
                    const char = await findBlePrinterCharacteristic(device);
                    await sendChunkedData(char, data);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Test cetak Bluetooth terkirim!', showConfirmButton: false, timer: 2000 });
                } else if (this.connectionType === 'usb') {
                    if (!navigator.usb) throw new Error('WebUSB tidak tersedia.');
                    let device;
                    const devices = await navigator.usb.getDevices();
                    if (devices && devices.length > 0) device = devices[0];
                    if (!device) device = await navigator.usb.requestDevice({ filters: [] });
                    await device.open();
                    if (device.configuration === null) await device.selectConfiguration(1);
                    let ifaceNum = 0, epNum = 1;
                    for (let c of device.configurations) {
                        for (let iface of c.interfaces) {
                            for (let alt of iface.alternates) {
                                for (let ep of alt.endpoints) {
                                    if (ep.direction === 'out') {
                                        ifaceNum = iface.interfaceNumber;
                                        epNum = ep.endpointNumber;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    await device.claimInterface(ifaceNum);
                    await device.transferOut(epNum, data);
                    await device.close();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Test cetak USB terkirim!', showConfirmButton: false, timer: 2000 });
                } else {
                    window.print();
                }
            } catch(e) {
                console.error("Test print error:", e);
                Swal.fire('Gagal Test Cetak', e.message || 'Periksa apakah printer menyala.', 'error');
            } finally {
                this.isTesting = false;
            }
        },

        hapusPrinter() {
            localStorage.removeItem('pos_printer_name');
            localStorage.removeItem('pos_usb_printer_name');
            localStorage.removeItem('pos_bt_active');
            localStorage.setItem('pos_auto_print_mode', 'manual');
            this.printerName = 'Belum ada printer terhubung';
            this.isConnected = false;
            this.connectionType = 'none';
            this.autoPrintMode = 'manual';

            Swal.fire({ 
                toast: true, position: 'top-end', icon: 'warning', 
                title: 'Printer dihapus dari memori', 
                showConfirmButton: false, timer: 1500 
            });
        }
    }));
});