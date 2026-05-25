<?php

namespace App\Http\Requests\V1\Articles;

use App\Rules\NoClickbaitWords;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Override;

class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role == 'writer';
    }

    protected function failedAuthorization(): never
    {
        abort(403, 'Only writers can create articles.');
    }

    #[Override]
    protected function prepareForValidation()
    {

        $this->merge([

            'title' => Str::squish(Str::title($this->input('title'))),
            'content' => trim($this->input('content')),
            'tags' => $this->normalizeTags($this->input('tags')),
            'status' => $this->input('status', 'draft'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'unique:articles,title',
                'min:10',
                'max:255',
                'string',
                new NoClickbaitWords,
            ],
            'content' => [
                'required',
                'string',
                'min:100',
            ],
            'status' => [
                'required',
                'string',
                'in:draft,published,archived',
            ],
            'tags' => ['array', 'nullable'],
            'tags.*' => [
                'exists:tags,id',
            ],
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf,docx,zip',
                'max:10240',
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Article title is required.',
            'title.min' => 'Article title must be at least 10 characters.',
            'title.max' => 'Article title cannot exceed 255 characters.',
            'title.unique' => 'An article with this title already exists.',

            'content.required' => 'Article content is required.',
            'content.min' => 'Article content must be at least 100 characters.',

            'tags.array' => 'Tags must be provided as an array of IDs.',
            'tags.*.integer' => 'Each tag must be a valid integer ID.',
            'tags.*.exists' => 'One or more selected tags do not exist.',

            'status.required' => 'Article status is required.',

            'attachments.max' => 'لا يمكنك رفع أكثر من 5 مرفقات للمقال الواحد.',
            'attachments.*.file' => 'المرفق يجب أن يكون ملفاً صحيحاً.',
            'attachments.*.mimes' => 'نوع الملف غير مدعوم! يسمح فقط بصيغ (PDF, Word, Zip) والصور (JPG, PNG).',
            'attachments.*.max' => 'حجم الملف كبير جداً، الحد الأقصى المسموح به للملف الواحد هو 10 ميجابايت.',
        ];
    }

    // -------------------------------------------------------------------------
    // ATTRIBUTES
    // Nicer field names in error messages.
    // -------------------------------------------------------------------------

    public function attributes(): array
    {
        return [
            'title' => 'article title',
            'content' => 'article content',
            'status' => 'article status',
            'tags.*' => 'tag ID',
        ];
    }

    private function normalizeTags(mixed $tags)
    {
        if (empty($tags)) {
            return [];
        }
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        if (is_array($tags)) {
            return array_values(
                array_filter(

                    array_map(
                        fn ($tag_id) => is_numeric($tag_id) ? (int) $tag_id : null,
                        $tags
                    ), // Callback function to run for each element in each array. and return the new array

                    fn ($tag_id) => $tag_id !== null
                )
            );
        }

        return [];
    }
}
