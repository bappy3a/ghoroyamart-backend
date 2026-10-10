@php
    $highlightRows = old($setting->key, old($setting->key . '_submitted') ? [] : null);
    if (!is_array($highlightRows)) {
        $highlightRows = home_highlight_items($setting->value, $setting->key);
    }
    $highlightIcons = home_highlight_icons();
    $highlightIsPromise = $setting->key === 'home_promise_items';
@endphp
<div class="col-12" data-highlight-builder data-key="{{ $setting->key }}" data-promise="{{ $highlightIsPromise ? 1 : 0 }}">
    <label class="form-label">
        {{ $setting->label }}
        @if($setting->description)
            <small class="text-muted d-block">{{ $setting->description }}</small>
        @endif
    </label>
    <input type="hidden" name="{{ $setting->key }}_submitted" value="1">
    <div class="d-grid gap-2" data-highlight-rows>
        @foreach($highlightRows as $i => $row)
            <div class="row g-2 align-items-center" data-highlight-row>
                <div class="col-md-3">
                    <select name="{{ $setting->key }}[{{ $i }}][icon]" class="form-select">
                        @foreach($highlightIcons as $iconKey => $iconLabel)
                            <option value="{{ $iconKey }}" @selected(($row['icon'] ?? '') === $iconKey)>{{ $iconLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="{{ $setting->key }}[{{ $i }}][title]" class="form-control" placeholder="Title" maxlength="80" value="{{ $row['title'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    @if($highlightIsPromise)
                        <input type="text" name="{{ $setting->key }}[{{ $i }}][subtitle]" class="form-control" placeholder="Subtitle (optional)" maxlength="120" value="{{ $row['subtitle'] ?? '' }}">
                    @else
                        <input type="hidden" name="{{ $setting->key }}[{{ $i }}][subtitle]" value="">
                    @endif
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-soft-danger btn-icon" data-highlight-remove title="Remove"><i class="ri-delete-bin-line"></i></button>
                </div>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-soft-primary btn-sm mt-2" data-highlight-add>
        <i class="ri-add-line align-middle me-1"></i> Add item
    </button>
    <template data-highlight-template>
        <div class="row g-2 align-items-center" data-highlight-row>
            <div class="col-md-3">
                <select name="__KEY__[__I__][icon]" class="form-select">
                    @foreach($highlightIcons as $iconKey => $iconLabel)
                        <option value="{{ $iconKey }}">{{ $iconLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="__KEY__[__I__][title]" class="form-control" placeholder="Title" maxlength="80">
            </div>
            <div class="col-md-4">
                @if($highlightIsPromise)
                    <input type="text" name="__KEY__[__I__][subtitle]" class="form-control" placeholder="Subtitle (optional)" maxlength="120">
                @else
                    <input type="hidden" name="__KEY__[__I__][subtitle]" value="">
                @endif
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-soft-danger btn-icon" data-highlight-remove title="Remove"><i class="ri-delete-bin-line"></i></button>
            </div>
        </div>
    </template>
</div>
