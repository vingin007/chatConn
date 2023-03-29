<?php

namespace App\Utils;

use Hyperf\Validation\Contract\Rule;

class PhoneRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param mixed $value
     */
    public function passes($attribute, $value): bool
    {
        $pattern = '/^(13\d|14[579]|15[^4\D]|166|17[^49\D]|18\d|19[89])\d{8}$/';
        return preg_match($pattern, $value);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Invalid phone number format.';
    }
}
