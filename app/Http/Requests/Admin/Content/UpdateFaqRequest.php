<?php

namespace App\Http\Requests\Admin\Content;

use App\Models\Faq;

/**
 * Edit a FAQ (rules in FaqRequest).
 */
class UpdateFaqRequest extends FaqRequest
{
    /**
     * Delegates to FaqPolicy::update.
     */
    public function authorize(): bool
    {
        /** @var Faq $faq */
        $faq = $this->route('faq');

        return $this->user()?->can('update', $faq) ?? false;
    }
}
