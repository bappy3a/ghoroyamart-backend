<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $unitId = $this->route('unit')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')->ignore($unitId),
            ],
        ];
    }

    /**
     * Always redirect validation errors back to the Units index.
     */
    protected function getRedirectUrl(): string
    {
        return route('units.index');
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->routeIs('units.update')) {
            session()->flash('unit_modal', 'edit');
            session()->flash('unit_edit_route', route('units.update', $this->route('unit')));
        } else {
            session()->flash('unit_modal', 'create');
        }

        parent::failedValidation($validator);
    }
}


