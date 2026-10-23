# Jaru Café menu website

Public website + QR menu + owner admin for **Jaru Café**, Naga City (jarucafe.shop). Built solo by Nikko Calumpiano.

## Stack

Same as the Kaffee project: **Laravel 13, Filament 5 (Livewire 4), Tailwind CSS 4 + Vite, Pest 4**, PHP 8.3+.
SQLite (no database server to run on the shared host). Vue 3 is used only as an island for the interactive menu (no Inertia, no SPA, no React): pages stay server-rendered Blade for Google and fast phone loads on cPanel, which cannot run Node/SSR.

- **Admin** (`/admin`): Filament panel. Menu items, categories, Settings page, QR code page (print + PNG), Backup page, dashboard. Styled like the website in `resources/css/filament/admin/theme.css` (linen sidebar, ink pill for the current page, paper cards with hairlines, ink primary buttons); hand-tuned olive and brick palettes in `AdminPanelProvider`, dark mode off. The login page shows the cover photo beside the form (`filament/auth-photo.blade.php`). QR cards: ivory paper, olive hairline frame, the code inside an arch.
- **Public site** (`/`, `/menu`, `/about`, `/visit`): Blade + Tailwind 4, quiet luxury taken from the room: ivory `#F8F5EF` and linen `#F0EAE0` paper, espresso ink `#2B2420` (primary buttons are ink with ivory text), the logo's olive `#5D6234` as the only accent (labels, active tab underline), dark oak `#2F2620` footer with the closing call to action. Hairlines instead of cards and shadows. Light Fraunces (optical size axis, italics) in sentence case, Manrope body; small tracked uppercase only for labels and buttons. Arch frames (with a hairline `arch-echo`) on the Home photo, the About photo and the menu item sheet. Tokens and components live in `resources/css/site.css`; inner pages use `<x-page-head>`. `/menu` is where the QR code points; it renders a plain list and `resources/js/MenuApp.vue` replaces it with search, category tabs, printed-menu rows (dotted leader to a single price) and the item sheet. No illustrations, 3D, collages or all-caps headlines.
- Run PHP 8.3+ for artisan, Composer and tests. XAMPP's own PHP is 8.2; use the winget PHP 8.4 on PATH.

## Conventions

- Money is stored as integer **centavos** inside the item's `prices` JSON (`[{"label":"16oz","cents":12000}]`, `cents: null` = no price yet). The admin form edits pesos; `App\Support\Money` formats.
- Owner photos live in `public/uploads/` as `<name>.jpg` (1200px) + `<name>_sm.jpg` (480px), written by `App\Support\Photos` (GD). Settings keys `logo`, `hero`, `cover` and `items.photo` hold the file name. Photos are deleted once nothing references them.
- Settings are a key/value table (`App\Models\Setting`). An empty value hides that block on the site.
- Every admin change is logged to `activities` (last 200 kept) by the model events, shown as "Recent changes" on the dashboard. Reordering is not logged.
- Timezone `Asia/Manila`.
- Write a Pest test for every business rule (`tests/Feature`).

## Commands

```
composer install && npm install && npm run build
php artisan migrate
php artisan jaru:import-legacy <path>      # copy menu + settings + admin password from the old plain-PHP app's menu.sqlite
php artisan make:filament-user             # create an admin login
php artisan test
```

