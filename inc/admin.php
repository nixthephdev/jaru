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
<?php admin_fonts() ?>
<link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body style="--chalk-art:url('<?= e(BASE . '/assets/chalk.jpg?v=' . filemtime(ROOT . '/assets/chalk.jpg')) ?>')">
<header class="top chalk">
  <div class="top-in">
    <a class="brand" href="index.php">
      <?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="" width="42" height="42"><?php endif ?>
      <span><b><?= e(setting('cafe_name')) ?></b><small>Menu editor</small></span>
    </a>
    <a class="view" href="<?= e(BASE) ?>/" target="_blank" rel="noopener">View site ↗</a>
  </div>
  <nav class="tabs">
    <?php foreach ($nav as $href => $label): ?><a href="<?= $href ?>"<?= $active === $href ? ' class="on"' : '' ?>><?= e($label) ?></a><?php endforeach ?>
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

/** Same fonts as the public website. */
function admin_fonts(): void
{
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Fraunces:opsz,wght@9..144,600&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700&display=swap">' . "\n";
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
