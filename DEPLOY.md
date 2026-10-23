# Deploying the Jaru Café menu

## Requirements
- PHP **8.1 or newer** with `pdo_sqlite`, `gd` and `zip` (all on by default on most cPanel hosts).
  To check: cPanel › **Select PHP Version** › Extensions.
- No database to create. The menu creates `data/menu.sqlite` on the first visit.

## Upload (cPanel)
1. cPanel › **File Manager** › open `public_html`.
2. **Upload** `jaru-menu.zip`, right-click it › **Extract** into `public_html`.
   Click **Settings › Show hidden files** and check that the `.htaccess` files are there.
3. Delete `jaru-menu.zip` from `public_html`.
4. Folder permissions: `data/` and `uploads/` must be **755** (right-click › Change Permissions).
   If the admin shows a red "not writable" message, set these two to 755 or ask the host.
5. **Right away**, open `https://jarucafe.shop/admin/` and create the owner password.
   Until a password is set, the first person to open that page can set one.

## Fill in the website details
Admin › **Settings**: opening hours, phone, Instagram, "Our story", and the home page and café photos.
Anything left empty is hidden on the site. Pages: `/` (home), `/menu` (where the QR code points), `/about`, `/visit`.
These short addresses need Apache `mod_rewrite`, which cPanel hosts have on by default.

## SSL (https)
1. cPanel › **SSL/TLS Status** › tick `jarucafe.shop` and `www.jarucafe.shop` › **Run AutoSSL**.
2. Once the padlock shows, `.htaccess` sends all `http://` and `www.` visits to `https://jarucafe.shop`.

## Backups
- **Easy:** Admin › Menu › **Download backup**. You get a zip of the database and all photos.
  Do it after big menu changes and keep it in Google Drive.
- **Restore:** extract the backup zip into `public_html` (it overwrites `data/menu.sqlite` and `uploads/`).
- cPanel › **Backup** also includes these files in a full home-directory backup.

## Updating the app later
Upload the new files over the old ones, but **do not overwrite `data/` or `uploads/`**.
They hold the live menu.

## Printing QR codes
Admin › **QR code** › pick a layout › **Print** (A4, scale 100%), or **Download QR as PNG**
for a print shop. Scan one printed card with a phone before printing many.
