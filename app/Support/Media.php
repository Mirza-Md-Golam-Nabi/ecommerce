<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Public URLs for uploaded images (stored on the `public` disk by the admin panel).
 */
final class Media
{
    public static function url(?string $path): ?string
    {
        return blank($path) ? null : Storage::disk('public')->url($path);
    }
}
