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

    /**
     * Round favicon for a photo: <name>_icon.png, 192px, transparent outside the circle.
     * Made on first use from the small copy, so a logo uploaded before this existed gets one too.
     * Falls back to the square small copy if GD cannot write it.
     */
    public static function iconUrl(string $file): string
    {
        if (! preg_match('/^[\w-]+\.jpg$/', $file)) {
            return '';
        }
        $icon = str_replace('.jpg', '_icon.png', $file);
        $path = self::dir().'/'.$icon;
        if (! is_file($path) && ! self::makeIcon(self::dir().'/'.str_replace('.jpg', '_sm.jpg', $file), $path)) {
            return self::url($file, small: true);
        }

        return asset('uploads/'.rawurlencode($icon));
    }

    private static function makeIcon(string $from, string $to): bool
    {
        $src = is_file($from) && function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($from) : false;
        if (! $src) {
            return false;
        }
        // Draw the centre square at 4x with hard circle edges, then scale down: the downscale smooths the edge.
        $big = 768;
        $side = min(imagesx($src), imagesy($src));
        $sq = imagecreatetruecolor($big, $big);
        imagealphablending($sq, false);
        imagesavealpha($sq, true);
        imagecopyresampled($sq, $src, 0, 0, intdiv(imagesx($src) - $side, 2), intdiv(imagesy($src) - $side, 2), $big, $big, $side, $side);
        $clear = imagecolorallocatealpha($sq, 0, 0, 0, 127);
        $r = $big / 2;
        for ($y = 0; $y < $big; $y++) {
            for ($x = 0; $x < $big; $x++) {
                if (($x + 0.5 - $r) ** 2 + ($y + 0.5 - $r) ** 2 > $r * $r) {
                    imagesetpixel($sq, $x, $y, $clear);
                }
            }
        }
        $out = imagecreatetruecolor(192, 192);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $sq, 0, 0, 0, 0, 192, 192, $big, $big);
        $ok = @imagepng($out, $to);
        imagedestroy($src);
        imagedestroy($sq);
        imagedestroy($out);

        return $ok;
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

    /** Deletes a photo, its small copy and its round icon once nothing references it. */
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
        @unlink(self::dir().'/'.str_replace('.jpg', '_icon.png', $name));
    }
}
