<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Re-encodes every upload with GD. Re-encoding drops all EXIF data, GPS position
 * included, and anything else that was hiding in the original file.
 */
class PhotoStore
{
    public function store(UploadedFile $file, string $directory): string
    {
        $contents = file_get_contents($file->getRealPath());
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('The uploaded file is not a readable image.');
        }

        $image = $this->applyOrientation($image, $file);
        $image = $this->limitSize($image, (int) config('civic.photos.max_dimension'));

        ob_start();
        imagejpeg($image, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::ulid().'.jpg';
        Storage::disk(config('civic.photos.disk'))->put($path, $jpeg);

        return $path;
    }

    public function path(string $path): string
    {
        return Storage::disk(config('civic.photos.disk'))->path($path);
    }

    public function exists(?string $path): bool
    {
        return $path !== null && Storage::disk(config('civic.photos.disk'))->exists($path);
    }

    /**
     * Phones record rotation in EXIF instead of rotating pixels. Apply it before EXIF is dropped.
     */
    private function applyOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || $file->getMimeType() !== 'image/jpeg') {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated === false ? $image : $rotated;
    }

    private function limitSize(\GdImage $image, int $max): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $max && $height <= $max) {
            return $image;
        }

        $ratio = min($max / $width, $max / $height);
        $scaled = imagescale($image, (int) round($width * $ratio), (int) round($height * $ratio));

        return $scaled === false ? $image : $scaled;
    }
}
