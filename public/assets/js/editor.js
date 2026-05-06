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

    editor.addEventListener('input', () => {
        input.value = editor.innerHTML;
    });

    form.addEventListener('submit', () => {
        input.value = editor.innerHTML;
    });
})();
