(function () {
    const editors = document.querySelectorAll('.rich-editor[data-target]');
    if (!editors.length) {
        return;
    }

    editors.forEach(initRichEditor);

    function initRichEditor(editor) {
    const input = document.getElementById(editor.dataset.target);
    const form = editor.closest('form');
    if (!input || !form) {
        return;
    }

    const htmlEditor = form.querySelector('[data-html-editor]');
    const toolbar = form.querySelector('.rich-toolbar');
    if (!toolbar) {
        return;
    }

    let htmlMode = false;
    let savedRange = null;
    let selectedImage = null;

    if (input.value && editor.innerHTML.trim() === '') {
        editor.innerHTML = input.value;
    }

    toolbar.addEventListener('mousedown', (event) => {
        saveSelection();
        if (event.target.closest('button')) {
            event.preventDefault();
        }
    });

    toolbar.addEventListener('touchstart', () => {
        saveSelection();
    }, { passive: true });

    toolbar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenus();
        }
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
                target.dataset.insertInEditor = '1';
                target.click();
            }
            return;
        }

        if (button.dataset.color) {
            restoreSelection();
            editor.focus();
            document.execCommand('foreColor', false, button.dataset.color);
            syncFromVisual();
            closeMenus();
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

        if (button.dataset.imageSize) {
            applyImageSize(button.dataset.imageSize);
            syncFromVisual();
            return;
        }

        if (button.dataset.imageAlign) {
            applyImageAlign(button.dataset.imageAlign);
            syncFromVisual();
            return;
        }

        if (button.dataset.action === 'html-toggle') {
            if (!htmlEditor) {
                return;
            }
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
            editLink();
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
            if (selectedImage && command.startsWith('justify')) {
                applyImageAlignFromCommand(command);
                syncFromVisual();
                return;
            }

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
    editor.addEventListener('click', (event) => {
        selectedImage = event.target instanceof HTMLImageElement ? event.target : null;
        editor.querySelectorAll('img.is-selected-media').forEach((image) => {
            image.classList.toggle('is-selected-media', image === selectedImage);
        });
    });

    form.querySelectorAll('input[type="file"][name="content_media[]"]').forEach((fileInput) => {
        fileInput.addEventListener('change', () => {
            if (htmlMode || fileInput.dataset.insertInEditor !== '1' || !fileInput.files.length) {
                fileInput.dataset.insertInEditor = '';
                return;
            }

            restoreSelection();
            editor.focus();

            Array.from(fileInput.files).forEach((file, index) => {
                const html = pendingUploadHtml(file, index);
                if (html) {
                    document.execCommand('insertHTML', false, html);
                }
            });

            fileInput.dataset.insertInEditor = '';
            syncFromVisual();
        });
    });

    if (htmlEditor) {
        htmlEditor.addEventListener('input', () => {
            input.value = sanitizeEditorHtml(htmlEditor.value);
        });
    }

    form.addEventListener('submit', () => {
        editor.querySelectorAll('[data-preview-url]').forEach((element) => {
            const previewUrl = element.getAttribute('data-preview-url');
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            element.removeAttribute('data-preview-url');
        });

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
        if (!wasOpen) {
            positionMenu(menu);
        }
    }

    function closeMenus() {
        toolbar.querySelectorAll('.rich-menu.is-open').forEach((menu) => {
            menu.classList.remove('is-open');
            resetMenuPosition(menu);
        });
    }

    function positionMenu(menu) {
        const popover = menu.querySelector('.rich-menu-popover');
        if (!popover) {
            return;
        }

        resetMenuPosition(menu);

        if (window.matchMedia('(max-width: 560px)').matches) {
            const buttonRect = menu.getBoundingClientRect();
            const preferredTop = buttonRect.bottom + 8;
            const top = preferredTop > window.innerHeight - 160 ? 12 : preferredTop;

            popover.style.position = 'fixed';
            popover.style.top = `${top}px`;
            popover.style.left = '12px';
            popover.style.right = '12px';
            popover.style.maxWidth = 'none';
            popover.style.maxHeight = `${Math.max(160, window.innerHeight - top - 12)}px`;
            popover.style.overflowY = 'auto';
            return;
        }

        const rect = popover.getBoundingClientRect();
        const margin = 12;

        if (rect.right > window.innerWidth - margin) {
            popover.style.left = 'auto';
            popover.style.right = '0';
        }
    }

    function resetMenuPosition(menu) {
        const popover = menu.querySelector('.rich-menu-popover');
        if (!popover) {
            return;
        }

        popover.style.position = '';
        popover.style.top = '';
        popover.style.left = '';
        popover.style.right = '';
        popover.style.maxWidth = '';
        popover.style.maxHeight = '';
        popover.style.overflowY = '';
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
                const title = element.getAttribute('title') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                element.setAttribute('href', isSafeUrl(href) ? href : '#');
                element.setAttribute('target', '_blank');
                element.setAttribute('rel', 'noopener');
                if (title.trim()) {
                    element.setAttribute('title', title.trim());
                }
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
                const pendingUpload = element.getAttribute('data-pending-upload') || '';
                const previewUrl = element.getAttribute('data-preview-url') || '';
                const sizeClass = imageSizeClass(element.className);
                const alignClass = imageAlignClass(element.className);
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (/^\d+$/.test(pendingUpload)) {
                    element.setAttribute('src', isSafeImageUrl(src) ? src : `/__pending-upload/${pendingUpload}`);
                    element.setAttribute('alt', alt);
                    element.setAttribute('loading', 'lazy');
                    if (sizeClass) {
                        element.classList.add(sizeClass);
                    }
                    if (alignClass) {
                        element.classList.add(alignClass);
                    }
                    element.setAttribute('data-pending-upload', pendingUpload);
                    if (previewUrl) {
                        element.setAttribute('data-preview-url', previewUrl);
                    }
                } else if (isSafeImageUrl(src)) {
                    element.setAttribute('src', src);
                    element.setAttribute('alt', alt);
                    element.setAttribute('loading', 'lazy');
                    if (sizeClass) {
                        element.classList.add(sizeClass);
                    }
                    if (alignClass) {
                        element.classList.add(alignClass);
                    }
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
                const pendingUpload = element.getAttribute('data-pending-upload') || '';
                const previewUrl = element.getAttribute('data-preview-url') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (/^\d+$/.test(pendingUpload)) {
                    element.setAttribute('src', isSafeMediaUrl(src, element.tagName.toLowerCase()) ? src : `/__pending-upload/${pendingUpload}`);
                    element.setAttribute('controls', '');
                    element.setAttribute('data-pending-upload', pendingUpload);
                    if (previewUrl) {
                        element.setAttribute('data-preview-url', previewUrl);
                    }
                    if (element.tagName === 'VIDEO') {
                        element.setAttribute('playsinline', '');
                    }
                } else if (isSafeMediaUrl(src, element.tagName.toLowerCase())) {
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

    function editLink() {
        restoreSelection();
        editor.focus();

        const existingLink = linkFromSelection();
        const selectedText = existingLink ? existingLink.textContent : selectionText();
        const currentHref = existingLink ? existingLink.getAttribute('href') || '' : '';
        const currentTitle = existingLink ? existingLink.getAttribute('title') || '' : '';

        const text = window.prompt('Texto que aparece no link:', selectedText || currentHref || 'Leia mais');
        if (text === null) {
            return;
        }

        const href = window.prompt('URL do link:', currentHref || 'https://');
        if (!href || !isSafeUrl(href)) {
            window.alert('Informe um link começando com https://, http://, mailto: ou /.');
            return;
        }

        const title = window.prompt('Descrição opcional do link:', currentTitle);
        const linkHtml = `<a href="${escapeAttribute(href)}"${title ? ` title="${escapeAttribute(title)}"` : ''}>${escapeHtml(text.trim() || href)}</a>`;

        if (existingLink) {
            existingLink.outerHTML = linkHtml;
        } else {
            document.execCommand('insertHTML', false, linkHtml);
        }
    }

    function linkFromSelection() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        return node instanceof Element ? node.closest('a') : null;
    }

    function selectionText() {
        const selection = window.getSelection();
        return selection ? String(selection.toString()).trim() : '';
    }

    function applyImageSize(size) {
        const image = selectedImage || imageFromSelection();
        if (!image) {
            window.alert('Clique em uma imagem do texto antes de escolher o tamanho.');
            return;
        }

        image.classList.remove('image-size-small', 'image-size-medium', 'image-size-large', 'image-size-full');
        image.classList.add(`image-size-${size}`);
        selectedImage = image;
        image.classList.add('is-selected-media');
    }

    function applyImageAlign(align) {
        const image = selectedImage || imageFromSelection();
        if (!image) {
            window.alert('Clique em uma imagem do texto antes de escolher a posição.');
            return;
        }

        image.classList.remove('image-align-left', 'image-align-center', 'image-align-right', 'image-align-justify');
        image.classList.add(`image-align-${align}`);
        if (align === 'justify') {
            image.classList.remove('image-size-small', 'image-size-medium', 'image-size-large');
            image.classList.add('image-size-full');
        }
        selectedImage = image;
        image.classList.add('is-selected-media');
    }

    function applyImageAlignFromCommand(command) {
        const map = {
            justifyLeft: 'left',
            justifyCenter: 'center',
            justifyRight: 'right',
            justifyFull: 'justify',
        };

        applyImageAlign(map[command] || 'left');
    }

    function imageFromSelection() {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }

        let node = selection.anchorNode;
        if (node && node.nodeType === Node.TEXT_NODE) {
            node = node.parentNode;
        }

        if (node instanceof HTMLImageElement) {
            return node;
        }

        return node instanceof Element ? node.querySelector('img') : null;
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

    function imageSizeClass(className) {
        const allowed = ['image-size-small', 'image-size-medium', 'image-size-large', 'image-size-full'];
        return String(className || '').split(/\s+/).find((item) => allowed.includes(item)) || '';
    }

    function imageAlignClass(className) {
        const allowed = ['image-align-left', 'image-align-center', 'image-align-right', 'image-align-justify'];
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

    function pendingUploadHtml(file, index) {
        const previewUrl = URL.createObjectURL(file);
        const escapedPreviewUrl = escapeAttribute(previewUrl);
        const escapedName = escapeAttribute(file.name || '');

        if (file.type.startsWith('image/')) {
            return `<p><img src="${escapedPreviewUrl}" alt="${escapedName}" loading="lazy" data-pending-upload="${index}" data-preview-url="${escapedPreviewUrl}"></p>`;
        }

        if (file.type.startsWith('video/')) {
            return `<p><video controls playsinline src="${escapedPreviewUrl}" data-pending-upload="${index}" data-preview-url="${escapedPreviewUrl}"></video></p>`;
        }

        if (file.type.startsWith('audio/')) {
            return `<p><audio controls src="${escapedPreviewUrl}" data-pending-upload="${index}" data-preview-url="${escapedPreviewUrl}"></audio></p>`;
        }

        URL.revokeObjectURL(previewUrl);
        return '';
    }

    function videoEmbedUrl(url) {
        try {
            const parsed = new URL(url, window.location.origin);
            const host = parsed.hostname.replace(/^www\./, '');

            if (host === 'youtube.com' || host === 'm.youtube.com') {
                const parts = parsed.pathname.split('/').filter(Boolean);
                const id = parsed.searchParams.get('v') || (['embed', 'live'].includes(parts[0]) ? parts[1] : '');
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
        return /^(https?:\/\/|\/|blob:)/i.test(url);
    }

    function isSafeEmbedUrl(url) {
        return /^https:\/\/(www\.youtube\.com\/embed\/|player\.vimeo\.com\/video\/)/i.test(url);
    }

    function isSafeMediaUrl(url, type) {
        if (!/^(https?:\/\/|\/|blob:)/i.test(url)) {
            return false;
        }

        if (/^blob:/i.test(url)) {
            return true;
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
    }
})();
