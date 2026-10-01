<?php

namespace App\Enums;

/**
 * Stored versions of an uploaded image (ImageService, D-018).
 * Path layout on the public disk: {dir}/{variant}/{uuid}.{ext}
 * e.g. gallery/originals/9b1e….jpg, gallery/large/9b1e….webp, gallery/thumbs/9b1e….webp
 */
enum ImageVariant: string
{
    case Original = 'originals';
    case Large = 'large';
    case Thumb = 'thumbs';

    /**
     * Longest edge in pixels for resized variants (null = untouched original).
     */
    public function maxEdge(): ?int
    {
        return match ($this) {
            self::Original => null,
            self::Large => 1600,
            self::Thumb => 480,
        };
    }
}
