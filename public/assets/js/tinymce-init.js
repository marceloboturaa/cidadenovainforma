(function () {
    const fields = document.querySelectorAll('textarea[data-tinymce]');

    if (!fields.length || typeof tinymce === 'undefined') {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const uploadUrl = document.querySelector('meta[name="tinymce-upload-url"]')?.content || '';
    const imageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    function uploadImage(file, filename) {
        return new Promise(function (resolve, reject) {
            if (!uploadUrl) {
                reject('Upload de imagem nao configurado.');
                return;
            }

            if (!file || !imageTypes.includes(file.type)) {
                reject('Use JPG, PNG, WEBP ou GIF.');
                return;
            }

            const formData = new FormData();
            formData.append('_token', token);
            formData.append('file', file, filename || file.name || 'imagem');

            fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok) {
                            throw new Error(payload.error || 'Falha no upload.');
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    if (!payload.location) {
                        throw new Error('Resposta de upload invalida.');
                    }

                    resolve(payload.location);
                })
                .catch(function (error) {
                    reject(error.message || 'Falha no upload.');
                });
        });
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function latexFromSelection(editor) {
        const selected = editor.selection.getContent({ format: 'text' }).trim();
        const blockMatch = selected.match(/^\$\$([\s\S]*)\$\$$/);
        const bracketMatch = selected.match(/^\\\[([\s\S]*)\\\]$/);
        const inlineMatch = selected.match(/^\\\(([\s\S]*)\\\)$/);

        if (blockMatch) {
            return { formula: blockMatch[1].trim(), display: true };
        }

        if (bracketMatch) {
            return { formula: bracketMatch[1].trim(), display: true };
        }

        if (inlineMatch) {
            return { formula: inlineMatch[1].trim(), display: false };
        }

        return { formula: selected, display: false };
    }

    function openLatexDialog(editor) {
        const initialData = latexFromSelection(editor);

        editor.windowManager.open({
            title: 'Formula LaTeX',
            body: {
                type: 'panel',
                items: [
                    {
                        type: 'textarea',
                        name: 'formula',
                        label: 'Formula'
                    },
                    {
                        type: 'checkbox',
                        name: 'display',
                        label: 'Exibir em linha separada'
                    }
                ]
            },
            buttons: [
                {
                    type: 'cancel',
                    text: 'Cancelar'
                },
                {
                    type: 'submit',
                    text: 'Inserir',
                    primary: true
                }
            ],
            initialData: initialData,
            onSubmit: function (api) {
                const data = api.getData();
                const formula = String(data.formula || '').trim();

                if (!formula) {
                    api.close();
                    return;
                }

                const safeFormula = escapeHtml(formula);
                const html = data.display
                    ? '<p>$$' + safeFormula + '$$</p>'
                    : '\\(' + safeFormula + '\\)';

                editor.insertContent(html);
                editor.save();
                api.close();
            }
        });
    }

    function codeFromSelection(editor) {
        return editor.selection.getContent({ format: 'text' });
    }

    function openCodeDialog(editor) {
        editor.windowManager.open({
            title: 'Bloco de codigo',
            body: {
                type: 'panel',
                items: [
                    {
                        type: 'textarea',
                        name: 'code',
                        label: 'Cole o codigo aqui'
                    }
                ]
            },
            buttons: [
                {
                    type: 'cancel',
                    text: 'Cancelar'
                },
                {
                    type: 'submit',
                    text: 'Inserir',
                    primary: true
                }
            ],
            initialData: {
                code: codeFromSelection(editor)
            },
            onSubmit: function (api) {
                const data = api.getData();
                const code = String(data.code || '').replace(/\r\n?/g, '\n');

                if (!code.trim()) {
                    api.close();
                    return;
                }

                editor.insertContent('<pre><code>' + escapeHtml(code) + '</code></pre><p></p>');
                editor.save();
                api.close();
            }
        });
    }

    function latexDocumentToHtml(content) {
        let text = String(content || '').replace(/\r\n?/g, '\n').trim();

        if (/<[a-z][\s\S]*>/i.test(text)) {
            const template = document.createElement('template');
            template.innerHTML = text;
            text = template.content.textContent || text;
        }

        if (!/\\documentclass|\\begin\{document\}|\\section\*?\{|\\\[/.test(text)) {
            return '';
        }

        const beginDocument = text.indexOf('\\begin{document}');
        if (beginDocument !== -1) {
            text = text.slice(beginDocument + '\\begin{document}'.length);
        }

        text = text
            .replace(/\\end\{document\}[\s\S]*$/g, '')
            .replace(/\\usepackage(?:\[[^\]]*])?\{[^}]+}/g, '')
            .replace(/\\documentclass(?:\[[^\]]*])?\{[^}]+}/g, '')
            .replace(/\\begin\{(?:center|figure|table)\}/g, '\n\n')
            .replace(/\\end\{(?:center|figure|table)\}/g, '\n\n')
            .replace(/\\author\{[^}]*}/g, '')
            .replace(/\\date\{[^}]*}/g, '')
            .replace(/\\maketitle/g, '')
            .replace(/\\title\{([\s\S]*?)}/g, function (match, title) {
                return '\n\\section*{' + title.replace(/\\\\/g, ' - ').trim() + '}\n';
            })
            .replace(/\\newpage/g, '\n\n---PAGE---\n\n')
            .replace(/\\section\*?\{([\s\S]*?)}/g, '\n\n## $1\n\n')
            .replace(/\\subsection\*?\{([\s\S]*?)}/g, '\n\n### $1\n\n')
            .replace(/\\\[((?:.|\n)*?)\\\]/g, function (match, formula) {
                return '\n\n$$' + formula.trim() + '$$\n\n';
            })
            .replace(/\\begin\{equation\*?}([\s\S]*?)\\end\{equation\*?}/g, function (match, formula) {
                return '\n\n$$' + formula.trim() + '$$\n\n';
            })
            .replace(/\\begin\{align\*?}([\s\S]*?)\\end\{align\*?}/g, function (match, formula) {
                return '\n\n$$\\begin{aligned}' + formula.trim() + '\\end{aligned}$$\n\n';
            })
            .replace(/\\begin\{(?:tikzpicture|axis)\}[\s\S]*?\\end\{(?:tikzpicture|axis)\}/g, function (match) {
                return '\n\n```latex\n' + match.trim() + '\n```\n\n';
            })
            .replace(/\\includegraphics(?:\[[^\]]*])?\{([^}]+)}/g, function (match, imagePath) {
                return '\n\n[Imagem do LaTeX: ' + imagePath.trim() + ']\n\n';
            })
            .replace(/\\caption\{([^}]+)}/g, '\n\nLegenda: $1\n\n')
            .replace(/\\begin\{(?:itemize|enumerate)\}/g, '\n\n')
            .replace(/\\end\{(?:itemize|enumerate)\}/g, '\n\n')
            .replace(/\\textbf\{([^}]+)}/g, '<strong>$1</strong>')
            .replace(/\\emph\{([^}]+)}/g, '<em>$1</em>')
            .replace(/\\item\s+/g, '\n- ');

        const blocks = text.split(/\n{2,}/).map(function (block) {
            return block.trim();
        }).filter(Boolean);

        return blocks.map(function (block) {
            if (block === '---PAGE---') {
                return '<hr>';
            }

            if (block.startsWith('## ')) {
                return '<h2>' + escapeHtml(block.slice(3).trim()) + '</h2>';
            }

            if (block.startsWith('### ')) {
                return '<h3>' + escapeHtml(block.slice(4).trim()) + '</h3>';
            }

            if (/^\$\$[\s\S]*\$\$$/.test(block)) {
                return '<p>' + escapeHtml(block) + '</p>';
            }

            if (/^```latex[\s\S]*```$/.test(block)) {
                return '<blockquote><p>Grafico LaTeX/TikZ detectado. Exporte este grafico como imagem e insira pelo botao de imagem do editor.</p><p>' + escapeHtml(block.replace(/^```latex|```$/g, '').trim()).replace(/\n/g, '<br>') + '</p></blockquote>';
            }

            if (/^- /m.test(block)) {
                const items = block.split(/\n/).filter(function (line) {
                    return line.trim().startsWith('- ');
                }).map(function (line) {
                    return '<li>' + escapeHtml(line.trim().slice(2)) + '</li>';
                }).join('');

                return '<ul>' + items + '</ul>';
            }

            return '<p>' + escapeHtml(block).replace(/\n/g, '<br>') + '</p>';
        }).join('');
    }

    tinymce.init({
        selector: 'textarea[data-tinymce]',
        language: 'pt_BR',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce@7/langs/pt_BR.js',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        menubar: false,
        height: 420,
        min_height: 260,
        autoresize_bottom_margin: 24,
        convert_urls: false,
        relative_urls: false,
        remove_script_host: false,
        entity_encoding: 'raw',
        plugins: 'advlist autoresize charmap code fullscreen image link lists media preview searchreplace table visualblocks wordcount',
        toolbar: [
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
            'alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | lineheight',
            'styles | link image media table latex codeblock | blockquote hr removeformat | preview code fullscreen'
        ].join(' | '),
        block_formats: 'Paragrafo=p; Titulo=h2; Subtitulo=h3; Destaque=h4; Citacao=blockquote',
        font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
        line_height_formats: '1.0 1.08 1.15 1.3 1.5 1.65 1.75 2 2.5 3',
        style_formats: [
            { title: 'Texto normal', inline: 'span', remove: 'all' },
            { title: 'Letras abertas', inline: 'span', styles: { letterSpacing: '0.08em' } },
            { title: 'Letras bem abertas', inline: 'span', styles: { letterSpacing: '0.16em' } },
            { title: 'Marcador suave', inline: 'span', styles: { backgroundColor: '#fef3c7' } },
            { title: 'Texto verde educacao', inline: 'span', styles: { color: '#15803d' } }
        ],
        image_advtab: true,
        image_caption: true,
        image_title: true,
        automatic_uploads: true,
        images_file_types: 'jpg,jpeg,png,webp,gif',
        file_picker_types: 'image',
        paste_data_images: true,
        file_picker_callback: function (callback, value, meta) {
            if (meta.filetype !== 'image') {
                return;
            }

            const input = document.createElement('input');
            input.type = 'file';
            input.accept = imageTypes.join(',');

            input.addEventListener('change', function () {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                uploadImage(file, file.name)
                    .then(function (location) {
                        callback(location, {
                            title: file.name,
                            alt: file.name
                        });
                    })
                    .catch(function (message) {
                        window.alert(message);
                    });
            });

            input.click();
        },
        content_style: [
            'body{font-family:Inter,Arial,sans-serif;font-size:16px;line-height:1.65;color:#111827;}',
            'img{max-width:100%;height:auto;}',
            'blockquote{border-left:4px solid #c9181f;margin:1rem 0;padding:.5rem 1rem;background:#f8fafc;}',
            'pre{margin:1rem 0;padding:1rem;overflow:auto;border-radius:8px;background:#111827;color:#f9fafb;font-family:Consolas,Monaco,"Courier New",monospace;font-size:14px;line-height:1.55;white-space:pre;}',
            'code{font-family:Consolas,Monaco,"Courier New",monospace;}',
            'table{border-collapse:collapse;width:100%;}',
            'td,th{border:1px solid #d1d5db;padding:.5rem;}'
        ].join(''),
        paste_preprocess: function (plugin, args) {
            const latexHtml = latexDocumentToHtml(args.content);

            if (latexHtml) {
                args.content = latexHtml;
            }
        },
        images_upload_handler: function (blobInfo) {
            return uploadImage(blobInfo.blob(), blobInfo.filename());
        },
        setup: function (editor) {
            editor.ui.registry.addButton('latex', {
                text: 'LaTeX',
                tooltip: 'Inserir ou editar formula LaTeX',
                onAction: function () {
                    openLatexDialog(editor);
                }
            });

            editor.ui.registry.addButton('codeblock', {
                text: 'Codigo',
                tooltip: 'Colar codigo preservando espacos e quebras de linha',
                onAction: function () {
                    openCodeDialog(editor);
                }
            });

            editor.on('change keyup blur', function () {
                editor.save();
            });
        }
    }).catch(function (error) {
        console.error('Falha ao iniciar o TinyMCE:', error);

        fields.forEach(function (field) {
            field.style.display = '';
            field.removeAttribute('aria-hidden');
        });
    });
})();
