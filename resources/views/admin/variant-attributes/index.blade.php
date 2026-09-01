@extends('layouts.master')

@section('title', 'Variant Attributes')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Variant Attributes</h4>
                    <p class="text-muted mb-0">Create reusable options like Size, Color, Storage, RAM, or Material.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAttributeModal">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Attribute
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width: 80px;">#</th>
                            <th>Attribute</th>
                            <th>Values</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attributes as $index => $attribute)
                            <tr>
                                <td>{{ $attributes->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $attribute->name }}</div>
                                    <small class="text-muted">{{ $attribute->slug }}</small>
                                </td>
                                <td>
                                    @forelse($attribute->values as $value)
                                        <span class="badge bg-light text-body border me-1 mb-1">
                                            @if($value->color_code)
                                                <span class="d-inline-block rounded-circle align-middle me-1 border" style="width: 12px; height: 12px; background: {{ $value->color_code }};"></span>
                                            @endif
                                            {{ $value->value }}
                                        </span>
                                    @empty
                                        <span class="text-muted">No values yet</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge {{ $attribute->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $attribute->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAttributeModal"
                                        data-attribute-name="{{ $attribute->name }}"
                                        data-attribute-values="{{ $attribute->values->pluck('value')->implode(', ') }}"
                                        data-attribute-colors='@json($attribute->values->map(fn ($value) => ['value' => $value->value, 'color_code' => $value->color_code ?: '#000000'])->values())'
                                        data-attribute-active="{{ $attribute->is_active ? 1 : 0 }}"
                                        data-update-url="{{ route('variant-attributes.update', $attribute) }}"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('variant-attributes.destroy', $attribute) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this variant attribute?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No variant attributes yet. Create Size, Color, Storage, RAM, or any custom attribute.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $attributes->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="createAttributeModal" tabindex="-1" aria-labelledby="createAttributeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('variant-attributes.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAttributeModalLabel">Create Variant Attribute</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $createHasErrors = $errors->any() && old('_method') !== 'PUT';
                        @endphp
                        <div class="mb-3">
                            <label for="attribute-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ $errors->has('name') && $createHasErrors ? 'is-invalid' : '' }}" id="attribute-name" name="name" value="{{ $createHasErrors ? old('name') : '' }}" placeholder="e.g. Size" required>
                            @if($errors->has('name') && $createHasErrors)
                                <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="attribute-values" class="form-label">Values</label>
                            <textarea class="form-control" id="attribute-values" name="values" rows="4" placeholder="S, M, L">{{ $createHasErrors ? old('values') : '' }}</textarea>
                            <div class="form-text">Separate values with commas or new lines.</div>
                        </div>
                        <div class="mb-3 color-value-section" data-name-input="attribute-name">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Color Options</label>
                                <button type="button" class="btn btn-sm btn-light add-color-row" data-target="create-color-rows">
                                    <i class="ri-add-line align-middle me-1"></i>
                                    Add Color
                                </button>
                            </div>
                            <div id="create-color-rows" class="vstack gap-2"></div>
                            <div class="form-text">Use this when the attribute is Color.</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="attribute-active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="attribute-active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editAttributeModal" tabindex="-1" aria-labelledby="editAttributeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editAttributeForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAttributeModalLabel">Edit Variant Attribute</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $editHasErrors = $errors->any() && old('_method') === 'PUT';
                        @endphp
                        <div class="mb-3">
                            <label for="edit-attribute-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ $errors->has('name') && $editHasErrors ? 'is-invalid' : '' }}" id="edit-attribute-name" name="name" required>
                            @if($errors->has('name') && $editHasErrors)
                                <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <label for="edit-attribute-values" class="form-label">Values</label>
                            <textarea class="form-control" id="edit-attribute-values" name="values" rows="4"></textarea>
                            <div class="form-text">Existing values stay available; new values are added automatically.</div>
                        </div>
                        <div class="mb-3 color-value-section" data-name-input="edit-attribute-name">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Color Options</label>
                                <button type="button" class="btn btn-sm btn-light add-color-row" data-target="edit-color-rows">
                                    <i class="ri-add-line align-middle me-1"></i>
                                    Add Color
                                </button>
                            </div>
                            <div id="edit-color-rows" class="vstack gap-2"></div>
                            <div class="form-text">Use this when the attribute is Color.</div>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-attribute-active" name="is_active" value="1">
                            <label class="form-check-label" for="edit-attribute-active">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editAttributeModal');
            const editForm = document.getElementById('editAttributeForm');
            const editName = document.getElementById('edit-attribute-name');
            const editValues = document.getElementById('edit-attribute-values');
            const editActive = document.getElementById('edit-attribute-active');
            const createModal = document.getElementById('createAttributeModal');
            const createName = document.getElementById('attribute-name');
            const createColorRows = document.getElementById('create-color-rows');
            const editColorRows = document.getElementById('edit-color-rows');

            function colorRowHtml(prefix, index, value = '', colorCode = '#000000') {
                return `
                    <div class="input-group color-row">
                        <input type="text" class="form-control" name="color_values[${index}][value]" value="${escapeHtml(value)}" placeholder="Color name">
                        <input type="color" class="form-control form-control-color" name="color_values[${index}][color_code]" value="${escapeHtml(colorCode || '#000000')}" title="Choose color">
                        <button type="button" class="btn btn-outline-danger remove-color-row">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                `;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderColorRows(container, colors = []) {
                if (!container) {
                    return;
                }

                container.innerHTML = colors.map((color, index) => colorRowHtml(container.id, index, color.value, color.color_code)).join('');
            }

            function addColorRow(container) {
                if (!container) {
                    return;
                }

                const index = container.querySelectorAll('.color-row').length;
                container.insertAdjacentHTML('beforeend', colorRowHtml(container.id, index));
            }

            document.querySelectorAll('.add-color-row').forEach((button) => {
                button.addEventListener('click', () => {
                    addColorRow(document.getElementById(button.dataset.target));
                });
            });

            document.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.remove-color-row');

                if (removeButton) {
                    removeButton.closest('.color-row')?.remove();
                }
            });

            editModal?.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;

                editName.value = button?.getAttribute('data-attribute-name') ?? '';
                editValues.value = button?.getAttribute('data-attribute-values') ?? '';
                editActive.checked = button?.getAttribute('data-attribute-active') === '1';
                editForm.action = button?.getAttribute('data-update-url') ?? '';
                renderColorRows(editColorRows, JSON.parse(button?.getAttribute('data-attribute-colors') || '[]'));
            });

            const shouldOpenCreate = @json($errors->any() && old('_method') !== 'PUT');
            const shouldOpenEdit = @json($errors->any() && old('_method') === 'PUT');
            const oldName = @json(old('name'));
            const oldValues = @json(old('values'));

            if (window.bootstrap && shouldOpenCreate && createModal) {
                renderColorRows(createColorRows, @json(old('color_values', [])));
                new window.bootstrap.Modal(createModal).show();
            }

            if (window.bootstrap && shouldOpenEdit && editModal) {
                editName.value = oldName ?? editName.value;
                editValues.value = oldValues ?? editValues.value;
                renderColorRows(editColorRows, @json(old('color_values', [])));
                new window.bootstrap.Modal(editModal).show();
            }

            createName?.addEventListener('input', () => {
                if (createName.value.trim().toLowerCase() === 'color' && createColorRows && !createColorRows.children.length) {
                    addColorRow(createColorRows);
                }
            });
        });
    </script>
@endsection
