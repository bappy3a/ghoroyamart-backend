@extends('layouts.master')

@section('title', 'Create Blog Category')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div>
                <h4 class="mb-0">New Blog Category</h4>
                <p class="text-muted mb-0">Add a new category to organize your blog content.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('blog-categories.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.blog-categories._form')
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            const form = document.querySelector('form[action="{{ route('blog-categories.store') }}"]');

            const slugify = function (value) {
                return (value || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            };

            if (nameInput && slugInput) {
                if (!slugInput.value) {
                    slugInput.value = slugify(nameInput.value);
                }

                nameInput.addEventListener('input', function () {
                    slugInput.value = slugify(this.value);
                });
            }

            if (form) {
                form.addEventListener('submit', function () {
                    if (nameInput && slugInput && !slugInput.value) {
                        slugInput.value = slugify(nameInput.value);
                    }
                });
            }
        });

        document.getElementById('icon').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const preview = document.getElementById('icon-preview-img');
            const placeholder = document.getElementById('icon-preview-placeholder');

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
