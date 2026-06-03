<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SecureUploadService
{
    public const MAX_BRANDING_KB = 1024;

    public const MAX_CATEGORY_ICON_KB = 512;

    public const MAX_QR_KB = 2048;

    /**
     * @param  list<string>  $allowedMimes
     */
    public function storeImage(
        UploadedFile $file,
        string $directory,
        array $allowedMimes,
        int $maxKilobytes,
        ?string $disk = null,
    ): string {
        $disk ??= config('filesystems.product_disk', 'public');

        if (! $file->isValid()) {
            throw new InvalidArgumentException('Invalid upload.');
        }

        if ($file->getSize() > $maxKilobytes * 1024) {
            throw new InvalidArgumentException("File must be smaller than {$maxKilobytes}KB.");
        }

        $mime = $file->getMimeType() ?? '';
        if (! in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException('File type is not allowed.');
        }

        $extension = match ($mime) {
            'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => $file->guessExtension() ?: 'bin',
        };

        if (in_array($extension, ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'js', 'html', 'htm'], true)) {
            throw new InvalidArgumentException('Executable uploads are not allowed.');
        }

        $filename = Str::uuid()->toString().'.'.$extension;

        return $file->storeAs($directory, $filename, $disk);
    }

    public function deleteIfExists(?string $path, ?string $disk = null): void
    {
        if (! $path) {
            return;
        }

        $disk ??= config('filesystems.product_disk', 'public');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function url(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }

        $disk ??= config('filesystems.product_disk', 'public');

        return Storage::disk($disk)->url($path);
    }

    /** @return list<string> */
    public static function brandingMimes(): array
    {
        return [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/x-icon',
            'image/vnd.microsoft.icon',
        ];
    }

    /** @return list<string> */
    public static function categoryIconMimes(): array
    {
        return [
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/gif',
        ];
    }
}
