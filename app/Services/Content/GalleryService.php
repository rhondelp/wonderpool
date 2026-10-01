<?php

namespace App\Services\Content;

use App\Enums\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Http\UploadedFile;

/**
 * Gallery uploads (many at once), edits and deletion with file cleanup (stored under "gallery/", D-018).
 */
class GalleryService
{
    /** Folder on the public disk. */
    public const DIRECTORY = 'gallery';

    /**
     * @param  ContentService  $content  Generic CRUD + audit
     * @param  ImageService  $images  Image storage
     */
    public function __construct(
        private readonly ContentService $content,
        private readonly ImageService $images,
    ) {
    }

    /**
     * Stores every file as a new image at the end of the gallery order.
     *
     * @param  list<UploadedFile>  $files  Validated uploads
     * @param  GalleryCategory  $category  Category for the whole batch
     * @param  string|null  $caption  Optional caption applied to every image
     * @param  bool  $visible  Show on the public site right away
     * @return list<GalleryImage>
     */
    public function upload(array $files, GalleryCategory $category, ?string $caption = null, bool $visible = true): array
    {
        $created = [];

        foreach ($files as $file) {
            $created[] = $this->content->create(GalleryImage::class, [
                'path' => $this->images->store($file, self::DIRECTORY),
                'caption' => $caption,
                'category' => $category,
                'is_visible' => $visible,
            ]);
        }

        return $created;
    }

    /**
     * Updates caption, category, visibility.
     *
     * @param  array<string, mixed>  $data  Validated attributes
     */
    public function update(GalleryImage $image, array $data): GalleryImage
    {
        return $this->content->update($image, $data);
    }

    /**
     * Shows or hides the image on the public site.
     */
    public function toggleVisibility(GalleryImage $image): GalleryImage
    {
        return $this->content->update($image, ['is_visible' => ! $image->is_visible]);
    }

    /**
     * Deletes the record and all stored versions of the file.
     */
    public function delete(GalleryImage $image): void
    {
        $path = $image->path;
        $this->content->delete($image);
        $this->images->delete($path);
    }
}
