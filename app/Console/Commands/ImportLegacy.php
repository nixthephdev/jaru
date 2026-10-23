<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportLegacy extends Command
{
    protected $signature = 'jaru:import-legacy {file : Path to the old data/menu.sqlite} {--email=jarumarucafe@gmail.com : Admin login email}';

    protected $description = 'Copy the menu, settings and admin password from the old plain-PHP app';

    public function handle(): int
    {
        $file = is_file($this->argument('file')) ? $this->argument('file') : base_path($this->argument('file'));
        if (! is_file($file)) {
            $this->error("Not found: $file");

            return self::FAILURE;
        }
        $old = new PDO('sqlite:'.$file, null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        DB::transaction(function () use ($old) {
            DB::table('items')->delete();
            DB::table('categories')->delete();
            DB::table('settings')->delete();

            foreach ($old->query('SELECT * FROM categories ORDER BY sort, id') as $c) {
                DB::table('categories')->insert(['id' => $c['id'], 'name' => $c['name'], 'note' => $c['note'],
                    'status' => $c['status'], 'sort' => $c['sort'], 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($old->query('SELECT * FROM items ORDER BY sort, id') as $i) {
                DB::table('items')->insert(['id' => $i['id'], 'category_id' => $i['category_id'], 'name' => $i['name'],
                    'description' => $i['description'], 'prices' => $i['prices'], 'photo' => $i['photo'],
                    'sold_out' => $i['sold_out'], 'hidden' => $i['hidden'], 'featured' => $i['featured'] ?? 0,
                    'sort' => $i['sort'], 'created_at' => now(), 'updated_at' => now()]);
            }

            $hash = '';
            foreach ($old->query('SELECT key, value FROM settings') as $s) {
                if ($s['key'] === 'password_hash') {
                    $hash = $s['value'];

                    continue;
                }
                DB::table('settings')->insert(['key' => $s['key'], 'value' => $s['value']]);
            }

            // Keeps the owner's existing password. With none set yet, run: php artisan make:filament-user
            if ($hash !== '') {
                // Straight to the table: the model's "hashed" cast would re-hash or reject the old bcrypt hash.
                DB::table('users')->updateOrInsert(
                    ['email' => $this->option('email')],
                    ['name' => 'Owner', 'password' => $hash, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        });

        $this->info('Imported '.Category::count().' categories, '.Item::count().' items.');

        return self::SUCCESS;
    }
}
