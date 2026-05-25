<?php

namespace App\Http\Requests\V1\Articles;

use App\Rules\NoClickbaitWords;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $article = $this->route('article');
        $user = $this->user();

        if (! $user || ! $article) {
            return false;
        }

        return $user->role === 'admin' || $user->id === $article->user_id;
    }

    protected function failedAuthorization(): never
    {
        abort(403, 'You are not allowed to update this article.');
    }

    // -------------------------------------------------------------------------
    // PREPARE FOR VALIDATION
    // Same sanitization as StoreArticleRequest.
    // -------------------------------------------------------------------------

    protected function prepareForValidation(): void
    {
        $fields = [];

        if ($this->has('title')) {
            $fields['title'] = Str::squish(Str::title($this->input('title')));
        }

        if ($this->has('content')) {
            $fields['content'] = trim($this->input('content', ''));
        }

        if ($this->has('tags')) {
            $fields['tags'] = $this->normalizeTags($this->input('tags'));
        }

        if (! empty($fields)) {
            $this->merge($fields);
        }
    }

    // -------------------------------------------------------------------------
    // RULES
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $articleId = $this->route('article')?->id;

        return [
            'title' => [
                'sometimes', // only validate if field is present in request
                'required',
                'string',
                'min:10',
                'max:255',
                // Ignore the current article when checking uniqueness
                Rule::unique('articles', 'title')->ignore($articleId),
                new NoClickbaitWords,
            ],

            'content' => [
                'sometimes',
                'required',
                'string',
                'min:100',
            ],

            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => [
                'integer',
                Rule::exists('tags', 'id'),
            ],

            'status' => [
                'sometimes',
                'required',
                'string',
                'in:draft,published,archived',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Article title must be at least 10 characters.',
            'title.max' => 'Article title cannot exceed 255 characters.',
            'title.unique' => 'An article with this title already exists.',
            'content.min' => 'Article content must be at least 100 characters.',
            'tags.*.exists' => 'One or more selected tags do not exist.',
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
