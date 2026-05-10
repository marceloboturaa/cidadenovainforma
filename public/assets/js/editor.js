(function () {
    const editor = document.querySelector('.rich-editor[data-target]');
    if (!editor) {
        return;
    }

    const input = document.getElementById(editor.dataset.target);
    const form = editor.closest('form');
    const htmlEditor = form.querySelector('[data-html-editor]');
    const toolbar = form.querySelector('.rich-toolbar');
    let htmlMode = false;
    let savedRange = null;

    if (input.value && editor.innerHTML.trim() === '') {
        editor.innerHTML = input.value;
    }

    toolbar.addEventListener('mousedown', (event) => {
        if (event.target.closest('button')) {
            event.preventDefault();
        }
        saveSelection();
    });

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (button.dataset.menuToggle !== undefined) {
            toggleMenu(button.closest('.rich-menu'));
            return;
        }

        closeMenus();

        if (button.dataset.uploadTarget) {
            const target = document.getElementById(button.dataset.uploadTarget);
            if (target) {
                target.click();
            }
            return;
        }

        if (button.dataset.color) {
            restoreSelection();
            editor.focus();
            document.execCommand('foreColor', false, button.dataset.color);
            syncFromVisual();
            return;
        }

        if (button.dataset.blockFormat) {
            restoreSelection();
            editor.focus();
            applyBlockFormat(button.dataset.blockFormat);
            markActiveBlockButton(button.dataset.blockFormat);
            syncFromVisual();
            return;
        }

        if (button.dataset.action === 'html-toggle') {
            toggleHtmlMode(button);
            return;
        }

        if (htmlMode) {
            return;
        }

        restoreSelection();
        editor.focus();
        const command = button.dataset.command;
        const value = button.dataset.value || null;
        const action = button.dataset.action;

        if (action === 'link') {
            const href = window.prompt('Link completo ou caminho interno:');
            if (href) {
                restoreSelection();
                document.execCommand('createLink', false, href);
            }
        } else if (action === 'image') {
            const src = window.prompt('URL da imagem:');
            if (src && isSafeImageUrl(src)) {
                restoreSelection();
                document.execCommand('insertHTML', false, `<p><img src="${escapeAttribute(src)}" alt="" loading="lazy"></p>`);
            }
        } else if (action === 'video') {
            const src = window.prompt('URL do vídeo, YouTube ou Vimeo:');
            const html = mediaHtml(src, 'video');
            if (html) {
                restoreSelection();
                document.execCommand('insertHTML', false, html);
            }
        } else if (action === 'audio') {
            const src = window.prompt('URL do áudio MP3, OGG ou WAV:');
            const html = mediaHtml(src, 'audio');
            if (html) {
                restoreSelection();
                document.execCommand('insertHTML', false, html);
            }
        } else if (action === 'color') {
            const color = window.prompt('Cor em hexadecimal. Ex.: #b91c1c');
            if (/^#[0-9a-f]{6}$/i.test(color || '')) {
                restoreSelection();
                document.execCommand('foreColor', false, color);
            }
        } else if (action === 'clear-format') {
            clearFormatting();
        } else if (command) {
            document.execCommand(command, false, value);
        }

        syncFromVisual();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.rich-menu')) {
            closeMenus();
        }
    });

    editor.addEventListener('paste', (event) => {
        event.preventDefault();

        const html = (event.clipboardData || window.clipboardData).getData('text/html');
        const text = (event.clipboardData || window.clipboardData).getData('text/plain');

        if (html.trim()) {
            document.execCommand('insertHTML', false, sanitizeEditorHtml(html));
        } else if (text.trim()) {
            document.execCommand('insertHTML', false, plainTextToHtml(text));
        }

        syncFromVisual();
    });

    editor.addEventListener('input', syncFromVisual);
    editor.addEventListener('keyup', saveSelection);
    editor.addEventListener('mouseup', saveSelection);
    editor.addEventListener('focus', saveSelection);

    if (htmlEditor) {
        htmlEditor.addEventListener('input', () => {
            input.value = sanitizeEditorHtml(htmlEditor.value);
        });
    }

    form.addEventListener('submit', () => {
        if (htmlMode && htmlEditor) {
            input.value = sanitizeEditorHtml(htmlEditor.value);
            return;
        }

        syncFromVisual();
    });

    function toggleHtmlMode(button) {
        htmlMode = !htmlMode;

        if (htmlMode) {
            input.value = sanitizeEditorHtml(editor.innerHTML);
            htmlEditor.value = input.value;
            editor.hidden = true;
            htmlEditor.hidden = false;
            button.classList.add('is-active');
            button.title = 'Visualização "Escrever"';
            htmlEditor.focus();
            return;
        }

        input.value = sanitizeEditorHtml(htmlEditor.value);
        editor.innerHTML = input.value;
        htmlEditor.hidden = true;
        editor.hidden = false;
        button.classList.remove('is-active');
        button.title = 'Visualização em HTML';
        editor.focus();
    }

    function toggleMenu(menu) {
        if (!menu) {
            return;
        }

        const wasOpen = menu.classList.contains('is-open');
        closeMenus();
        menu.classList.toggle('is-open', !wasOpen);
    }

    function closeMenus() {
        toolbar.querySelectorAll('.rich-menu.is-open').forEach((menu) => {
            menu.classList.remove('is-open');
        });
    }

    function syncFromVisual() {
        input.value = sanitizeEditorHtml(editor.innerHTML);
        if (editor.innerHTML !== input.value) {
            editor.innerHTML = input.value;
        }
    }

    function plainTextToHtml(text) {
        return text
            .replace(/\r\n/g, '\n')
            .split(/\n{2,}/)
            .map((paragraph) => paragraph.trim())
            .filter(Boolean)
            .map((paragraph) => `<p>${escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
            .join('');
    }

    function sanitizeEditorHtml(html) {
        const template = document.createElement('template');
        template.innerHTML = html;

        template.content.querySelectorAll('*').forEach((element) => {
            if (element.tagName === 'A') {
                const href = element.getAttribute('href') || '#';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                element.setAttribute('href', isSafeUrl(href) ? href : '#');
                element.setAttribute('target', '_blank');
                element.setAttribute('rel', 'noopener');
                return;
            }

            if (isBlockElement(element)) {
                const alignClass = alignmentClass(element);
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (alignClass) {
                    element.className = alignClass;
                }
                return;
            }

            if (element.tagName === 'FONT' || element.tagName === 'SPAN') {
                const color = element.getAttribute('color') || element.style.color || '';
                const colorClass = colorToClass(color) || existingColorClass(element.className);
                const replacement = document.createElement('span');
                replacement.innerHTML = element.innerHTML;

                if (colorClass) {
                    replacement.className = colorClass;
                }

                element.replaceWith(...(colorClass ? [replacement] : Array.from(replacement.childNodes)));
                return;
            }

            if (element.tagName === 'IMG') {
                const src = element.getAttribute('src') || '';
                const alt = element.getAttribute('alt') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (isSafeImageUrl(src)) {
                    element.setAttribute('src', src);
                    element.setAttribute('alt', alt);
                    element.setAttribute('loading', 'lazy');
                } else {
                    element.remove();
                }
                return;
            }

            if (element.tagName === 'IFRAME') {
                const src = element.getAttribute('src') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (isSafeEmbedUrl(src)) {
                    element.setAttribute('src', src);
                    element.setAttribute('loading', 'lazy');
                    element.setAttribute('allowfullscreen', '');
                    element.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
                } else {
                    element.remove();
                }
                return;
            }

            if (element.tagName === 'VIDEO' || element.tagName === 'AUDIO') {
                const src = element.getAttribute('src') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (isSafeMediaUrl(src, element.tagName.toLowerCase())) {
                    element.setAttribute('src', src);
                    element.setAttribute('controls', '');
                    if (element.tagName === 'VIDEO') {
                        element.setAttribute('playsinline', '');
                    }
                } else {
                    element.remove();
                }
                return;
            }

            [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
        });

        return template.innerHTML;
    }

    function saveSelection() {
        if (htmlMode) {
            return;
        }

        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        if (editor.contains(range.commonAncestorContainer)) {
            savedRange = range.cloneRange();
        }
    }

    function restoreSelection() {
        if (!savedRange || htmlMode) {
            return;
        }

        const selection = window.getSelection();
        if (!selection) {
            return;
        }

        selection.removeAllRanges();
        selection.addRange(savedRange);
    }

    function clearFormatting() {
        restoreSelection();
        editor.focus();
        document.execCommand('removeFormat', false, null);
        document.execCommand('unlink', false, null);
        removeFormattingClassesFromSelection();
        syncFromVisual();
    }

    function applyBlockFormat(value) {
        const formats = {
            P: 'p',
            H2: 'h2',
            H3: 'h3',
            BLOCKQUOTE: 'blockquote',
        };
        const format = formats[value] || 'p';

        document.execCommand('formatBlock', false, format);

        if (value === 'P') {
            document.execCommand('removeFormat', false, null);
            removeFormattingClassesFromSelection();
        }
    }

    function markActiveBlockButton(value) {
        toolbar.querySelectorAll('[data-block-format]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.blockFormat === value);
        });
    }

    function removeFormattingClassesFromSelection() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        editor.querySelectorAll('.text-align-left, .text-align-center, .text-align-right, .text-align-justify').forEach((element) => {
            if (range.intersectsNode(element)) {
                element.classList.remove('text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify');
            }
        });

        editor.querySelectorAll('.text-color-ink, .text-color-gray, .text-color-red, .text-color-orange, .text-color-gold, .text-color-green, .text-color-teal, .text-color-blue').forEach((element) => {
            if (range.intersectsNode(element)) {
                element.replaceWith(...Array.from(element.childNodes));
            }
        });
    }

    function isBlockElement(element) {
        return ['P', 'H2', 'H3', 'BLOCKQUOTE', 'LI'].includes(element.tagName);
    }

    function alignmentClass(element) {
        const align = String(element.style.textAlign || element.getAttribute('align') || '').toLowerCase();
        const existing = existingAlignClass(element.className);

        if (align === 'center') {
            return 'text-align-center';
        }

        if (align === 'right') {
            return 'text-align-right';
        }

        if (align === 'justify') {
            return 'text-align-justify';
        }

        if (align === 'left') {
            return 'text-align-left';
        }

        return existing;
    }

    function existingAlignClass(className) {
        const allowed = ['text-align-left', 'text-align-center', 'text-align-right', 'text-align-justify'];
        return String(className || '').split(/\s+/).find((item) => allowed.includes(item)) || '';
    }

    function mediaHtml(url, type) {
        if (!url || !isSafeUrl(url)) {
            return '';
        }

        const embed = videoEmbedUrl(url);
        if (type === 'video' && embed) {
            return `<p><iframe src="${escapeAttribute(embed)}" loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></p>`;
        }

        if (type === 'video' && isSafeMediaUrl(url, 'video')) {
            return `<p><video controls playsinline src="${escapeAttribute(url)}"></video></p>`;
        }

        if (type === 'audio' && isSafeMediaUrl(url, 'audio')) {
            return `<p><audio controls src="${escapeAttribute(url)}"></audio></p>`;
        }

        return '';
    }

    function videoEmbedUrl(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            const host = parsed.hostname.replace(/^www\./, '');

            if (host === 'youtube.com' || host === 'm.youtube.com') {
                const id = parsed.searchParams.get('v');
                return id ? `https://www.youtube.com/embed/${encodeURIComponent(id)}` : '';
            }

            if (host === 'youtu.be') {
                const id = parsed.pathname.split('/').filter(Boolean)[0];
                return id ? `https://www.youtube.com/embed/${encodeURIComponent(id)}` : '';
            }

            if (host === 'vimeo.com') {
                const id = parsed.pathname.split('/').filter(Boolean)[0];
                return id ? `https://player.vimeo.com/video/${encodeURIComponent(id)}` : '';
            }
        } catch (error) {
            return '';
        }

        return '';
    }

    function isSafeUrl(url) {
        return /^(https?:\/\/|mailto:|\/)/i.test(url);
    }

    function isSafeImageUrl(url) {
        return /^(https?:\/\/|\/)/i.test(url);
    }

    function isSafeEmbedUrl(url) {
        return /^https:\/\/(www\.youtube\.com\/embed\/|player\.vimeo\.com\/video\/)/i.test(url);
    }

    function isSafeMediaUrl(url, type) {
        if (!/^(https?:\/\/|\/)/i.test(url)) {
            return false;
        }

        return type === 'video'
            ? /\.(mp4|webm)(\?.*)?$/i.test(url)
            : /\.(mp3|ogg|wav)(\?.*)?$/i.test(url);
    }

    function colorToClass(color) {
        const normalized = normalizeColor(color);
        const map = {
            '#111827': 'text-color-ink',
            '#6b7280': 'text-color-gray',
            '#b91c1c': 'text-color-red',
            '#c2410c': 'text-color-orange',
            '#a16207': 'text-color-gold',
            '#15803d': 'text-color-green',
            '#0f766e': 'text-color-teal',
            '#2563eb': 'text-color-blue',
        };

        return map[normalized] || '';
    }

    function existingColorClass(className) {
        const allowed = ['text-color-ink', 'text-color-gray', 'text-color-red', 'text-color-orange', 'text-color-gold', 'text-color-green', 'text-color-teal', 'text-color-blue'];
        return String(className || '').split(/\s+/).find((item) => allowed.includes(item)) || '';
    }

    function normalizeColor(color) {
        const value = String(color || '').trim().toLowerCase();
        const rgb = value.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);

        if (rgb) {
            return '#' + rgb.slice(1).map((part) => Number(part).toString(16).padStart(2, '0')).join('');
        }

        return value;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function escapeAttribute(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }
})();
