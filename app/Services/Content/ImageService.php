<?php

namespace App\Services\Content;

use App\Enums\ImageVariant;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Stores uploaded images on the public disk: the untouched original plus WebP
 * "large" and "thumbs" versions scaled down to ImageVariant::maxEdge() (D-018).
 * Only the original's path is saved in the database; variant paths are derived from it.
 */
class ImageService
{
    /** Storage disk for all content images. */
    public const DISK = 'public';

    /** WebP quality (0–100) for resized variants. */
    private const QUALITY = 80;

    /**
     * @param  ImageManager  $images  Intervention Image manager (GD driver)
     */
    public function __construct(private readonly ImageManager $images)
    {
    }

    /**
     * Saves the original and its resized variants under $directory.
     *
     * @param  UploadedFile  $file  Validated jpg/png/webp upload
     * @param  string  $directory  Module folder, e.g. "gallery" or "amenities"
     * @return string Original's path relative to the disk, e.g. "gallery/originals/{uuid}.jpg"
     */
    public function store(UploadedFile $file, string $directory): string
    {
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $original = "{$directory}/".ImageVariant::Original->value."/{$uuid}.{$extension}";

        $this->disk()->putFileAs(dirname($original), $file, basename($original));

        foreach ([ImageVariant::Large, ImageVariant::Thumb] as $variant) {
            $edge = (int) $variant->maxEdge();
            $encoded = $this->images->read($file->getRealPath())
                ->scaleDown($edge, $edge)
                ->toWebp(self::QUALITY);

            $this->disk()->put(self::variantPath($original, $variant), (string) $encoded);
        }

        return $original;
    }

    /**
     * Deletes the original and every variant. Missing files are ignored.
     *
     * @param  string|null  $originalPath  Path returned by store()
     */
    public function delete(?string $originalPath): void
    {
        if ($originalPath === null || $originalPath === '') {
            return;
        }

        $this->disk()->delete(array_map(
            fn (ImageVariant $variant): string => self::variantPath($originalPath, $variant),
            ImageVariant::cases(),
        ));
    }

    /**
     * Disk path of a variant derived from the original's path.
     * Paths that do not follow the {dir}/originals/{file} layout are returned unchanged.
     */
    public static function variantPath(string $originalPath, ImageVariant $variant): string
    {
        $marker = '/'.ImageVariant::Original->value.'/';

        if ($variant === ImageVariant::Original || ! str_contains($originalPath, $marker)) {
            return $originalPath;
        }

        [$directory, $file] = explode($marker, $originalPath, 2);

        return $directory.'/'.$variant->value.'/'.pathinfo($file, PATHINFO_FILENAME).'.webp';
    }

    /**
     * Public URL of a variant, or null when there is no image.
     */
    public static function url(?string $originalPath, ImageVariant $variant = ImageVariant::Large): ?string
    {
        if ($originalPath === null || $originalPath === '') {
            return null;
        }

        return Storage::disk(self::DISK)->url(self::variantPath($originalPath, $variant));
    }

    /**
     * The public disk.
     */
    private function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }
}
