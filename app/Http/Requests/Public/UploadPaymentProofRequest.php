<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Guest payment proof upload: jpg/png/webp/pdf, max 5 MB, checked by real MIME type (PLAN.md §5.6).
 * Access to the booking is checked by the controller (session, D-025).
 */
class UploadPaymentProofRequest extends FormRequest
{
    /** Max size in kilobytes (5 MB). */
    public const MAX_KB = 5120;

    /**
     * Public endpoint (the controller checks booking access).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:'.self::MAX_KB],
            'reference_no' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.required' => 'Please choose a screenshot or PDF of your payment receipt.',
            'proof.mimes' => 'Upload a JPG, PNG, WebP image or a PDF.',
            'proof.mimetypes' => 'Upload a JPG, PNG, WebP image or a PDF.',
            'proof.max' => 'The file is too large. The limit is 5 MB.',
        ];
    }

    /**
     * The uploaded proof.
     */
    public function proof(): UploadedFile
    {
        /** @var UploadedFile */
        return $this->file('proof');
    }
}
