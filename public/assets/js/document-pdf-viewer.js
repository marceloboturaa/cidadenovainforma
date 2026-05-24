(function () {
    const workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';

    function initViewer(viewer) {
        if (!window.pdfjsLib) {
            showError(viewer);
            return;
        }

        window.pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

        const url = viewer.dataset.pdfUrl;
        const canvas = viewer.querySelector('[data-pdf-canvas]');
        const status = viewer.querySelector('[data-pdf-status]');
        const prev = viewer.querySelector('[data-pdf-prev]');
        const next = viewer.querySelector('[data-pdf-next]');
        const zoomIn = viewer.querySelector('[data-pdf-zoom-in]');
        const zoomOut = viewer.querySelector('[data-pdf-zoom-out]');
        const context = canvas.getContext('2d');

        let pdf = null;
        let pageNumber = Math.max(1, parseInt(viewer.dataset.pdfStartPage || '1', 10) || 1);
        let zoom = 1;
        let rendering = false;
        let pending = false;

        function updateStatus() {
            if (!pdf) {
                status.textContent = 'Carregando PDF...';
                return;
            }

            status.textContent = 'Pagina ' + pageNumber + ' de ' + pdf.numPages + ' - ' + Math.round(zoom * 100) + '%';
            prev.disabled = pageNumber <= 1;
            next.disabled = pageNumber >= pdf.numPages;
            zoomOut.disabled = zoom <= 0.6;
            zoomIn.disabled = zoom >= 2;
        }

        function renderPage() {
            if (!pdf || rendering) {
                pending = true;
                return;
            }

            rendering = true;
            updateStatus();

            pdf.getPage(pageNumber).then(function (page) {
                const shell = viewer.querySelector('.document-pdf-canvas-shell');
                const baseViewport = page.getViewport({ scale: 1 });
                const availableWidth = Math.max(280, shell.clientWidth - 24);
                const baseScale = Math.min(1.8, availableWidth / baseViewport.width);
                const viewport = page.getViewport({ scale: baseScale * zoom });
                const outputScale = window.devicePixelRatio || 1;

                canvas.width = Math.floor(viewport.width * outputScale);
                canvas.height = Math.floor(viewport.height * outputScale);
                canvas.style.width = Math.floor(viewport.width) + 'px';
                canvas.style.height = Math.floor(viewport.height) + 'px';

                return page.render({
                    canvasContext: context,
                    viewport: viewport,
                    transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
                }).promise;
            }).then(function () {
                rendering = false;
                if (pending) {
                    pending = false;
                    renderPage();
                }
            }).catch(function () {
                rendering = false;
                showError(viewer);
            });
        }

        prev.addEventListener('click', function () {
            if (pageNumber > 1) {
                pageNumber -= 1;
                renderPage();
            }
        });

        next.addEventListener('click', function () {
            if (pdf && pageNumber < pdf.numPages) {
                pageNumber += 1;
                renderPage();
            }
        });

        zoomOut.addEventListener('click', function () {
            zoom = Math.max(0.6, zoom - 0.2);
            renderPage();
        });

        zoomIn.addEventListener('click', function () {
            zoom = Math.min(2, zoom + 0.2);
            renderPage();
        });

        window.addEventListener('resize', function () {
            if (pdf) {
                renderPage();
            }
        });

        window.pdfjsLib.getDocument({ url: url, withCredentials: true }).promise.then(function (loadedPdf) {
            pdf = loadedPdf;
            pageNumber = Math.min(pageNumber, pdf.numPages);
            renderPage();
        }).catch(function () {
            showError(viewer);
        });
    }

    function showError(viewer) {
        const error = viewer.querySelector('[data-pdf-error]');
        const status = viewer.querySelector('[data-pdf-status]');

        if (status) {
            status.textContent = 'PDF indisponivel';
        }

        if (error) {
            error.hidden = false;
        }
    }

    function boot() {
        document.querySelectorAll('[data-pdf-viewer]').forEach(initViewer);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
