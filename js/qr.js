/**
 * QR-Code Generierung und Live-Kamera-Scanner
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Statischer QR-Code Generator (z.B. Detailseite)
    const qrCanvas = document.getElementById('qrcodeCanvas');
    if (qrCanvas && typeof qrcode !== 'undefined') {
        const currentUrl = window.location.href;
        const qr = qrcode(0, 'M');
        qr.addData(currentUrl);
        qr.make();
        qrCanvas.innerHTML = qr.createImgTag(5, 10);
    }

    // 2. Dynamischer QR-Code für Lagerorte (Event-Delegation)
    document.addEventListener('click', (e) => {
        const qrBtn = e.target.closest('.location-qr-btn');
        if (!qrBtn) return;

        e.preventDefault();

        const locId = qrBtn.dataset.id;
        const locName = qrBtn.dataset.name;

        const origin = window.location.origin;
        const basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const targetUrl = `${origin}${basePath}index.php?location_id=${locId}`;

        const modalTitle = document.getElementById('locationQrTitle') || document.getElementById('qrModalTitle');
        const modalInfo = document.getElementById('locationQrInfo') || document.getElementById('qrModalInfo');
        
        if (modalTitle) modalTitle.textContent = locName;
        if (modalInfo) modalInfo.textContent = `Lagerort-ID: ${locId}`;

        const qrContainer = document.getElementById('locationQrContainer') || document.getElementById('qrModalCode');
        if (qrContainer) {
            qrContainer.innerHTML = '';

            if (typeof qrcode !== 'undefined') {
                const qr = qrcode(0, 'M');
                qr.addData(targetUrl);
                qr.make();
                qrContainer.innerHTML = qr.createImgTag(5, 10);
            } else if (typeof QRCode !== 'undefined') {
                new QRCode(qrContainer, {
                    text: targetUrl,
                    width: 180,
                    height: 180
                });
            } else {
                qrContainer.textContent = 'QR-Bibliothek nicht geladen.';
            }
        }

        const modalEl = document.getElementById('locationQrModal') || document.getElementById('qrModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    // 3. Kamera-Scanner Steuerung
    let html5QrcodeScanner = null;
    const scannerModal = document.getElementById('scannerModal');

    document.querySelectorAll('[data-bs-target="#scannerModal"], #qrScannerBtn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (scannerModal && typeof bootstrap !== 'undefined') {
                e.preventDefault();
                const modal = bootstrap.Modal.getInstance(scannerModal) || new bootstrap.Modal(scannerModal);
                modal.show();
            }
        });
    });

    if (scannerModal) {
        scannerModal.addEventListener('shown.bs.modal', function () {
            if (typeof Html5QrcodeScanner === 'undefined') {
                alert('Kamera-Bibliothek ist nicht geladen.');
                return;
            }

            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                });
            }

            html5QrcodeScanner.render((decodedText) => {
                if (decodedText.startsWith("http://") || decodedText.startsWith("https://")) {
                    window.location.href = decodedText;
                } else {
                    const resEl = document.getElementById('qr-reader-results');
                    if (resEl) resEl.innerText = "Gescannter Inhalt: " + decodedText;
                }
            }, () => {});
        });

        scannerModal.addEventListener('hidden.bs.modal', function () {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => console.error("Scanner Stop Error:", error));
            }
        });
    }
});
