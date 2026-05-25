<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NoClickbaitWords implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $forbiddenWords = ['عاجل جدا', 'مفاجأة', 'انقر هنا', 'لن تصدق'];

        foreach ($forbiddenWords as $word) {
            if (str_contains($value, $word)) {
                // $fail("الحقل {$attribute} يحتوي على كلمات خادعة غير مسموح بها في منصتنا الأخبارية.");
                $fail("This {$attribute} contains forbidden words.");
            }
        }

    }
}
