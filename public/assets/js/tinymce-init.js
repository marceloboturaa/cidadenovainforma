(function () {
    const fields = document.querySelectorAll('textarea[data-tinymce]');

    if (!fields.length || typeof tinymce === 'undefined') {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const uploadUrl = document.querySelector('meta[name="tinymce-upload-url"]')?.content || '';
    const imageTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    let pasteAsPlainText = false;

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

    function htmlToPlainText(html) {
        const template = document.createElement('template');
        template.innerHTML = String(html || '')
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/(?:p|div|h[1-6]|li|tr|blockquote)>/gi, '\n');

        return (template.content.textContent || '')
            .replace(/\u00a0/g, ' ')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function plainTextToParagraphs(text) {
        return String(text || '')
            .replace(/\r\n?/g, '\n')
            .split(/\n{2,}/)
            .map(function (block) {
                const value = block.trim();

                if (!value) {
                    return '';
                }

                return '<p>' + escapeHtml(value).replace(/\n/g, '<br>') + '</p>';
            })
            .join('');
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
                    },
                    {
                        type: 'selectbox',
                        name: 'theme',
                        label: 'Cor de fundo',
                        items: [
                            { text: 'Escuro', value: 'code-theme-dark' },
                            { text: 'Claro', value: 'code-theme-light' },
                            { text: 'Azul', value: 'code-theme-blue' },
                            { text: 'Verde', value: 'code-theme-green' },
                            { text: 'Vinho', value: 'code-theme-wine' }
                        ]
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
                code: codeFromSelection(editor),
                theme: 'code-theme-dark'
            },
            onSubmit: function (api) {
                const data = api.getData();
                const code = String(data.code || '').replace(/\r\n?/g, '\n');
                const allowedThemes = ['code-theme-dark', 'code-theme-light', 'code-theme-blue', 'code-theme-green', 'code-theme-wine'];
                const theme = allowedThemes.includes(data.theme) ? data.theme : 'code-theme-dark';

                if (!code.trim()) {
                    api.close();
                    return;
                }

                editor.insertContent('<pre class="' + theme + '"><code>' + escapeHtml(code) + '</code></pre><p></p>');
                editor.save();
                api.close();
            }
        });
    }

    function openParagraphSpacingDialog(editor) {
        editor.windowManager.open({
            title: 'Espaco entre paragrafos',
            body: {
                type: 'panel',
                items: [
                    {
                        type: 'selectbox',
                        name: 'spacing',
                        label: 'Depois do paragrafo',
                        items: [
                            { text: 'Sem espaco', value: '0' },
                            { text: 'Curto', value: '0.5em' },
                            { text: 'Normal', value: '1em' },
                            { text: 'Padrao da noticia', value: '1.25em' },
                            { text: 'Amplo', value: '1.75em' },
                            { text: 'Bem amplo', value: '2.25em' }
                        ]
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
                    text: 'Aplicar',
                    primary: true
                }
            ],
            initialData: {
                spacing: '1.25em'
            },
            onSubmit: function (api) {
                const data = api.getData();
                const allowedSpacing = ['0', '0.5em', '1em', '1.25em', '1.75em', '2.25em'];
                const spacing = allowedSpacing.includes(data.spacing) ? data.spacing : '1.25em';
                const blocks = editor.selection.getSelectedBlocks();

                blocks.forEach(function (block) {
                    if (/^(P|H2|H3|H4|BLOCKQUOTE|LI)$/i.test(block.nodeName)) {
                        editor.dom.setStyle(block, 'margin-bottom', spacing);
                    }
                });

                editor.save();
                api.close();
            }
        });
    }

    function exerciseHtmlToArticleHtml(content) {
        const html = String(content || '');

        if (!/<(?:html|body|main|section|div)\b/i.test(html) || !/(question-block|solution-block|step-formula|exercise-card)/i.test(html)) {
            return '';
        }

        const template = document.createElement('template');
        template.innerHTML = html;
        const root = template.content;
        const output = [];
        const statement = root.querySelector('.question-block .statement, .statement');
        const steps = root.querySelectorAll('.solution-block .step-row, .step-row');
        const credit = root.querySelector('.solution-credit');
        const reference = root.querySelector('.reference-inline p');

        if (statement) {
            output.push('<h2>Enunciado</h2>');
            output.push('<p>' + escapeHtml(statement.textContent.trim()) + '</p>');
        }

        if (steps.length) {
            output.push('<h2>Resolucao</h2>');
            steps.forEach(function (step) {
                const formula = step.querySelector('.step-formula');
                const text = step.querySelector('.step-text');

                if (formula && formula.textContent.trim()) {
                    output.push('<p>' + escapeHtml(formula.textContent.trim()) + '</p>');
                }

                if (text && text.textContent.trim()) {
                    output.push('<p>' + escapeHtml(text.textContent.trim()) + '</p>');
                }
            });
        }

        if (credit && credit.textContent.trim()) {
            output.push('<p><em>' + escapeHtml(credit.textContent.trim()) + '</em></p>');
        }

        if (reference && reference.textContent.trim()) {
            output.push('<h3>Referencia</h3>');
            output.push('<p>' + escapeHtml(reference.textContent.trim()) + '</p>');
        }

        return output.join('');
    }

    function latexTextToHtml(text) {
        let value = String(text || '').trim();

        value = value
            .replace(/\{\\(?:Huge|huge|LARGE|Large|large|normalsize|small|footnotesize)\s+\\textbf\{([^{}]+)}}/g, '\\textbf{$1}')
            .replace(/\{\\(?:Huge|huge|LARGE|Large|large|normalsize|small|footnotesize)\s+\\emph\{([^{}]+)}}/g, '\\emph{$1}')
            .replace(/\{\\(?:Huge|huge|LARGE|Large|large|normalsize|small|footnotesize)\s+([\s\S]*?)}/g, '$1')
            .replace(/\\noindent\s*/g, '')
            .replace(/\\\\/g, '<br>');

        value = escapeHtml(value);
        value = value
            .replace(/\\textbf\{([^{}]+)}/g, '<strong>$1</strong>')
            .replace(/\\emph\{([^{}]+)}/g, '<em>$1</em>')
            .replace(/\\textit\{([^{}]+)}/g, '<em>$1</em>');

        return value;
    }

    function cleanDisplayFormula(formula) {
        return String(formula || '')
            .replace(/\\textbf\{([^{}]+)}/g, '\\text{$1}')
            .replace(/<\s*\/?\s*(?:strong|b|em|i)\s*>/gi, '')
            .replace(/\\hline(?=[A-Za-z0-9(])/g, '\\hline ')
            .trim();
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
            .replace(/\\vspace\*?\{[^}]+}/g, '\n\n')
            .replace(/\\hspace\*?\{[^}]+}/g, ' ')
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
                return '\n\n$$' + cleanDisplayFormula(formula) + '$$\n\n';
            })
            .replace(/\\begin\{equation\*?}([\s\S]*?)\\end\{equation\*?}/g, function (match, formula) {
                return '\n\n$$' + cleanDisplayFormula(formula) + '$$\n\n';
            })
            .replace(/\\begin\{align\*?}([\s\S]*?)\\end\{align\*?}/g, function (match, formula) {
                return '\n\n$$\\begin{aligned}' + cleanDisplayFormula(formula) + '\\end{aligned}$$\n\n';
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
            .replace(/^[ \t]*\\item\s+/gm, '- ');

        const blocks = text.split(/\n{2,}/).map(function (block) {
            return block.trim();
        }).filter(Boolean);

        return blocks.map(function (block) {
            if (block === '---PAGE---') {
                return '<hr>';
            }

            if (block.startsWith('## ')) {
                return '<h2>' + latexTextToHtml(block.slice(3).trim()) + '</h2>';
            }

            if (block.startsWith('### ')) {
                return '<h3>' + latexTextToHtml(block.slice(4).trim()) + '</h3>';
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
                    return '<li>' + latexTextToHtml(line.trim().slice(2)) + '</li>';
                }).join('');

                return '<ul>' + items + '</ul>';
            }

            return '<p>' + latexTextToHtml(block).replace(/\n/g, '<br>') + '</p>';
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
            'alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | lineheight paragraphspace',
            'styles | link image media table latex codeblock cleanpaste | blockquote hr removeformat | preview code fullscreen'
        ].join(' | '),
        block_formats: 'Paragrafo=p; Titulo=h2; Subtitulo=h3; Destaque=h4; Citacao=blockquote',
        font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
        line_height_formats: '1.0 1.08 1.15 1.3 1.5 1.65 1.75 2 2.5 3',
        style_formats: [
            { title: 'Texto normal', inline: 'span', remove: 'all' },
            {
                title: 'Espaco entre paragrafos',
                items: [
                    { title: 'Sem espaco depois', block: 'p', styles: { marginBottom: '0' } },
                    { title: 'Curto depois', block: 'p', styles: { marginBottom: '0.5em' } },
                    { title: 'Normal depois', block: 'p', styles: { marginBottom: '1em' } },
                    { title: 'Amplo depois', block: 'p', styles: { marginBottom: '1.75em' } }
                ]
            },
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
            'figure{margin:1rem 0;}',
            'figure img{display:block;margin:0;}',
            'figcaption{margin-top:.45rem;color:#6b7280;font-size:13px;line-height:1.45;text-align:center;}',
            'blockquote{border-left:4px solid #c9181f;margin:1rem 0;padding:.5rem 1rem;background:#f8fafc;}',
            'pre{margin:1rem 0;padding:1rem;overflow:auto;border-radius:8px;background:#111827;color:#f9fafb;font-family:Consolas,Monaco,"Courier New",monospace;font-size:14px;line-height:1.55;white-space:pre;}',
            'pre.code-theme-light{background:#f8fafc;color:#111827;border:1px solid #d1d5db;}',
            'pre.code-theme-blue{background:#0f172a;color:#dbeafe;}',
            'pre.code-theme-green{background:#052e16;color:#dcfce7;}',
            'pre.code-theme-wine{background:#450a0a;color:#fee2e2;}',
            'code{font-family:Consolas,Monaco,"Courier New",monospace;}',
            'table{border-collapse:collapse;width:100%;}',
            'td,th{border:1px solid #d1d5db;padding:.5rem;}'
        ].join(''),
        paste_preprocess: function (plugin, args) {
            if (pasteAsPlainText) {
                args.content = plainTextToParagraphs(htmlToPlainText(args.content));
                return;
            }

            const exerciseHtml = exerciseHtmlToArticleHtml(args.content);

            if (exerciseHtml) {
                args.content = exerciseHtml;
                return;
            }

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

            editor.ui.registry.addToggleButton('cleanpaste', {
                text: 'Colar limpo',
                tooltip: 'Quando ativo, remove toda formatacao do texto colado',
                onAction: function (api) {
                    pasteAsPlainText = !pasteAsPlainText;
                    api.setActive(pasteAsPlainText);
                },
                onSetup: function (api) {
                    api.setActive(pasteAsPlainText);

                    return function () {};
                }
            });

            editor.ui.registry.addButton('paragraphspace', {
                text: 'Espaco P',
                tooltip: 'Ajustar espaco depois do paragrafo',
                onAction: function () {
                    openParagraphSpacingDialog(editor);
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
