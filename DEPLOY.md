# Deploying the Jaru Café website (Laravel + Filament)

## Requirements
- PHP **8.3 or newer** (the host's default is 8.2, so switch it): cPanel › **Select PHP Version** › pick **8.3** (or 8.4).
  Extensions needed: `pdo_sqlite`, `sqlite3`, `gd`, `zip`, `mbstring`, `intl`, `fileinfo`, `exif`, `openssl`, `ctype`, `curl`, `dom`, `xml`.
- No MySQL. The menu lives in `database/database.sqlite`.

## 1. Build the upload on your PC
```
composer install --no-dev --optimize-autoloader
npm install && npm run build
```
Zip the project folder **without** `node_modules/`, `tests/`, `.git/` and `.env`.
It must contain `vendor/` and `public/build/` (they are built on your PC, the host does not run Composer or npm).

## 2. Bring over the live menu (first time only)
The old site's **Admin › Download backup** zip has `data/menu.sqlite` and `uploads/`. On your PC:
```
php artisan migrate
php artisan jaru:import-legacy path/to/menu.sqlite
```
This copies the menu, settings and **the owner's current password**. The admin login becomes the email
`jarumarucafe@gmail.com` (change it with `--email=`) plus the same password as before.
Copy the old `uploads/*.jpg` files into `public/uploads/`, then include `database/database.sqlite` and `public/uploads/` in the zip.

Starting fresh instead? Skip this and run `php artisan make:filament-user` on your PC after `migrate`.

## 3. Upload (cPanel)
1. File Manager › `public_html` › **Upload** the zip, **Extract** it into `public_html`, delete the zip.
   Turn on **Settings › Show hidden files** and check that `.htaccess` and `.env` are there.
2. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://jarucafe.shop`,
   `APP_KEY` (run `php artisan key:generate --show` on your PC and paste it).
3. Permissions **755** on `storage/`, `bootstrap/cache/`, `database/` (and the file `database/database.sqlite` **644/664**), and `public/uploads/`.
   If the admin says a folder is not writable, set these to 755 or ask the host.
4. The root `.htaccess` sends every visit into `public/`, so `.env`, `vendor/` and `database/` can never be downloaded.
   (If your host lets you pick the document root, point it at `public/` instead.)

## SSL (https)
cPanel › **SSL/TLS Status** › tick `jarucafe.shop` and `www.jarucafe.shop` › **Run AutoSSL**.
Once the padlock shows, `.htaccess` sends all `http://` and `www.` visits to `https://jarucafe.shop`.

## Using it
- Admin: `https://jarucafe.shop/admin`. **Menu items** (tap Sold out when something runs out), **Categories**, **QR code**, **Backup**, **Settings**.
- Pages: `/` (home), `/menu` (where the QR code points), `/about`, `/visit`.
- Password and email: top-right avatar › **Profile**.

## Backups
- **Easy:** Admin › **Backup** › Download backup. You get a zip of the database and all photos. Do it after big menu changes and keep it in Google Drive.
- **Restore:** extract the zip into `public_html` (it overwrites `database/database.sqlite` and `public/uploads/`).
- cPanel › **Backup** also includes these files in a full home-directory backup.

## Updating the app later
Build again (step 1) and upload the new files over the old ones, but **do not overwrite `.env`, `database/database.sqlite` or `public/uploads/`**.
They hold the live menu. If the update has new database changes, run `php artisan migrate` (locally on a copy of the database, then upload it) or ask for it to be done.

## Printing QR codes
Admin › **QR code** › pick a layout › **Print** (A4, scale 100%), or **Download QR as PNG** for a print shop.
Scan one printed card with a phone before printing many.
