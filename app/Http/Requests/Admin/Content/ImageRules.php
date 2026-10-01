<?php

namespace App\Http\Requests\Admin\Content;

/**
 * Shared upload rules for content images: jpg/png/webp, max 5 MB, max 8000 px per side.
 */
final class ImageRules
{
    /** Max upload size in kilobytes (5 MB). */
    public const MAX_KB = 5120;

    /**
     * Rules for one image file.
     *
     * @return list<string>
     */
    public static function file(): array
    {
        return ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB, 'dimensions:max_width=8000,max_height=8000'];
    }
}
