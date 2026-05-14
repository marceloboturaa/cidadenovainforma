(function () {
    const fields = document.querySelectorAll('textarea[data-tinymce]');

    if (!fields.length || typeof tinymce === 'undefined') {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const uploadUrl = document.querySelector('meta[name="tinymce-upload-url"]')?.content || '';

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
        plugins: 'advlist autoresize charmap code fullscreen image link lists media preview searchreplace table visualblocks wordcount',
        toolbar: [
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor',
            'alignleft aligncenter alignright alignjustify | bullist numlist | outdent indent | lineheight',
            'styles | link image media table | blockquote hr removeformat | preview code fullscreen'
        ].join(' | '),
        block_formats: 'Paragrafo=p; Titulo=h2; Subtitulo=h3; Destaque=h4; Citacao=blockquote',
        font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
        line_height_formats: '1 1.15 1.3 1.5 1.75 2 2.5 3',
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
        file_picker_types: 'image media',
        paste_data_images: true,
        content_style: [
            'body{font-family:Inter,Arial,sans-serif;font-size:16px;line-height:1.65;color:#111827;}',
            'img{max-width:100%;height:auto;}',
            'blockquote{border-left:4px solid #c9181f;margin:1rem 0;padding:.5rem 1rem;background:#f8fafc;}',
            'table{border-collapse:collapse;width:100%;}',
            'td,th{border:1px solid #d1d5db;padding:.5rem;}'
        ].join(''),
        images_upload_handler: function (blobInfo) {
            return new Promise(function (resolve, reject) {
                if (!uploadUrl) {
                    reject('Upload de imagem nao configurado.');
                    return;
                }

                const formData = new FormData();
                formData.append('_token', token);
                formData.append('file', blobInfo.blob(), blobInfo.filename());

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
        },
        setup: function (editor) {
            editor.on('change keyup blur', function () {
                editor.save();
            });
        }
    });
})();
