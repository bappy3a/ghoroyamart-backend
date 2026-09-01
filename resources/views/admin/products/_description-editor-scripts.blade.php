<style>
    .ck-toolbar .product-description-color-control {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 6px;
    }

    .ck-toolbar .product-description-color-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 30px;
        color: #495057;
        font-size: 16px;
    }

    .ck-toolbar .product-description-color-picker {
        width: 34px;
        height: 30px;
        min-height: 30px;
        padding: 2px;
        cursor: pointer;
    }

    .ck-toolbar .product-description-video-upload {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 6px;
        width: 32px;
        height: 30px;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: #495057;
        cursor: pointer;
    }

    .ck-toolbar .product-description-video-upload:hover {
        background: var(--ck-color-button-on-hover-background, #e6e6e6);
    }

    .ck-content video {
        display: block;
        max-width: 100%;
        height: auto;
    }
</style>

<script>
    (function() {
        if (typeof ClassicEditor === 'undefined' || ClassicEditor.__productDescriptionColorReady) {
            return;
        }

        const originalCreate = ClassicEditor.create.bind(ClassicEditor);
        let editorInstance = null;
        const editorUploadUrl = @json(route('products.create-editor-upload'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.value
            || '';

        class ProductDescriptionUploadAdapter {
            constructor(loader) {
                this.loader = loader;
            }

            upload() {
                return this.loader.file.then(file => new Promise((resolve, reject) => {
                    const data = new FormData();
                    data.append('upload', file);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', editorUploadUrl, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.responseType = 'json';

                    xhr.addEventListener('error', () => reject('Upload failed.'));
                    xhr.addEventListener('abort', () => reject('Upload aborted.'));
                    xhr.addEventListener('load', () => {
                        const response = xhr.response;

                        if (xhr.status < 200 || xhr.status >= 300) {
                            const message = response?.message
                                || response?.errors?.upload?.[0]
                                || 'Upload failed.';
                            reject(message);
                            return;
                        }

                        if (!response || !response.url) {
                            reject('Invalid upload response.');
                            return;
                        }

                        resolve({
                            default: response.url
                        });
                    });

                    if (xhr.upload) {
                        xhr.upload.addEventListener('progress', event => {
                            if (!event.lengthComputable) {
                                return;
                            }

                            this.loader.uploadTotal = event.total;
                            this.loader.uploaded = event.loaded;
                        });
                    }

                    xhr.send(data);
                }));
            }

            abort() {}
        }

        function ProductDescriptionUploadAdapterPlugin(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = loader => {
                return new ProductDescriptionUploadAdapter(loader);
            };
        }

        class ProductDescriptionTextColor {
            constructor(editor) {
                this.editor = editor;
            }

            init() {
                const editor = this.editor;

                editor.model.schema.extend('$text', {
                    allowAttributes: ['textColor', 'backgroundColor']
                });

                editor.conversion.for('upcast').elementToAttribute({
                    view: {
                        name: 'span',
                        styles: {
                            color: /.+/
                        }
                    },
                    model: {
                        key: 'textColor',
                        value: viewElement => viewElement.getStyle('color')
                    }
                });

                editor.conversion.for('downcast').attributeToElement({
                    model: 'textColor',
                    view: (color, { writer }) => writer.createAttributeElement('span', {
                        style: 'color:' + color
                    }, {
                        priority: 7
                    })
                });

                editor.conversion.for('upcast').elementToAttribute({
                    view: {
                        name: 'span',
                        styles: {
                            'background-color': /.+/
                        }
                    },
                    model: {
                        key: 'backgroundColor',
                        value: viewElement => viewElement.getStyle('background-color')
                    }
                });

                editor.conversion.for('downcast').attributeToElement({
                    model: 'backgroundColor',
                    view: (color, { writer }) => writer.createAttributeElement('span', {
                        style: 'background-color:' + color
                    }, {
                        priority: 7
                    })
                });
            }
        }

        function applyColor(editor, attribute, color) {
            editor.model.change(writer => {
                const selection = editor.model.document.selection;

                if (selection.isCollapsed) {
                    writer.setSelectionAttribute(attribute, color);
                    return;
                }

                for (const range of selection.getRanges()) {
                    writer.setAttribute(attribute, color, range);
                }
            });

            editor.editing.view.focus();
        }

        function createColorControl(editor, attribute, label, className, iconClass, defaultColor) {
            const control = document.createElement('span');
            control.className = 'product-description-color-control';

            const icon = document.createElement('i');
            icon.className = 'product-description-color-icon ' + iconClass;
            icon.title = label;
            icon.setAttribute('aria-hidden', 'true');
            control.appendChild(icon);

            const colorPicker = document.createElement('input');
            colorPicker.type = 'color';
            colorPicker.className = 'form-control form-control-color product-description-color-picker ' + className;
            colorPicker.value = defaultColor;
            colorPicker.title = label;
            colorPicker.setAttribute('aria-label', label);
            colorPicker.addEventListener('input', function() {
                applyColor(editor, attribute, colorPicker.value);
            });
            control.appendChild(colorPicker);

            return control;
        }

        function uploadEditorFile(file) {
            const data = new FormData();
            data.append('upload', file);

            return fetch(editorUploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: data
            }).then(async response => {
                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload?.message || payload?.errors?.upload?.[0] || 'Upload failed.');
                }

                if (!payload.url) {
                    throw new Error('Invalid upload response.');
                }

                return payload;
            });
        }

        function insertUploadedVideo(editor, url) {
            editor.model.change(writer => {
                const mediaElement = writer.createElement('media', {
                    url: url
                });
                editor.model.insertContent(mediaElement, editor.model.document.selection);
            });
        }

        function setupVideoUploadControl(editor) {
            const toolbar = editor.ui.view.toolbar && editor.ui.view.toolbar.element;

            if (!toolbar || toolbar.querySelector('.product-description-video-upload')) {
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'product-description-video-upload';
            button.title = 'Upload Video';
            button.setAttribute('aria-label', 'Upload Video');
            button.innerHTML = '<i class="ri-video-upload-line"></i>';

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'video/mp4,video/webm,video/ogg,video/quicktime';
            fileInput.className = 'd-none';

            button.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                const file = fileInput.files && fileInput.files[0];
                fileInput.value = '';

                if (!file) {
                    return;
                }

                button.disabled = true;

                uploadEditorFile(file)
                    .then(payload => {
                        insertUploadedVideo(editor, payload.url);
                        editor.editing.view.focus();
                    })
                    .catch(error => {
                        alert(error.message || 'Video upload failed.');
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
            });

            toolbar.appendChild(button);
            toolbar.appendChild(fileInput);
        }

        function setupColorControls(editor) {
            const toolbar = editor.ui.view.toolbar && editor.ui.view.toolbar.element;

            if (!toolbar || toolbar.querySelector('.product-description-text-color-picker')) {
                return;
            }

            toolbar.appendChild(createColorControl(editor, 'textColor', 'Text Color', 'product-description-text-color-picker', 'ri-font-color', '#212529'));
            toolbar.appendChild(createColorControl(editor, 'backgroundColor', 'Background Color', 'product-description-background-color-picker', 'ri-paint-brush-line', '#fff3cd'));
        }

        ClassicEditor.create = function(element, config) {
            if (element && element.id === 'ckeditor-classic' && editorInstance) {
                return Promise.resolve(editorInstance);
            }

            const editorConfig = Object.assign({}, config || {});
            editorConfig.extraPlugins = [
                ProductDescriptionUploadAdapterPlugin,
                ProductDescriptionTextColor
            ].concat(editorConfig.extraPlugins || []);

            editorConfig.mediaEmbed = Object.assign({}, editorConfig.mediaEmbed || {}, {
                previewsInData: true,
                extraProviders: [
                    {
                        name: 'uploaded-video',
                        url: [
                            /^(https?:\/\/.+\.(mp4|webm|ogg|mov)(\?.*)?)$/i,
                            /^(\/.+\.(mp4|webm|ogg|mov)(\?.*)?)$/i
                        ],
                        html: match => {
                            const url = match[1] || match[0];
                            return (
                                '<div style="position:relative;max-width:100%;">' +
                                    '<video controls playsinline style="width:100%;max-height:480px;" src="' + url + '"></video>' +
                                '</div>'
                            );
                        }
                    }
                ]
            });

            return originalCreate(element, editorConfig).then(function(editor) {
                if (element && element.id === 'ckeditor-classic') {
                    editorInstance = editor;
                    editor.ui.view.editable.element.style.height = '280px';
                    setupColorControls(editor);
                    setupVideoUploadControl(editor);

                    const form = element.closest('form');
                    if (form) {
                        form.addEventListener('submit', function() {
                            editor.updateSourceElement();
                        });
                    }
                }

                return editor;
            });
        };

        ClassicEditor.__productDescriptionColorReady = true;
    })();
</script>
