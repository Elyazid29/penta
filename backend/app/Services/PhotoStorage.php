<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoStorage
{
    public const MAX_WIDTH = 1600;

    public const QUALITY = 75;

    public const THUMB_WIDTH = 320;

    public const THUMB_QUALITY = 70;

    /**
     * Simpan foto terkompresi + thumbnail.
     *
     * @return array{path: string, thumb_path: string|null}
     */
    public function store(UploadedFile $file, string $folder): array
    {
        $name = (string) Str::uuid();
        $source = $file->getRealPath();
        $image = $this->createImage($source);

        if (! $image instanceof GdImage) {
            $fallback = $file->storeAs($folder, $name.'.'.$file->getClientOriginalExtension(), 'public');

            return ['path' => $fallback, 'thumb_path' => null];
        }

        $image = $this->applyOrientation($image, $source);

        $mainData = $this->encode($this->resize($image, self::MAX_WIDTH), self::QUALITY);

        if ($mainData === null) {
            $fallback = $file->storeAs($folder, $name.'.'.$file->getClientOriginalExtension(), 'public');

            return ['path' => $fallback, 'thumb_path' => null];
        }

        $mainPath = "{$folder}/{$name}.jpg";
        Storage::disk('public')->put($mainPath, $mainData);

        $thumbData = $this->encode($this->resize($image, self::THUMB_WIDTH), self::THUMB_QUALITY);
        $thumbPath = null;

        if ($thumbData !== null) {
            $thumbPath = "{$folder}/thumbs/{$name}.jpg";
            Storage::disk('public')->put($thumbPath, $thumbData);
        }

        return ['path' => $mainPath, 'thumb_path' => $thumbPath];
    }

    private function createImage(string $source): ?GdImage
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $contents = @file_get_contents($source);

        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        return $image instanceof GdImage ? $image : null;
    }

    private function applyOrientation(GdImage $image, string $source): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($source);
        $orientation = (int) ($exif['Orientation'] ?? 0);

        return match ($orientation) {
            3 => $this->rotate($image, 180),
            6 => $this->rotate($image, -90),
            8 => $this->rotate($image, 90),
            default => $image,
        };
    }

    private function rotate(GdImage $image, int $degrees): GdImage
    {
        $rotated = @imagerotate($image, $degrees, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function resize(GdImage $image, int $maxWidth): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) max(1, round($height * ($maxWidth / $width)));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if (! $canvas instanceof GdImage) {
            return $image;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }

    private function encode(GdImage $image, int $quality): ?string
    {
        ob_start();
        $encoded = imagejpeg($image, null, $quality);
        $data = ob_get_clean();

        if (! $encoded || ! is_string($data) || $data === '') {
            return null;
        }

        return $data;
    }
}
