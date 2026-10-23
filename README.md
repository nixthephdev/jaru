# Jaru Café

Website, QR menu and owner admin for Jaru Café. Laravel 13 + Filament 5 + Tailwind 4 + Pest.
See [CLAUDE.md](CLAUDE.md) for the layout and conventions and [DEPLOY.md](DEPLOY.md) for putting it on the cPanel host.

```
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan jaru:import-legacy path/to/menu.sqlite   # optional: bring over the old menu
php artisan make:filament-user
php artisan serve
```
