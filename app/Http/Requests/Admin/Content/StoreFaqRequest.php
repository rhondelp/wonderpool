<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Faq;

/**
 * Create a FAQ (rules in FaqRequest).
 */
class StoreFaqRequest extends FaqRequest
{
    /**
     * Delegates to FaqPolicy::create.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Faq::class) ?? false;
    }
}
