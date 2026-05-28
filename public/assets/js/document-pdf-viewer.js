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
        const pageFrame = viewer.querySelector('[data-pdf-page-frame]');
        const singleLayer = viewer.querySelector('[data-pdf-annotation-layer]');
        const pages = viewer.querySelector('[data-pdf-pages]');
        const status = viewer.querySelector('[data-pdf-status]');
        const prev = viewer.querySelector('[data-pdf-prev]');
        const next = viewer.querySelector('[data-pdf-next]');
        const zoomIn = viewer.querySelector('[data-pdf-zoom-in]');
        const zoomOut = viewer.querySelector('[data-pdf-zoom-out]');
        const fit = viewer.querySelector('[data-pdf-fit]');
        const pageModeButton = viewer.querySelector('[data-pdf-mode-page]');
        const scrollModeButton = viewer.querySelector('[data-pdf-mode-scroll]');
        const colorInput = viewer.querySelector('[data-pdf-annotation-color]');
        const noteInput = viewer.querySelector('[data-pdf-annotation-note]');
        const hint = viewer.querySelector('[data-pdf-annotation-hint]');
        const shell = viewer.querySelector('.document-pdf-canvas-shell');
        const context = canvas.getContext('2d');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        let pdf = null;
        let annotations = [];
        let pageNumber = Math.max(1, parseInt(viewer.dataset.pdfStartPage || '1', 10) || 1);
        let zoom = 1;
        let mode = 'page';
        let activeTool = 'select';
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
            pageModeButton?.classList.toggle('is-active', mode === 'page');
            scrollModeButton?.classList.toggle('is-active', mode === 'scroll');
            viewer.querySelectorAll('[data-pdf-tool]').forEach(function (button) {
                button.classList.toggle('is-active', button.dataset.pdfTool === activeTool);
            });
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
            pageFrame.hidden = false;
            pages.hidden = true;
            pages.innerHTML = '';

            pdf.getPage(pageNumber).then(function (page) {
                return renderPdfPage(page, canvas, context, pageFrame);
            }).then(function () {
                renderAnnotationsForPage(pageNumber, singleLayer);
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
            pageFrame.hidden = true;
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
                    const frame = document.createElement('div');
                    const pageCanvas = document.createElement('canvas');
                    const layer = document.createElement('div');
                    frame.className = 'document-pdf-page-frame';
                    frame.dataset.pageNumber = String(number);
                    layer.className = 'document-pdf-annotation-layer';
                    pageCanvas.dataset.pageNumber = String(number);
                    frame.appendChild(pageCanvas);
                    frame.appendChild(layer);
                    pages.appendChild(frame);
                    bindAnnotationLayer(layer, number);

                    return renderPdfPage(page, pageCanvas, pageCanvas.getContext('2d'), frame).then(function () {
                        renderAnnotationsForPage(number, layer);
                    });
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

        function renderPdfPage(page, targetCanvas, targetContext, frame) {
            const baseViewport = page.getViewport({ scale: 1 });
            const availableWidth = Math.max(280, shell.clientWidth - 28);
            const baseScale = Math.min(1.8, availableWidth / baseViewport.width);
            const viewport = page.getViewport({ scale: baseScale * zoom });
            const outputScale = window.devicePixelRatio || 1;
            const width = Math.floor(viewport.width);
            const height = Math.floor(viewport.height);

            targetCanvas.width = Math.floor(width * outputScale);
            targetCanvas.height = Math.floor(height * outputScale);
            targetCanvas.style.width = width + 'px';
            targetCanvas.style.height = height + 'px';
            frame.style.width = width + 'px';
            frame.style.height = height + 'px';

            return page.render({
                canvasContext: targetContext,
                viewport: viewport,
                transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null
            }).promise;
        }

        function renderAnnotationsForPage(number, layer) {
            if (!layer) {
                return;
            }

            layer.innerHTML = '';
            annotations.filter(function (annotation) {
                return annotation.page_number === number;
            }).forEach(function (annotation) {
                layer.appendChild(annotationElement(annotation));
            });
        }

        function annotationElement(annotation) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'document-pdf-annotation document-pdf-annotation-' + annotation.type;
            item.style.left = (annotation.x * 100) + '%';
            item.style.top = (annotation.y * 100) + '%';
            item.style.width = (Math.max(annotation.width, 0.018) * 100) + '%';
            item.style.height = (Math.max(annotation.height, 0.018) * 100) + '%';
            applyAnnotationColor(item, annotation.color || '#facc15');
            item.title = annotation.note || 'Marcacao';
            item.setAttribute('aria-label', annotation.note || 'Marcacao no PDF');
            item.innerHTML = annotation.type === 'comment' ? '<i class="bi bi-chat-left-text" aria-hidden="true"></i>' : '';
            item.addEventListener('click', function (event) {
                event.stopPropagation();
                showAnnotationDetail(annotation, item);
            });

            return item;
        }

        function showAnnotationDetail(annotation, anchor) {
            viewer.querySelectorAll('.document-pdf-annotation-popover').forEach(function (item) {
                item.remove();
            });

            const popover = document.createElement('div');
            popover.className = 'document-pdf-annotation-popover';
            const note = annotation.note ? escapeHtml(annotation.note) : 'Sem comentario.';
            const author = annotation.user_name ? '<small>' + escapeHtml(annotation.user_name) + '</small>' : '';
            popover.innerHTML = '<strong>' + (annotation.type === 'comment' ? 'Comentario' : 'Marcacao') + '</strong>' + author + '<p>' + note + '</p><button type="button">Remover</button>';
            popover.querySelector('button').addEventListener('click', function () {
                deleteAnnotation(annotation.id);
            });
            anchor.appendChild(popover);
        }

        function bindAnnotationLayer(layer, number) {
            if (!layer || !viewer.dataset.pdfAnnotationsStoreUrl) {
                return;
            }

            let start = null;
            let preview = null;

            layer.addEventListener('pointerdown', function (event) {
                if (activeTool === 'select' || event.target !== layer) {
                    return;
                }

                const point = layerPoint(layer, event);
                const targetPage = layer === singleLayer ? pageNumber : number;

                if (activeTool === 'comment') {
                    saveAnnotation({
                        page_number: targetPage,
                        type: 'comment',
                        x: point.x,
                        y: point.y,
                        width: 0.035,
                        height: 0.035,
                        color: colorInput?.value || '#facc15',
                        note: noteInput?.value || ''
                    });
                    return;
                }

                start = point;
                preview = document.createElement('div');
                preview.className = 'document-pdf-annotation-preview';
                applyAnnotationColor(preview, colorInput?.value || '#facc15');
                layer.appendChild(preview);
                layer.setPointerCapture(event.pointerId);
            });

            layer.addEventListener('pointermove', function (event) {
                if (!start || !preview) {
                    return;
                }

                drawPreview(preview, start, layerPoint(layer, event));
            });

            layer.addEventListener('pointerup', function (event) {
                if (!start || !preview) {
                    return;
                }

                const box = normalizedBox(start, layerPoint(layer, event));
                preview.remove();
                preview = null;
                start = null;

                if (box.width < 0.01 || box.height < 0.01) {
                    return;
                }

                saveAnnotation({
                    page_number: layer === singleLayer ? pageNumber : number,
                    type: 'highlight',
                    x: box.x,
                    y: box.y,
                    width: box.width,
                    height: box.height,
                    color: colorInput?.value || '#facc15',
                    note: noteInput?.value || ''
                });
            });
        }

        function layerPoint(layer, event) {
            const rect = layer.getBoundingClientRect();
            return {
                x: clamp((event.clientX - rect.left) / rect.width),
                y: clamp((event.clientY - rect.top) / rect.height)
            };
        }

        function drawPreview(element, start, end) {
            const box = normalizedBox(start, end);
            element.style.left = (box.x * 100) + '%';
            element.style.top = (box.y * 100) + '%';
            element.style.width = (box.width * 100) + '%';
            element.style.height = (box.height * 100) + '%';
        }

        function normalizedBox(start, end) {
            const x = Math.min(start.x, end.x);
            const y = Math.min(start.y, end.y);
            return {
                x: x,
                y: y,
                width: Math.max(start.x, end.x) - x,
                height: Math.max(start.y, end.y) - y
            };
        }

        function saveAnnotation(payload) {
            if (payload.type === 'comment' && !payload.note.trim()) {
                setHint('Escreva um comentario antes de clicar no PDF.');
                noteInput?.focus();
                return;
            }

            fetch(viewer.dataset.pdfAnnotationsStoreUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            }).then(jsonResponse).then(function (data) {
                annotations.push(normalizeAnnotation(data.annotation));
                renderPage();
                if (payload.type === 'comment' && noteInput) {
                    noteInput.value = '';
                }
                setHint('Anotacao salva.');
            }).catch(function (error) {
                setHint(error.message || 'Nao foi possivel salvar a anotacao.');
            });
        }

        function deleteAnnotation(id) {
            fetch(viewer.dataset.pdfAnnotationsDeleteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ id: id })
            }).then(jsonResponse).then(function () {
                annotations = annotations.filter(function (annotation) {
                    return annotation.id !== id;
                });
                renderPage();
                setHint('Anotacao removida.');
            }).catch(function (error) {
                setHint(error.message || 'Nao foi possivel remover a anotacao.');
            });
        }

        function loadAnnotations() {
            if (!viewer.dataset.pdfAnnotationsUrl) {
                return Promise.resolve();
            }

            return fetch(viewer.dataset.pdfAnnotationsUrl, { credentials: 'same-origin' })
                .then(jsonResponse)
                .then(function (data) {
                    annotations = (data.annotations || []).map(normalizeAnnotation);
                })
                .catch(function () {
                    setHint('PDF carregado, mas as anotacoes nao foram lidas.');
                });
        }

        function updateCurrentPageFromScroll() {
            if (mode !== 'scroll' || !pdf) {
                return;
            }

            const frames = Array.from(pages.querySelectorAll('[data-page-number]'));
            const shellTop = shell.getBoundingClientRect().top;
            let closest = pageNumber;
            let closestDistance = Number.POSITIVE_INFINITY;

            frames.forEach(function (frame) {
                const distance = Math.abs(frame.getBoundingClientRect().top - shellTop - 12);
                if (distance < closestDistance) {
                    closestDistance = distance;
                    closest = parseInt(frame.dataset.pageNumber || '1', 10) || 1;
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

        function setHint(message) {
            if (hint) {
                hint.textContent = message;
            }
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

        viewer.querySelectorAll('[data-pdf-tool]').forEach(function (button) {
            button.addEventListener('click', function () {
                activeTool = button.dataset.pdfTool || 'select';
                setHint(activeTool === 'highlight' ? 'Arraste no PDF para marcar uma area.' : activeTool === 'comment' ? 'Digite o comentario e clique no ponto do PDF.' : 'Selecione uma anotacao para ver ou remover.');
                updateStatus();
            });
        });

        bindAnnotationLayer(singleLayer, pageNumber);
        window.addEventListener('scroll', updateCurrentPageFromScroll, { passive: true });
        window.addEventListener('resize', function () {
            if (pdf) {
                renderPage();
            }
        });

        Promise.all([
            window.pdfjsLib.getDocument({ url: url, withCredentials: true }).promise,
            loadAnnotations()
        ]).then(function (results) {
            pdf = results[0];
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

    function normalizeAnnotation(annotation) {
        return {
            id: parseInt(annotation.id || '0', 10) || 0,
            page_number: parseInt(annotation.page_number || '1', 10) || 1,
            type: annotation.type === 'comment' ? 'comment' : 'highlight',
            x: clamp(parseFloat(annotation.x || '0')),
            y: clamp(parseFloat(annotation.y || '0')),
            width: clamp(parseFloat(annotation.width || '0')),
            height: clamp(parseFloat(annotation.height || '0')),
            color: annotation.color || '#facc15',
            note: annotation.note || '',
            user_name: annotation.user_name || ''
        };
    }

    function jsonResponse(response) {
        return response.json().then(function (data) {
            if (!response.ok) {
                throw new Error(data.error || 'Erro ao processar a solicitacao.');
            }

            return data;
        });
    }

    function clamp(value) {
        value = Number.isFinite(value) ? value : 0;
        return Math.min(1, Math.max(0, value));
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function applyAnnotationColor(element, color) {
        element.style.setProperty('--annotation-color', color);
        element.style.setProperty('--annotation-fill', hexToRgba(color, 0.34));
        element.style.setProperty('--annotation-border', hexToRgba(color, 0.88));
    }

    function hexToRgba(color, alpha) {
        const normalized = /^#?[0-9a-f]{6}$/i.test(color) ? color.replace('#', '') : 'facc15';
        const red = parseInt(normalized.slice(0, 2), 16);
        const green = parseInt(normalized.slice(2, 4), 16);
        const blue = parseInt(normalized.slice(4, 6), 16);
        return 'rgba(' + red + ', ' + green + ', ' + blue + ', ' + alpha + ')';
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
