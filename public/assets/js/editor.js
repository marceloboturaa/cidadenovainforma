(function () {
    const editor = document.querySelector('.rich-editor[data-target]');
    if (!editor) {
        return;
    }

    const input = document.getElementById(editor.dataset.target);
    const form = editor.closest('form');

    if (input.value && editor.innerHTML.trim() === '') {
        editor.innerHTML = input.value;
    }

    document.querySelectorAll('.rich-toolbar button').forEach((button) => {
        button.addEventListener('click', () => {
            editor.focus();

            const command = button.dataset.command;
            const value = button.dataset.value || null;
            const action = button.dataset.action;

            if (action === 'link') {
                const href = window.prompt('Link completo ou caminho interno:');
                if (href) {
                    document.execCommand('createLink', false, href);
                }
            } else if (action === 'image') {
                const src = window.prompt('URL da imagem:');
                if (src) {
                    document.execCommand('insertImage', false, src);
                }
            } else if (command) {
                document.execCommand(command, false, value);
            }

            input.value = editor.innerHTML;
        });
    });

    editor.addEventListener('paste', (event) => {
        event.preventDefault();

        const text = (event.clipboardData || window.clipboardData).getData('text/plain');
        if (!text.trim()) {
            return;
        }

        document.execCommand('insertHTML', false, plainTextToHtml(text));
        input.value = sanitizeEditorHtml(editor.innerHTML);
        editor.innerHTML = input.value;
    });

    editor.addEventListener('input', () => {
        input.value = sanitizeEditorHtml(editor.innerHTML);
    });

    form.addEventListener('submit', () => {
        input.value = sanitizeEditorHtml(editor.innerHTML);
    });

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

            if (element.tagName === 'IMG') {
                const src = element.getAttribute('src') || '';
                const alt = element.getAttribute('alt') || '';
                [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
                if (isSafeUrl(src)) {
                    element.setAttribute('src', src);
                    element.setAttribute('alt', alt);
                    element.setAttribute('loading', 'lazy');
                } else {
                    element.remove();
                }
                return;
            }

            [...element.attributes].forEach((attribute) => element.removeAttribute(attribute.name));
        });

        return template.innerHTML;
    }

    function isSafeUrl(url) {
        return /^(https?:\/\/|mailto:|\/)/i.test(url);
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }
})();
