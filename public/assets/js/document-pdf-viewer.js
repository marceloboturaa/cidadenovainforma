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
        const pages = viewer.querySelector('[data-pdf-pages]');
        const status = viewer.querySelector('[data-pdf-status]');
        const prev = viewer.querySelector('[data-pdf-prev]');
        const next = viewer.querySelector('[data-pdf-next]');
        const zoomIn = viewer.querySelector('[data-pdf-zoom-in]');
        const zoomOut = viewer.querySelector('[data-pdf-zoom-out]');
        const fit = viewer.querySelector('[data-pdf-fit]');
        const pageModeButton = viewer.querySelector('[data-pdf-mode-page]');
        const scrollModeButton = viewer.querySelector('[data-pdf-mode-scroll]');
        const shell = viewer.querySelector('.document-pdf-canvas-shell');
        const context = canvas.getContext('2d');

        let pdf = null;
        let pageNumber = Math.max(1, parseInt(viewer.dataset.pdfStartPage || '1', 10) || 1);
        let zoom = 1;
        let mode = 'page';
        let rendering = false;
        let pending = false;
        let renderToken = 0;

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
            if (pageModeButton) {
                pageModeButton.classList.toggle('is-active', mode === 'page');
            }
            if (scrollModeButton) {
                scrollModeButton.classList.toggle('is-active', mode === 'scroll');
            }
        }

        function renderPage() {
            if (mode === 'scroll') {
                renderAllPages();
                return;
            }

            if (!pdf || rendering) {
                pending = true;
                return;
            }

            rendering = true;
            renderToken += 1;
            updateStatus();
            canvas.hidden = false;
            pages.hidden = true;
            pages.innerHTML = '';

            pdf.getPage(pageNumber).then(function (page) {
                return renderPdfPage(page, canvas, context);
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

        function renderAllPages() {
            if (!pdf || rendering) {
                pending = true;
                return;
            }

            rendering = true;
            renderToken += 1;
            const token = renderToken;
            updateStatus();
            canvas.hidden = true;
            pages.hidden = false;
            pages.innerHTML = '';

            const renderNext = function (number) {
                if (token !== renderToken || number > pdf.numPages) {
                    rendering = false;
                    if (pending) {
                        pending = false;
                        renderAllPages();
                    }
                    return Promise.resolve();
                }

                return pdf.getPage(number).then(function (page) {
                    const pageCanvas = document.createElement('canvas');
                    pageCanvas.dataset.pageNumber = String(number);
                    pages.appendChild(pageCanvas);
                    return renderPdfPage(page, pageCanvas, pageCanvas.getContext('2d'));
                }).then(function () {
                    return renderNext(number + 1);
                });
            };

            renderNext(1).then(function () {
                rendering = false;
                updateCurrentPageFromScroll();
            }).catch(function () {
                rendering = false;
                showError(viewer);
            });
        }

        function renderPdfPage(page, targetCanvas, targetContext) {
            const baseViewport = page.getViewport({ scale: 1 });
            const availableWidth = Math.max(280, shell.clientWidth - 28);
            const baseScale = Math.min(1.8, availableWidth / baseViewport.width);
            const viewport = page.getViewport({ scale: baseScale * zoom });
            const outputScale = window.devicePixelRatio || 1;

            targetCanvas.width = Math.floor(viewport.width * outputScale);
            targetCanvas.height = Math.floor(viewport.height * outputScale);
            targetCanvas.style.width = Math.floor(viewport.width) + 'px';
            targetCanvas.style.height = Math.floor(viewport.height) + 'px';

            return page.render({
                canvasContext: targetContext,
                viewport: viewport,
                transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
            }).promise;
        }

        function updateCurrentPageFromScroll() {
            if (mode !== 'scroll' || !pdf) {
                return;
            }

            const canvases = Array.from(pages.querySelectorAll('canvas'));
            const shellTop = shell.getBoundingClientRect().top;
            let closest = pageNumber;
            let closestDistance = Number.POSITIVE_INFINITY;

            canvases.forEach(function (pageCanvas) {
                const distance = Math.abs(pageCanvas.getBoundingClientRect().top - shellTop - 12);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closest = parseInt(pageCanvas.dataset.pageNumber || '1', 10) || 1;
                }
            });

            pageNumber = closest;
            updateStatus();
        }

        function setMode(nextMode) {
            if (mode === nextMode) {
                return;
            }

            mode = nextMode;
            pending = false;
            rendering = false;
            renderToken += 1;
            renderPage();
        }

        prev.addEventListener('click', function () {
            if (pageNumber > 1) {
                pageNumber -= 1;
                if (mode === 'scroll') {
                    scrollToPage(pageNumber);
                } else {
                    renderPage();
                }
            }
        });

        next.addEventListener('click', function () {
            if (pdf && pageNumber < pdf.numPages) {
                pageNumber += 1;
                if (mode === 'scroll') {
                    scrollToPage(pageNumber);
                } else {
                    renderPage();
                }
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

        fit.addEventListener('click', function () {
            zoom = 1;
            renderPage();
        });

        pageModeButton.addEventListener('click', function () {
            setMode('page');
        });

        scrollModeButton.addEventListener('click', function () {
            setMode('scroll');
        });

        shell.addEventListener('scroll', updateCurrentPageFromScroll);

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

        function scrollToPage(number) {
            const target = pages.querySelector('[data-page-number="' + number + '"]');
            if (target) {
                target.scrollIntoView({ block: 'start', behavior: 'smooth' });
                updateStatus();
            }
        }
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
