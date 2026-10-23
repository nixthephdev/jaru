<?php
// Jaru Cafe menu: DB, migrations, seed data and helpers. Included by every page.
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('UPLOAD_DIR', ROOT . '/uploads');
define('DB_FILE', DATA_DIR . '/menu.sqlite');

// Base URL path of the app ("" at a domain root, "/jaru" in a subfolder), same from / and /admin/.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (substr($base, -6) === '/admin') $base = substr($base, 0, -6);
define('BASE', $base);

date_default_timezone_set('Asia/Manila');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Security headers (also set in .htaccess for static files). Google Fonts is the only third party.
if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
        . "script-src 'self'; connect-src 'self'; manifest-src 'self'; worker-src 'self'; "
        . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0755, true);
    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $v = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    if ($v < 1) {
        $pdo->beginTransaction();
        $pdo->exec("
            CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT NOT NULL DEFAULT '');
            CREATE TABLE categories (
                id INTEGER PRIMARY KEY, name TEXT NOT NULL, note TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','soon','hidden')),
                sort INTEGER NOT NULL DEFAULT 0);
            CREATE TABLE items (
                id INTEGER PRIMARY KEY,
                category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
                name TEXT NOT NULL, description TEXT NOT NULL DEFAULT '',
                prices TEXT NOT NULL DEFAULT '[]', -- JSON [{\"label\":\"16oz\",\"cents\":12000}], cents null = not set
                photo TEXT NOT NULL DEFAULT '', sold_out INTEGER NOT NULL DEFAULT 0,
                hidden INTEGER NOT NULL DEFAULT 0, sort INTEGER NOT NULL DEFAULT 0);
            CREATE TABLE activity (id INTEGER PRIMARY KEY, at TEXT NOT NULL, what TEXT NOT NULL);
            CREATE TABLE login_attempts (ip TEXT NOT NULL, at INTEGER NOT NULL);
        ");
        seed($pdo);
        $pdo->exec('PRAGMA user_version = 1');
        $pdo->commit();
        $v = 1;
    }
    if ($v < 2) {
        // "Popular" row. Defaults are a starting pick; the owner changes them in admin.
        $pdo->beginTransaction();
        $pdo->exec('ALTER TABLE items ADD COLUMN featured INTEGER NOT NULL DEFAULT 0');
        $pdo->exec("UPDATE items SET featured = 1 WHERE name IN
            ('Matcha Crunch', 'Dirty Matcha', 'Strawberry Crunch', 'Café Latte', 'Hamburger Steak', 'Chocolate Drip Cake')");
        $pdo->exec('PRAGMA user_version = 2');
        $pdo->commit();
    }
}

// Real Jaru data, read from their FB posts. No price menu was available, so every price is blank
// (cents null) until the owner fills them in. Photos ship in uploads/ as seed-*.jpg.
function seed(PDO $pdo): void
{
    $settings = [
        'cafe_name' => 'Jaru Café',
        'tagline' => 'Good drinks, brighter days',
        'address' => 'Naga City, Camarines Sur',
        'hours' => '',
        'facebook' => '',
        'site_url' => 'https://jarucafe.shop/',
        'logo' => 'seed-logo.jpg',
        'cover' => 'seed-cover.jpg',
        'password_hash' => '',
    ];
    $st = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) $st->execute([$k, $v]);

    $menu = [
        ['Coffee', 'active', '', [
            ['Café Latte', 'Espresso and steamed milk.', 'seed-latte.jpg'],
            ['Iced Coffee Jelly Latte', 'Iced latte over coffee jelly.', 'seed-coffee-jelly.jpg'],
        ]],
        ['Matcha Series', 'active', '', [
            ['Matcha Crunch', 'Matcha latte with sea salt cream and cereals.', 'seed-matcha-crunch.jpg'],
            ['Dirty Matcha', 'Matcha latte topped with a shot of espresso.', 'seed-dirty-matcha.jpg'],
        ]],
        ['Non-Coffee', 'active', '', [
            ['Strawberry Crunch', 'Strawberry milk with cereals.', 'seed-strawberry-crunch.jpg'],
            ['Strawberry Milk', 'Fresh milk with strawberry jam.', 'seed-strawberry-milk.jpg'],
            ['Hot Chocolate', '', 'seed-hot-chocolate.jpg'],
            ['Iced Tea', '', 'seed-iced-tea.jpg'],
        ]],
        ['Rice Meals', 'active', 'Served with rice and egg.', [
            ['Hamburger Steak', 'Beef patties with mushroom gravy, rice and egg.', 'seed-hamburger-steak.jpg'],
            ['Longganisa', 'Sweet longganisa with rice, egg, tomato and cucumber.', 'seed-longganisa.jpg'],
        ]],
        ['Pasta', 'active', '', [
            ['Filipino Spaghetti', 'Sweet-style red sauce with hotdog and cheese.', 'seed-spaghetti.jpg'],
            ['Tuyo Pasta', 'Olive oil pasta with tuyo, olives and peppers.', 'seed-tuyo-pasta.jpg'],
            ['Chicken Pasta', 'Savory chicken pasta topped with a fried egg.', 'seed-chicken-pasta.jpg'],
        ]],
        ['Cakes & Pastries', 'active', '', [
            ['Banana Bread', 'With walnuts and chocolate chips.', 'seed-banana-bread.jpg'],
            ['Chocolate Drip Cake', 'Mocha buttercream with chocolate drip.', 'seed-drip-cake.jpg'],
        ]],
        ['Sinkers (Add-ons)', 'active', 'Add to any drink.', [
            ['Rainbow Jelly', '', 'seed-sinkers.jpg'],
            ['Crystal Boba', '', ''],
            ['Egg Pudding', '', ''],
            ['Brown Sugar Boba', '', ''],
            ['Coffee Jelly', '', ''],
        ]],
        ['Hojicha Series', 'soon', 'Roasted. Smooth. Distinctly delicious.', [
            ['Hojicha Latte', 'Smooth and earthy roasted goodness.', 'seed-hojicha-latte.jpg'],
            ['Hojicha Sea Salt', 'Hojicha latte with sea salt cream.', 'seed-hojicha-sea-salt.jpg'],
            ['Hojicha Crunch', 'Hojicha latte with sea salt cream and cereals.', 'seed-hojicha-crunch.jpg'],
        ]],
        ['Ube Series', 'soon', 'Bold flavors. Brighter days.', [
            ['Ube Latte', 'Smooth and creamy ube goodness.', 'seed-ube-latte.jpg'],
            ['Ube Sea Salt', 'Ube latte with sea salt cream.', 'seed-ube-sea-salt.jpg'],
            ['Ube Crunch', 'Ube latte with sea salt cream and cereals.', 'seed-ube-crunch.jpg'],
            ['Ube Pudding', 'Ube latte with sea salt cream and egg pudding.', 'seed-ube-pudding.jpg'],
            ['Ube Matcha', 'A perfect blend of ube and ceremonial matcha.', 'seed-ube-matcha.jpg'],
            ['Ube Matcha Sea Salt', 'Ceremonial matcha and ube with sea salt cream.', 'seed-ube-matcha-sea-salt.jpg'],
        ]],
    ];
    $cat = $pdo->prepare('INSERT INTO categories (name, status, note, sort) VALUES (?, ?, ?, ?)');
    $item = $pdo->prepare('INSERT INTO items (category_id, name, description, prices, photo, sort) VALUES (?, ?, ?, ?, ?, ?)');
    $blank = json_encode([['label' => '', 'cents' => null]]);
    foreach ($menu as $ci => [$name, $status, $note, $items]) {
        $cat->execute([$name, $status, $note, $ci + 1]);
        $cid = (int)$pdo->lastInsertId();
        foreach ($items as $ii => [$iname, $desc, $photo]) {
            $item->execute([$cid, $iname, $desc, $blank, $photo, $ii + 1]);
        }
    }
}

// ---------- small helpers ----------

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function setting(string $key): string
{
    static $all = null;
    if ($all === null) $all = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $all[$key] ?? '';
}

function set_setting(string $key, string $value): void
{
    db()->prepare('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value')
        ->execute([$key, $value]);
}

function peso(?int $cents): string
{
    if ($cents === null) return '';
    return '₱' . number_format($cents / 100, $cents % 100 ? 2 : 0);
}

/** "120", "120.50", "₱1,200" -> centavos; "" -> null. Returns false if invalid. */
function parse_peso(string $s)
{
    $s = trim(str_replace([',', '₱', ' '], '', $s));
    if ($s === '') return null;
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $s)) return false;
    return (int)round((float)$s * 100);
}

/** Decoded prices of an item row: [['label' => '', 'cents' => 12000|null], ...] */
function prices(array $item): array
{
    return json_decode($item['prices'] ?: '[]', true) ?: [];
}

function price_text(array $item): string
{
    $out = [];
    foreach (prices($item) as $p) {
        if ($p['cents'] === null) continue;
        $out[] = trim($p['label'] . ' ' . peso($p['cents']));
    }
    return implode(' · ', $out);
}

function upload_url(string $file, bool $small = false): string
{
    if ($file === '') return '';
    if ($small) $file = preg_replace('/\.jpg$/', '_sm.jpg', $file);
    return BASE . '/uploads/' . rawurlencode($file);
}

function log_activity(string $what): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO activity (at, what) VALUES (?, ?)')->execute([date('Y-m-d H:i:s'), $what]);
    $pdo->exec('DELETE FROM activity WHERE id NOT IN (SELECT id FROM activity ORDER BY id DESC LIMIT 200)');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

/** flash('Saved.') or flash('Wrong!', 'bad') to set; flash() returns [message, type] once. */
function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ---------- sessions, auth, CSRF ----------

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name('jaru_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => BASE . '/admin', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Session expired. Go back, refresh the page and try again.');
    }
}

function require_login(): void
{
    start_session();
    if (empty($_SESSION['admin'])) redirect(BASE . '/admin/login.php');
}

const MAX_ATTEMPTS = 5;      // per IP
const ATTEMPT_WINDOW = 900;  // 15 minutes

function login_locked(string $ip): bool
{
    $pdo = db();
    $pdo->prepare('DELETE FROM login_attempts WHERE at < ?')->execute([time() - ATTEMPT_WINDOW]);
    $st = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $st->execute([$ip]);
    return (int)$st->fetchColumn() >= MAX_ATTEMPTS;
}

// ---------- images ----------

/**
 * Validates an uploaded image (JPG/PNG/WEBP), re-encodes it with GD and writes
 * <name>.jpg (max 1200px) and <name>_sm.jpg (max 480px) into uploads/.
 * Returns the stored file name, or throws RuntimeException with a message for the owner.
 */
function save_upload(array $file, string $prefix = ''): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $tooBig = in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        throw new RuntimeException($tooBig ? 'Photo is too big. Try a smaller one.' : 'Upload failed. Please try again.');
    }
    if ($file['size'] > 12 * 1024 * 1024) throw new RuntimeException('Photo is too big (max 12 MB).');
    return save_image($file['tmp_name'], $prefix);
}

function save_image(string $path, string $prefix = ''): string
{
    if (!is_writable(UPLOAD_DIR)) throw new RuntimeException('The uploads folder is not writable. Ask your web host to fix folder permissions.');
    $info = @getimagesize($path);
    $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    if (!$info || !isset($types[$info[2]]) || !function_exists($types[$info[2]])) {
        throw new RuntimeException('Only JPG, PNG or WEBP photos are allowed.');
    }
    if ($info[0] * $info[1] > 40000000) throw new RuntimeException('Photo is too large in pixels.');
    ini_set('memory_limit', '256M');
    $src = @$types[$info[2]]($path);
    if (!$src) throw new RuntimeException('Could not read that photo. Try another one.');

    // Phone photos: honour EXIF rotation.
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($path)['Orientation'] ?? 1);
        $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($deg) $src = imagerotate($src, $deg, 0);
    }

    $name = $prefix . bin2hex(random_bytes(8)) . '.jpg';
    foreach ([[$name, 1200], [str_replace('.jpg', '_sm.jpg', $name), 480]] as [$out, $max]) {
        $w = imagesx($src); $h = imagesy($src);
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // flatten PNG transparency
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imageinterlace($dst, true);
        if (!imagejpeg($dst, UPLOAD_DIR . '/' . $out, 82)) throw new RuntimeException('Could not save the photo.');
        imagedestroy($dst);
    }
    imagedestroy($src);
    return $name;
}

/** Deletes an uploaded photo and its small copy if nothing references it any more. */
function delete_photo(string $name): void
{
    if ($name === '' || !preg_match('/^[\w-]+\.jpg$/', $name)) return;
    $pdo = db();
    $st = $pdo->prepare('SELECT (SELECT COUNT(*) FROM items WHERE photo = ?) + (SELECT COUNT(*) FROM settings WHERE value = ? AND key IN (\'logo\',\'cover\'))');
    $st->execute([$name, $name]);
    if ((int)$st->fetchColumn() > 0) return;
    @unlink(UPLOAD_DIR . '/' . $name);
    @unlink(UPLOAD_DIR . '/' . str_replace('.jpg', '_sm.jpg', $name));
}
