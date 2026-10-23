<?php
// Shared admin layout. Every admin page except login.php includes this.
require_once __DIR__ . '/bootstrap.php';
require_login();

function admin_top(string $title, string $active = ''): void
{
    $nav = ['index.php' => 'Menu', 'item.php' => '+ Item', 'qr.php' => 'QR code', 'settings.php' => 'Settings'];
    $logo = setting('logo');
    $css = BASE . '/assets/admin.css?v=' . filemtime(ROOT . '/assets/admin.css');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Jaru admin</title>
<?php if ($logo): ?><link rel="icon" href="<?= e(upload_url($logo, true)) ?>"><?php endif ?>
<link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body>
<header class="top">
  <a class="brand" href="index.php"><?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="" width="36" height="36"><?php endif ?><span>Jaru admin</span></a>
  <nav>
    <?php foreach ($nav as $href => $label): ?><a href="<?= $href ?>"<?= $active === $href ? ' class="on"' : '' ?>><?= e($label) ?></a><?php endforeach ?>
    <a href="<?= e(BASE) ?>/menu" target="_blank" rel="noopener">View menu ↗</a>
  </nav>
</header>
<main class="wrap">
<?php
    $problems = [];
    if (!is_writable(DATA_DIR)) $problems[] = 'The <b>data/</b> folder is not writable, so changes cannot be saved.';
    if (!is_writable(UPLOAD_DIR)) $problems[] = 'The <b>uploads/</b> folder is not writable, so photos cannot be uploaded.';
    foreach ($problems as $p) echo '<p class="alert bad">' . $p . ' Ask your web host to set folder permissions to 755.</p>';
    if ($m = flash()) echo '<p class="alert ' . ($m[1] === 'bad' ? 'bad" role="alert"' : 'ok" role="status"') . '>' . e($m[0]) . '</p>';
}

function admin_bottom(bool $js = true): void
{
    if ($js) echo '<script src="' . e(BASE . '/assets/admin.js?v=' . filemtime(ROOT . '/assets/admin.js')) . '" defer></script>';
    echo "</main>\n</body>\n</html>\n";
}

function categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY sort, id')->fetchAll();
}
