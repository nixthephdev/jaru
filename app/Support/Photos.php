<?php

namespace App\Support;

use App\Models\Item;
use App\Models\Setting;
use RuntimeException;

/** Owner photos live in public/uploads as <name>.jpg (max 1200px) plus <name>_sm.jpg (max 480px). */
class Photos
{
    public static function dir(): string
    {
        return public_path('uploads');
    }

    public static function url(string $file, bool $small = false): string
    {
        if ($file === '') {
            return '';
        }
        if ($small) {
            $file = preg_replace('/\.jpg$/', '_sm.jpg', $file);
        }

        return asset('uploads/'.rawurlencode($file));
    }

    /** Validates, re-encodes with GD and stores both sizes. Returns the file name. */
    public static function save(string $path, string $prefix = ''): string
    {
        if (! is_dir(self::dir())) {
            @mkdir(self::dir(), 0755, true);
        }
        if (! is_writable(self::dir())) {
            throw new RuntimeException('The uploads folder is not writable. Ask your web host to fix folder permissions.');
        }
        $info = @getimagesize($path);
        $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        if (! $info || ! isset($types[$info[2]]) || ! function_exists($types[$info[2]])) {
            throw new RuntimeException('Only JPG, PNG or WEBP photos are allowed.');
        }
        if ($info[0] * $info[1] > 40000000) {
            throw new RuntimeException('Photo is too large in pixels.');
        }

        ini_set('memory_limit', '256M');
        $src = @$types[$info[2]]($path);
        if (! $src) {
            throw new RuntimeException('Could not read that photo. Try another one.');
        }

        // Phone photos: honour EXIF rotation.
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $deg = [3 => 180, 6 => -90, 8 => 90][(int) (@exif_read_data($path)['Orientation'] ?? 1)] ?? 0;
            if ($deg) {
                $src = imagerotate($src, $deg, 0);
            }
        }

        $name = $prefix.bin2hex(random_bytes(8)).'.jpg';
        foreach ([[$name, 1200], [str_replace('.jpg', '_sm.jpg', $name), 480]] as [$out, $max]) {
            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min(1, $max / max($w, $h));
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten PNG transparency
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imageinterlace($dst, true);
            if (! imagejpeg($dst, self::dir().'/'.$out, 82)) {
                throw new RuntimeException('Could not save the photo.');
            }
            imagedestroy($dst);
        }
        imagedestroy($src);

        return $name;
    }

    /** Deletes a photo and its small copy once nothing references it. */
    public static function delete(?string $name): void
    {
        if (! $name || ! preg_match('/^[\w-]+\.jpg$/', $name)) {
            return;
        }
        $used = Item::where('photo', $name)->exists()
            || Setting::whereIn('key', Setting::PHOTOS)->where('value', $name)->exists();
        if ($used) {
            return;
        }
        @unlink(self::dir().'/'.$name);
        @unlink(self::dir().'/'.str_replace('.jpg', '_sm.jpg', $name));
    }
}
