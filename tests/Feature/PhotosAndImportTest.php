<?php

use App\Models\Category;
use App\Models\Item;
use App\Models\Setting;
use App\Support\Photos;
use Illuminate\Support\Facades\DB;

afterEach(function () {
    foreach (glob(Photos::dir().'/test-*.{jpg,png}', GLOB_BRACE) ?: [] as $f) {
        @unlink($f);
    }
});

function makeImage(string $type, int $w = 2000, int $h = 1000): string
{
    $path = tempnam(sys_get_temp_dir(), 'img');
    $im = imagecreatetruecolor($w, $h);
    match ($type) {
        'png' => imagepng($im, $path),
        'jpg' => imagejpeg($im, $path),
        default => file_put_contents($path, 'not an image'),
    };

    return $path;
}

it('stores a big photo as 1200px and 480px jpgs', function () {
    $name = Photos::save(makeImage('png'), 'test-');

    expect($name)->toStartWith('test-')->toEndWith('.jpg');
    [$w] = getimagesize(Photos::dir().'/'.$name);
    [$wSmall] = getimagesize(Photos::dir().'/'.str_replace('.jpg', '_sm.jpg', $name));
    expect($w)->toBe(1200)->and($wSmall)->toBe(480);
});

it('rejects files that are not photos', function () {
    Photos::save(makeImage('text'));
})->throws(RuntimeException::class, 'Only JPG, PNG or WEBP');

it('deletes a photo only once nothing uses it', function () {
    $name = Photos::save(makeImage('jpg'), 'test-');
    $item = Item::create(['category_id' => Category::create(['name' => 'A'])->id, 'name' => 'One', 'photo' => $name]);

    Photos::delete($name);
    expect(file_exists(Photos::dir().'/'.$name))->toBeTrue();

    $item->delete();
    expect(file_exists(Photos::dir().'/'.$name))->toBeFalse()
        ->and(file_exists(Photos::dir().'/'.str_replace('.jpg', '_sm.jpg', $name)))->toBeFalse();
});

it('makes a round favicon from the logo and deletes it with the logo', function () {
    $name = Photos::save(makeImage('jpg'), 'test-');
    Setting::put('logo', $name);

    expect(Photos::iconUrl($name))->toEndWith(str_replace('.jpg', '_icon.png', $name));
    $icon = Photos::dir().'/'.str_replace('.jpg', '_icon.png', $name);
    $im = imagecreatefrompng($icon);
    expect(imagesx($im))->toBe(192)
        ->and(imagecolorat($im, 0, 0) >> 24)->toBe(127)    // corner: fully transparent
        ->and(imagecolorat($im, 96, 96) >> 24)->toBe(0);   // centre: solid
    $this->get('/')->assertSee('_icon.png');

    Setting::put('logo', '');
    Photos::delete($name);
    expect(file_exists($icon))->toBeFalse();
});

it('removes the old photo when an item gets a new one', function () {
    $old = Photos::save(makeImage('jpg'), 'test-');
    $new = Photos::save(makeImage('jpg'), 'test-');
    $item = Item::create(['category_id' => Category::create(['name' => 'A'])->id, 'name' => 'One', 'photo' => $old]);

    $item->update(['photo' => $new]);

    expect(file_exists(Photos::dir().'/'.$old))->toBeFalse()->and(file_exists(Photos::dir().'/'.$new))->toBeTrue();
});

it('keeps a photo that a setting still uses', function () {
    $name = Photos::save(makeImage('jpg'), 'test-');
    Setting::put('hero', $name);

    Photos::delete($name);

    expect(file_exists(Photos::dir().'/'.$name))->toBeTrue();
});

it('imports the old menu and keeps the owner password', function () {
    $file = tempnam(sys_get_temp_dir(), 'legacy');
    $old = new PDO('sqlite:'.$file);
    $old->exec('CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT);
        CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT, note TEXT, status TEXT, sort INTEGER);
        CREATE TABLE items (id INTEGER PRIMARY KEY, category_id INTEGER, name TEXT, description TEXT, prices TEXT, photo TEXT,
            sold_out INTEGER, hidden INTEGER, sort INTEGER, featured INTEGER);');
    $hash = password_hash('secret-pass', PASSWORD_DEFAULT);
    $old->exec("INSERT INTO settings VALUES ('cafe_name', 'Jaru Café'), ('password_hash', '$hash')");
    $old->exec("INSERT INTO categories VALUES (1, 'Coffee', '', 'active', 1)");
    $old->exec("INSERT INTO items VALUES (1, 1, 'Latte', '', '[{\"label\":\"\",\"cents\":12000}]', '', 0, 0, 1, 1)");
    $old = null;

    $this->artisan('jaru:import-legacy', ['file' => $file, '--email' => 'owner@jaru.test'])->assertSuccessful();

    expect(Item::first()->priceText())->toBe('₱120')
        ->and(Item::first()->featured)->toBeTrue()
        ->and(Setting::get('cafe_name'))->toBe('Jaru Café')
        ->and(Setting::get('password_hash'))->toBe('')
        ->and(password_verify('secret-pass', DB::table('users')->where('email', 'owner@jaru.test')->value('password')))->toBeTrue();
});
