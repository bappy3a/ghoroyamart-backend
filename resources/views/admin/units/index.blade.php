@extends('layouts.master')

@section('title', 'Units')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Units</h4>
                    <p class="text-muted mb-0">Maintain measurement units for products.</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Unit
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
                            <th>Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($units as $index => $unit)
                            <tr>
                                <td>{{ $units->firstItem() + $index }}</td>
                                <td class="fw-semibold">{{ $unit->name }}</td>
                                <td class="text-end">
                                    <button
                                        class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUnitModal"
                                        data-unit-name="{{ $unit->name }}"
                                        data-update-url="{{ route('units.update', $unit) }}"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    No units yet. Use the button above to add one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $units->links() }}
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createUnitModal" tabindex="-1" aria-labelledby="createUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('units.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createUnitModalLabel">Create Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $createHasError = $errors->has('name') && old('_method') !== 'PUT';
                        @endphp
                        <div class="mb-3">
                            <label for="unit-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control {{ $createHasError ? 'is-invalid' : '' }}"
                                id="unit-name"
                                name="name"
                                value="{{ old('_method') === 'PUT' ? '' : old('name') }}"
                                placeholder="e.g. Kilogram"
                                required
                            >
                            @if($createHasError)
                                <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                            @endif
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editUnitForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUnitModalLabel">Edit Unit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $editHasError = $errors->has('name') && old('_method') === 'PUT';
                        @endphp
                        <div class="mb-3">
                            <label for="edit-unit-name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control {{ $editHasError ? 'is-invalid' : '' }}"
                                id="edit-unit-name"
                                name="name"
                                required
                            >
                            @if($editHasError)
                                <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                            @endif
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
            const editModal = document.getElementById('editUnitModal');
            const editForm = document.getElementById('editUnitForm');
            const editNameInput = document.getElementById('edit-unit-name');
            const createModalElement = document.getElementById('createUnitModal');

            editModal?.addEventListener('show.bs.modal', (event) => {
                const triggerButton = event.relatedTarget;
                if (!triggerButton) {
                    return;
                }

                const unitName = triggerButton.getAttribute('data-unit-name') ?? '';
                const updateUrl = triggerButton.getAttribute('data-update-url') ?? '';

                editNameInput.value = unitName;
                editForm.action = updateUrl;
            });

            const modalToOpen = @json(session('unit_modal'));
            const editRouteFromSession = @json(session('unit_edit_route'));
            const oldName = @json(old('name'));
            const bootstrapGlobal = window.bootstrap;

            if (bootstrapGlobal) {
                if (modalToOpen === 'create' && createModalElement) {
                    const createModal = new bootstrapGlobal.Modal(createModalElement);
                    createModal.show();
                }

                if (modalToOpen === 'edit' && editModal) {
                    if (editRouteFromSession) {
                        editForm.action = editRouteFromSession;
                    }
                    if (oldName) {
                        editNameInput.value = oldName;
                    }
                    const modal = new bootstrapGlobal.Modal(editModal);
                    modal.show();
                }
            }
        });
    </script>
@endsection


