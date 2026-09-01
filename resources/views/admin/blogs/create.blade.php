@extends('layouts.master')

@section('title', 'Create Blog Post')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div>
                <h4 class="mb-0">New Blog Post</h4>
                <p class="text-muted mb-0">Write and publish a new article to your blog.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('blogs.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.blogs._form')
    </form>

    <script src="{{ URL::asset('build/libs/@ckeditor/ckeditor5-build-classic/build/ckeditor.js') }}"></script>
    <script>
        let blogEditorInstance = null;

        document.addEventListener('DOMContentLoaded', function () {
            const contentTextarea = document.getElementById('ckeditor-blog-content');
            const form = document.querySelector('form[action="{{ route('blogs.store') }}"]');
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');

            const slugify = function (value) {
                return (value || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            };

            if (typeof ClassicEditor !== 'undefined' && contentTextarea) {
                ClassicEditor.create(contentTextarea)
                    .then(function (editor) {
                        blogEditorInstance = editor;
                        editor.ui.view.editable.element.style.height = '320px';
                    })
                    .catch(function (error) {
                        console.error('Error initializing blog editor:', error);
                    });
            }

            if (titleInput && slugInput) {
                if (!slugInput.value) {
                    slugInput.value = slugify(titleInput.value);
                }

                titleInput.addEventListener('input', function () {
                    slugInput.value = slugify(this.value);
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    if (blogEditorInstance) {
                        blogEditorInstance.updateSourceElement();
                    }

                    if (titleInput && slugInput && !slugInput.value) {
                        slugInput.value = slugify(titleInput.value);
                    }
                });
            }
        });

        document.getElementById('featured_image').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('featured_image-preview-img');
            const placeholder = document.getElementById('featured_image-preview-placeholder');

            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
@endsection
