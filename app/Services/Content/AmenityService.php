<?php

namespace App\Services\Content;

use App\Models\Amenity;
use Illuminate\Http\UploadedFile;

/**
 * Amenity create/update/delete including the optional photo (stored under "amenities/", D-018).
 */
class AmenityService
{
    /** Folder on the public disk. */
    public const DIRECTORY = 'amenities';

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
     * Creates an amenity, storing the photo first when given.
     *
     * @param  array<string, mixed>  $data  Validated attributes (without the file)
     */
    public function create(array $data, ?UploadedFile $image = null): Amenity
    {
        if ($image !== null) {
            $data['image_path'] = $this->images->store($image, self::DIRECTORY);
        }

        return $this->content->create(Amenity::class, $data);
    }

    /**
     * Updates an amenity; a new photo replaces the old one, $removeImage drops it.
     * Old files are deleted only after the record is saved.
     *
     * @param  array<string, mixed>  $data  Validated attributes (without the file)
     */
    public function update(Amenity $amenity, array $data, ?UploadedFile $image = null, bool $removeImage = false): Amenity
    {
        $oldPath = $amenity->image_path;

        if ($image !== null) {
            $data['image_path'] = $this->images->store($image, self::DIRECTORY);
        } elseif ($removeImage) {
            $data['image_path'] = null;
        }

        $this->content->update($amenity, $data);

        if ($oldPath !== null && $amenity->image_path !== $oldPath) {
            $this->images->delete($oldPath);
        }

        return $amenity;
    }

    /**
     * Deletes the amenity and its photo files.
     */
    public function delete(Amenity $amenity): void
    {
        $path = $amenity->image_path;
        $this->content->delete($amenity);
        $this->images->delete($path);
    }
}
