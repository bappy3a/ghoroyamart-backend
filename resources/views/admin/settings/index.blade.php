@extends('layouts.master')

@section('title', 'Website Settings')

@section('css')
    <style>
        .menu-builder-item,
        .menu-builder-child {
            background: #fff;
        }

        .menu-builder-child {
            border-left: 3px solid var(--vz-primary);
        }

        .menu-builder-source {
            max-height: 260px;
            overflow-y: auto;
        }

        .menu-builder-row {
            border-left: 4px solid transparent;
            cursor: grab;
        }

        .menu-builder-row:active {
            cursor: grabbing;
        }

        .menu-builder-row[data-depth="1"] {
            margin-left: 24px;
            border-left-color: var(--vz-primary);
        }

        .menu-builder-row[data-depth="2"],
        .menu-builder-row[data-depth="3"] {
            margin-left: 48px;
            border-left-color: var(--vz-info);
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Website Settings</h4>
                    <p class="text-muted mb-0">Manage your website configuration, contact information, and SEO settings.</p>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Please fix the following errors:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="settings-form" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                    @foreach($settings as $group => $groupSettings)
                        @php
                            $tabId = strtolower(str_replace([' ', '&'], ['-', ''], $group));
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                               data-bs-toggle="tab" 
                               href="#tab-{{ $tabId }}" 
                               role="tab"
                               aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                @if($group === 'General')
                                    <i class="ri-settings-3-line me-1 align-bottom"></i>
                                @elseif($group === 'Menu Settings')
                                    <i class="ri-menu-line me-1 align-bottom"></i>
                                @elseif($group === 'Contact' || $group === 'Contact Us')
                                    <i class="ri-phone-line me-1 align-bottom"></i>
                                @elseif($group === 'Social Media')
                                    <i class="ri-share-line me-1 align-bottom"></i>
                                @elseif($group === 'Image Gallery')
                                    <i class="ri-gallery-line me-1 align-bottom"></i>
                                @elseif($group === 'Payment Methods')
                                    <i class="ri-bank-card-line me-1 align-bottom"></i>
                                @elseif($group === 'SEO & Meta')
                                    <i class="ri-search-line me-1 align-bottom"></i>
                                @elseif($group === 'Delivery' || $group === 'Delivery Charge')
                                    <i class="ri-truck-line me-1 align-bottom"></i>
                                @endif
                                {{ $group }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    @foreach($settings as $group => $groupSettings)
                        @php
                            $tabId = strtolower(str_replace([' ', '&'], ['-', ''], $group));
                        @endphp
                        <div class="tab-pane {{ $loop->first ? 'active' : '' }}" 
                             id="tab-{{ $tabId }}" 
                             role="tabpanel">
                            <div class="row g-4">
                                @foreach($groupSettings as $setting)
                                    @if($setting->type === 'menu')
                                        @php
                                            $frontendMenuItems = json_decode(old($setting->key, $setting->value), true);

                                            if (!is_array($frontendMenuItems)) {
                                                $frontendMenuItems = frontend_menu_defaults();
                                            }
                                        @endphp

                                        <div class="col-12">
                                            <div class="mb-3 menu-builder" data-menu-builder>
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>

                                                <textarea
                                                    class="d-none @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    data-menu-json
                                                >@json($frontendMenuItems)</textarea>

                                                <div class="row g-4">
                                                    <div class="col-lg-4">
                                                        <div class="border rounded p-3 h-100">
                                                            <h6 class="mb-3">Add Menu Items</h6>

                                                            <div class="accordion" id="menuSourceAccordion">
                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="menuCustomHeading">
                                                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#menuCustomPanel" aria-expanded="true" aria-controls="menuCustomPanel">
                                                                            Custom Link
                                                                        </button>
                                                                    </h2>
                                                                    <div id="menuCustomPanel" class="accordion-collapse collapse show" aria-labelledby="menuCustomHeading" data-bs-parent="#menuSourceAccordion">
                                                                        <div class="accordion-body">
                                                                            <div class="mb-2">
                                                                                <label class="form-label">Link Text</label>
                                                                                <input type="text" class="form-control" data-custom-label placeholder="Menu label">
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label">URL</label>
                                                                                <input type="text" class="form-control" data-custom-url placeholder="/products or https://example.com">
                                                                            </div>
                                                                            <button type="button" class="btn btn-soft-primary w-100" data-add-custom-link>
                                                                                <i class="ri-add-line align-middle me-1"></i>
                                                                                Add To Menu
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="menuCoreHeading">
                                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menuCorePanel" aria-expanded="false" aria-controls="menuCorePanel">
                                                                            Site Pages
                                                                        </button>
                                                                    </h2>
                                                                    <div id="menuCorePanel" class="accordion-collapse collapse" aria-labelledby="menuCoreHeading" data-bs-parent="#menuSourceAccordion">
                                                                        <div class="accordion-body menu-builder-source">
                                                                            @foreach(($menuSources['core'] ?? []) as $sourceItem)
                                                                                <label class="form-check mb-2">
                                                                                    <input class="form-check-input" type="checkbox" data-source-item data-source-label="{{ $sourceItem['label'] }}" data-source-url="{{ $sourceItem['url'] }}" data-source-type="core">
                                                                                    <span class="form-check-label">{{ $sourceItem['label'] }}</span>
                                                                                </label>
                                                                            @endforeach

                                                                            <button type="button" class="btn btn-soft-primary w-100 mt-2" data-add-selected-source>
                                                                                <i class="ri-add-line align-middle me-1"></i>
                                                                                Add Selected
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <div class="accordion-item">
                                                                    <h2 class="accordion-header" id="menuCategoryHeading">
                                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menuCategoryPanel" aria-expanded="false" aria-controls="menuCategoryPanel">
                                                                            Categories
                                                                        </button>
                                                                    </h2>
                                                                    <div id="menuCategoryPanel" class="accordion-collapse collapse" aria-labelledby="menuCategoryHeading" data-bs-parent="#menuSourceAccordion">
                                                                        <div class="accordion-body menu-builder-source">
                                                                            @forelse(($menuSources['categories'] ?? []) as $sourceItem)
                                                                                <label class="form-check mb-2">
                                                                                    <input class="form-check-input" type="checkbox" data-source-item data-source-label="{{ $sourceItem['label'] }}" data-source-url="{{ $sourceItem['url'] }}" data-source-type="category">
                                                                                    <span class="form-check-label">{{ $sourceItem['label'] }}</span>
                                                                                </label>
                                                                            @empty
                                                                                <p class="text-muted mb-0">No active categories found.</p>
                                                                            @endforelse

                                                                            @if(!empty($menuSources['categories']))
                                                                                <button type="button" class="btn btn-soft-primary w-100 mt-2" data-add-selected-source>
                                                                                    <i class="ri-add-line align-middle me-1"></i>
                                                                                    Add Selected
                                                                                </button>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-8">
                                                        <div class="border rounded p-3">
                                                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                                                <div>
                                                                    <h6 class="mb-1">Menu Structure</h6>
                                                                    <p class="text-muted mb-0 small">Drag items to reorder. Set parent item to create dropdown menus.</p>
                                                                </div>
                                                                <button type="button" class="btn btn-soft-secondary" data-add-blank-item>
                                                                    <i class="ri-add-line align-middle me-1"></i>
                                                                    Blank Item
                                                                </button>
                                                            </div>

                                                            <div data-menu-items></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                @error($setting->key)
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @elseif($setting->type === 'images')
                                        @php
                                            $galleryImages = json_decode($setting->value ?: '[]', true);
                                            if (!is_array($galleryImages)) {
                                                $galleryImages = [];
                                            }

                                            $galleryExistingKey = str_ends_with($setting->key, '_images')
                                                ? preg_replace('/_images$/', '_existing', $setting->key)
                                                : $setting->key . '_existing';
                                        @endphp
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}[]"
                                                    accept="image/*"
                                                    multiple
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                @error($setting->key . '.*')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror

                                                @if(count($galleryImages))
                                                    <div class="row g-3 mt-1">
                                                        @foreach($galleryImages as $img)
                                                            <div class="col-md-3 settings-gallery-image">
                                                                <input type="hidden" name="{{ $galleryExistingKey }}[]" value="{{ $img }}">
                                                                <div class="border rounded p-2 h-100">
                                                                    <img
                                                                        src="{{ api_asset($img) }}"
                                                                        class="img-thumbnail w-100"
                                                                        style="height:100px; object-fit:cover;"
                                                                        alt="{{ $setting->label }}"
                                                                    >
                                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2 remove-settings-gallery-image">
                                                                        Delete
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="col-md-6">
                                            @if($setting->type === 'textarea')
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <textarea
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    rows="3"
                                                >{{ old($setting->key, $setting->value) }}</textarea>
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @elseif($setting->type === 'image')
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="file"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    accept="image/*"
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                @if($setting->value)
                                                    <div class="mt-2">
                                                        <img 
                                                            src="{{ api_asset($setting->value) }}"
                                                            alt="{{ $setting->label }}"
                                                            class="img-thumbnail"
                                                            style="max-height: 100px;"
                                                        >
                                                        <div class="form-text">Current image</div>
                                                    </div>
                                                @endif
                                            </div>
                                            @elseif($setting->type === 'url')
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="url"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    value="{{ old($setting->key, $setting->value) }}"
                                                    placeholder="https://example.com"
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @elseif($setting->type === 'email')
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="email"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    value="{{ old($setting->key, $setting->value) }}"
                                                    placeholder="email@example.com"
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @elseif($setting->type === 'number')
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="number"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    value="{{ old($setting->key, $setting->value) }}"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @else
                                            <div class="mb-3">
                                                <label for="{{ $setting->key }}" class="form-label">
                                                    {{ $setting->label }}
                                                    @if($setting->description)
                                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                                    @endif
                                                </label>
                                                <input
                                                    type="text"
                                                    class="form-control @error($setting->key) is-invalid @enderror"
                                                    id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}"
                                                    value="{{ old($setting->key, $setting->value) }}"
                                                >
                                                @error($setting->key)
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-transparent border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="ri-save-line align-middle me-1"></i>
                        Save All Settings
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="deleteSettingsGalleryImageModal" tabindex="-1" aria-labelledby="deleteSettingsGalleryImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSettingsGalleryImageModalLabel">Delete Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this image?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmSettingsGalleryImageDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let pendingGalleryImage = null;

            document.querySelectorAll('.remove-settings-gallery-image').forEach(function (button) {
                button.addEventListener('click', function () {
                    pendingGalleryImage = button.closest('.settings-gallery-image');
                    const modal = document.getElementById('deleteSettingsGalleryImageModal');
                    if (modal && window.bootstrap) {
                        window.bootstrap.Modal.getOrCreateInstance(modal).show();
                    } else if (pendingGalleryImage) {
                        pendingGalleryImage.remove();
                        pendingGalleryImage = null;
                    }
                });
            });

            document.getElementById('confirmSettingsGalleryImageDelete')?.addEventListener('click', function () {
                if (pendingGalleryImage) {
                    pendingGalleryImage.remove();
                    pendingGalleryImage = null;
                }

                const modal = document.getElementById('deleteSettingsGalleryImageModal');
                if (modal && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modal).hide();
                }
            });

            const form = document.getElementById('settings-form');
            const builder = document.querySelector('[data-menu-builder]');

            if (!form || !builder) {
                return;
            }

            const itemsWrapper = builder.querySelector('[data-menu-items]');
            const jsonField = builder.querySelector('[data-menu-json]');
            let menuItems = [];

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function uniqueId() {
                return 'menu-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
            }

            function isChecked(value) {
                return value === true || value === 'true' || value === 1 || value === '1' || value === undefined;
            }

            function renderTargetOptions(target) {
                const selectedTarget = target === '_blank' ? '_blank' : '_self';

                return `
                    <option value="_self" ${selectedTarget === '_self' ? 'selected' : ''}>Same Tab</option>
                    <option value="_blank" ${selectedTarget === '_blank' ? 'selected' : ''}>New Tab</option>
                `;
            }

            function flattenSavedItems(items, parentId = null, output = []) {
                if (!Array.isArray(items)) {
                    return output;
                }

                items.forEach(function (item) {
                    if (!item || typeof item !== 'object') {
                        return;
                    }

                    const id = item.id || uniqueId();
                    output.push({
                        id,
                        parent_id: item.parent_id ?? parentId,
                        label: item.label ?? '',
                        url: item.url ?? '#',
                        target: item.target === '_blank' ? '_blank' : '_self',
                        is_active: isChecked(item.is_active),
                        sort_order: Number(item.sort_order ?? output.length + 1),
                        type: item.type ?? 'custom',
                    });

                    if (Array.isArray(item.children)) {
                        flattenSavedItems(item.children, id, output);
                    }
                });

                return output;
            }

            function itemDepth(item, seen = []) {
                if (!item.parent_id || seen.includes(item.parent_id)) {
                    return 0;
                }

                const parent = menuItems.find(candidate => candidate.id === item.parent_id);

                if (!parent) {
                    return 0;
                }

                return Math.min(3, 1 + itemDepth(parent, [...seen, item.parent_id]));
            }

            function childIds(parentId, output = []) {
                menuItems.filter(item => item.parent_id === parentId).forEach(function (child) {
                    output.push(child.id);
                    childIds(child.id, output);
                });

                return output;
            }

            function parentOptions(currentItem) {
                const blockedIds = [currentItem.id, ...childIds(currentItem.id)];

                return [
                    '<option value="">No Parent</option>',
                    ...menuItems
                        .filter(item => !blockedIds.includes(item.id))
                        .map(function (item) {
                            const selected = currentItem.parent_id === item.id ? 'selected' : '';
                            const depthPrefix = '&nbsp;'.repeat(itemDepth(item) * 4);

                            return `<option value="${escapeHtml(item.id)}" ${selected}>${depthPrefix}${escapeHtml(item.label || 'Untitled')}</option>`;
                        })
                ].join('');
            }

            function sortItems() {
                menuItems = menuItems
                    .map((item, index) => ({ ...item, sort_order: Number(item.sort_order ?? index + 1) }))
                    .sort((a, b) => a.sort_order - b.sort_order);
            }

            function addMenuItem(item) {
                menuItems.push({
                    id: item.id || uniqueId(),
                    parent_id: item.parent_id ?? null,
                    label: item.label ?? '',
                    url: item.url ?? '#',
                    target: item.target === '_blank' ? '_blank' : '_self',
                    is_active: isChecked(item.is_active),
                    sort_order: menuItems.length + 1,
                    type: item.type ?? 'custom',
                });

                render();
            }

            function moveItem(id, direction) {
                sortItems();

                const index = menuItems.findIndex(item => item.id === id);
                const swapIndex = direction === 'up' ? index - 1 : index + 1;

                if (index < 0 || swapIndex < 0 || swapIndex >= menuItems.length) {
                    return;
                }

                [menuItems[index].sort_order, menuItems[swapIndex].sort_order] = [menuItems[swapIndex].sort_order, menuItems[index].sort_order];
                render();
            }

            function reorderItem(draggedId, targetId) {
                if (!draggedId || !targetId || draggedId === targetId) {
                    return;
                }

                sortItems();

                const draggedIndex = menuItems.findIndex(item => item.id === draggedId);
                const targetIndex = menuItems.findIndex(item => item.id === targetId);

                if (draggedIndex < 0 || targetIndex < 0) {
                    return;
                }

                const [draggedItem] = menuItems.splice(draggedIndex, 1);
                menuItems.splice(targetIndex, 0, draggedItem);
                menuItems = menuItems.map((item, index) => ({ ...item, sort_order: index + 1 }));
                render();
            }

            function removeItem(id) {
                const idsToRemove = [id, ...childIds(id)];
                menuItems = menuItems.filter(item => !idsToRemove.includes(item.id));
                render();
            }

            function syncJson() {
                sortItems();
                jsonField.value = JSON.stringify(menuItems.map((item, index) => ({
                    id: item.id,
                    parent_id: item.parent_id || null,
                    label: item.label,
                    url: item.url || '#',
                    target: item.target === '_blank' ? '_blank' : '_self',
                    is_active: Boolean(item.is_active),
                    sort_order: index + 1,
                    type: item.type || 'custom',
                })));
            }

            function render() {
                sortItems();

                if (!menuItems.length) {
                    itemsWrapper.innerHTML = '<div class="alert alert-light border mb-0">No menu items yet. Add links from the left panel.</div>';
                    syncJson();
                    return;
                }

                itemsWrapper.innerHTML = menuItems.map(function (item) {
                    const depth = itemDepth(item);

                    return `
                        <div class="menu-builder-row border rounded p-3 mb-3 bg-white" data-menu-item="${escapeHtml(item.id)}" data-depth="${depth}" draggable="true">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-0"><i class="ri-draggable align-middle text-muted me-1"></i>${escapeHtml(item.label || 'Untitled')}</h6>
                                    <span class="text-muted small">${depth > 0 ? 'Dropdown item' : 'Top level item'} · ${escapeHtml(item.type || 'custom')}</span>
                                </div>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-soft-secondary btn-sm" data-move-up="${escapeHtml(item.id)}" aria-label="Move up"><i class="ri-arrow-up-line"></i></button>
                                    <button type="button" class="btn btn-soft-secondary btn-sm" data-move-down="${escapeHtml(item.id)}" aria-label="Move down"><i class="ri-arrow-down-line"></i></button>
                                    <button type="button" class="btn btn-soft-danger btn-sm" data-remove-menu-item="${escapeHtml(item.id)}" aria-label="Remove menu item"><i class="ri-delete-bin-line"></i></button>
                                </div>
                            </div>

                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Navigation Label</label>
                                    <input type="text" class="form-control" data-menu-field="label" value="${escapeHtml(item.label)}" placeholder="Products">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">URL</label>
                                    <input type="text" class="form-control" data-menu-field="url" value="${escapeHtml(item.url)}" placeholder="/products">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Parent Item</label>
                                    <select class="form-select" data-menu-field="parent_id">${parentOptions(item)}</select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Open</label>
                                    <select class="form-select" data-menu-field="target">${renderTargetOptions(item.target)}</select>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" data-menu-field="is_active" ${isChecked(item.is_active) ? 'checked' : ''}>
                                        <label class="form-check-label">Show in menu</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                syncJson();
            }

            let initialItems = [];

            try {
                initialItems = JSON.parse(jsonField.value || '[]');
            } catch (error) {
                initialItems = [];
            }

            menuItems = flattenSavedItems(initialItems);
            render();

            builder.addEventListener('click', function (event) {
                const addCustomButton = event.target.closest('[data-add-custom-link]');
                const addBlankButton = event.target.closest('[data-add-blank-item]');
                const addSelectedButton = event.target.closest('[data-add-selected-source]');
                const removeButton = event.target.closest('[data-remove-menu-item]');
                const moveUpButton = event.target.closest('[data-move-up]');
                const moveDownButton = event.target.closest('[data-move-down]');

                if (addCustomButton) {
                    const labelField = builder.querySelector('[data-custom-label]');
                    const urlField = builder.querySelector('[data-custom-url]');

                    addMenuItem({
                        label: labelField.value.trim() || 'Custom Link',
                        url: urlField.value.trim() || '#',
                        type: 'custom',
                    });

                    labelField.value = '';
                    urlField.value = '';
                }

                if (addBlankButton) {
                    addMenuItem({ label: 'Menu Item', url: '#', type: 'custom' });
                }

                if (addSelectedButton) {
                    const sourcePanel = addSelectedButton.closest('.accordion-body');
                    sourcePanel.querySelectorAll('[data-source-item]:checked').forEach(function (checkbox) {
                        addMenuItem({
                            label: checkbox.dataset.sourceLabel,
                            url: checkbox.dataset.sourceUrl,
                            type: checkbox.dataset.sourceType,
                        });

                        checkbox.checked = false;
                    });
                }

                if (removeButton) {
                    removeItem(removeButton.dataset.removeMenuItem);
                }

                if (moveUpButton) {
                    moveItem(moveUpButton.dataset.moveUp, 'up');
                }

                if (moveDownButton) {
                    moveItem(moveDownButton.dataset.moveDown, 'down');
                }
            });

            let draggedItemId = null;

            itemsWrapper.addEventListener('dragstart', function (event) {
                const row = event.target.closest('[data-menu-item]');

                if (!row) {
                    return;
                }

                draggedItemId = row.dataset.menuItem;
                event.dataTransfer.effectAllowed = 'move';
            });

            itemsWrapper.addEventListener('dragover', function (event) {
                if (event.target.closest('[data-menu-item]')) {
                    event.preventDefault();
                }
            });

            itemsWrapper.addEventListener('drop', function (event) {
                const row = event.target.closest('[data-menu-item]');

                if (!row) {
                    return;
                }

                event.preventDefault();
                reorderItem(draggedItemId, row.dataset.menuItem);
                draggedItemId = null;
            });

            itemsWrapper.addEventListener('input', function (event) {
                const row = event.target.closest('[data-menu-item]');

                if (!row || !event.target.matches('[data-menu-field]')) {
                    return;
                }

                const item = menuItems.find(candidate => candidate.id === row.dataset.menuItem);

                if (!item) {
                    return;
                }

                const field = event.target.dataset.menuField;
                item[field] = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
                syncJson();
            });

            itemsWrapper.addEventListener('change', function (event) {
                const row = event.target.closest('[data-menu-item]');

                if (!row || !event.target.matches('[data-menu-field]')) {
                    return;
                }

                const item = menuItems.find(candidate => candidate.id === row.dataset.menuItem);

                if (!item) {
                    return;
                }

                const field = event.target.dataset.menuField;
                item[field] = event.target.type === 'checkbox' ? event.target.checked : (event.target.value || null);

                if (field === 'parent_id') {
                    render();
                    return;
                }

                syncJson();
            });

            form.addEventListener('submit', syncJson);
        });
    </script>
@endsection
