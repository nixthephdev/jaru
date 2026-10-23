<?php
// Shared layout for the public pages (Home, Menu, About, Visit).
require_once __DIR__ . '/bootstrap.php';

try {
    db();
} catch (Throwable $ex) {
    error_log('Jaru site: ' . $ex->getMessage());
    http_response_code(503);
    exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Jaru Café</title><p style="font:16px sans-serif;padding:2rem">We\'re updating the site. Please try again in a minute.</p>');
}

/** Public page URL: page('menu') -> /menu (clean URLs via .htaccess). */
function page(string $slug = ''): string
{
    return BASE . '/' . $slug;
}

function asset(string $file): string
{
    return BASE . '/assets/' . $file . '?v=' . filemtime(ROOT . '/assets/' . $file);
}

/** Non-empty lines of a multi-line setting (e.g. hours). */
function lines(string $key): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', setting($key)))));
}

function tel(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function maps_link(): string
{
    return 'https://www.google.com/maps/search/?api=1&query='
        . rawurlencode(setting('map_query') ?: setting('cafe_name') . ', ' . setting('address'));
}

/** Small line icons (stroke uses currentColor). */
function icon(string $name): string
{
    $paths = [
        'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'ig' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".6"/>',
        'fb' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function site_header(string $title, string $active, string $description = ''): void
{
    $name = setting('cafe_name');
    $site = rtrim(setting('site_url') ?: 'https://jarucafe.shop/', '/');
    $logo = setting('logo');
    $cover = setting('cover');
    $description = $description ?: setting('tagline') . ' · ' . $name . ', ' . setting('address');
    $nav = ['' => 'Home', 'menu' => 'Menu', 'about' => 'About', 'visit' => 'Visit'];
    header('Cache-Control: no-cache');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title === '' ? "$name · " . setting('tagline') : "$title · $name") ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="theme-color" content="#37230a">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($title === '' ? $name : "$title · $name") ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($site . '/' . ($active === 'home' ? '' : $active)) ?>">
<?php if ($cover || $logo): ?><meta property="og:image" content="<?= e($site . '/uploads/' . rawurlencode($cover ?: $logo)) ?>"><?php endif ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($logo): ?><link rel="icon" href="<?= e(upload_url($logo, true)) ?>"><link rel="apple-touch-icon" href="<?= e(upload_url($logo, true)) ?>"><?php endif ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700&display=swap">
<link rel="stylesheet" href="<?= e(asset('site.css')) ?>">
<script src="<?= e(asset('site.js')) ?>" defer></script>
<?php if ($active === 'menu'): ?><script src="<?= e(asset('menu.js')) ?>" defer></script><?php endif ?>
</head>
<body class="page-<?= e($active) ?>" style="--chalk-art:url('<?= e(asset('chalk.jpg')) ?>')">
<header class="site-head">
  <div class="wrap head-in">
    <a class="brand" href="<?= e(page()) ?>">
      <?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="" width="44" height="44"><?php endif ?>
      <span><?= e($name) ?></span>
    </a>
    <nav class="site-nav" aria-label="Main">
      <?php foreach ($nav as $slug => $label): ?>
        <a href="<?= e(page($slug)) ?>"<?= ($active === $slug || ($slug === '' && $active === 'home')) ? ' aria-current="page"' : '' ?><?= $slug === '' ? ' class="nav-home"' : '' ?>><?= e($label) ?></a>
      <?php endforeach ?>
    </nav>
  </div>
</header>
<?php
}

function site_footer(): void
{
    $name = setting('cafe_name');
    $logo = setting('logo');
    ?>
<footer class="site-foot chalk">
  <div class="wrap foot-in">
    <div class="foot-brand">
      <?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="" width="64" height="64" loading="lazy"><?php endif ?>
      <p class="foot-name"><?= e($name) ?></p>
      <?php if (setting('tagline')): ?><p class="script"><?= e(setting('tagline')) ?></p><?php endif ?>
    </div>
    <div>
      <p class="foot-h">Visit us</p>
      <?php if (setting('address')): ?><p><?= e(setting('address')) ?></p><?php endif ?>
      <?php if (setting('landmark')): ?><p class="dim"><?= e(setting('landmark')) ?></p><?php endif ?>
      <p><a href="<?= e(maps_link()) ?>" rel="noopener">Get directions</a></p>
    </div>
    <?php if (lines('hours')): ?>
    <div>
      <p class="foot-h">Hours</p>
      <?php foreach (lines('hours') as $h): ?><p><?= e($h) ?></p><?php endforeach ?>
    </div>
    <?php endif ?>
    <div>
      <p class="foot-h">Contact</p>
      <?php if (setting('phone')): ?><p><a href="<?= e(tel(setting('phone'))) ?>"><?= e(setting('phone')) ?></a></p><?php endif ?>
      <?php if (setting('email')): ?><p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p><?php endif ?>
      <p class="socials">
        <?php if (setting('instagram')): ?><a href="<?= e(setting('instagram')) ?>" rel="noopener" aria-label="Instagram"><?= icon('ig') ?></a><?php endif ?>
        <?php if (setting('facebook')): ?><a href="<?= e(setting('facebook')) ?>" rel="noopener" aria-label="Facebook"><?= icon('fb') ?></a><?php endif ?>
      </p>
    </div>
  </div>
  <div class="wrap foot-bottom">
    <p>© <?= date('Y') ?> <?= e($name) ?> · Naga City</p>
    <nav aria-label="Footer"><a href="<?= e(page('menu')) ?>">Menu</a><a href="<?= e(page('about')) ?>">About</a><a href="<?= e(page('visit')) ?>">Visit</a></nav>
  </div>
</footer>
</body>
</html>
<?php
}
