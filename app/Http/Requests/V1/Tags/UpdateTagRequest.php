<?php

namespace App\Http\Requests\V1\Tags;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function failedAuthorization(): never
    {
        abort(403, 'Only admins can update tags.');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tags', 'name')->ignore($this->route('tag')->id),
            ],
            'id' => [
                'required',
                'integer',
                Rule::exists('tags', 'id'),
            ],
        ];
    }
}
