<?php
require __DIR__ . '/inc/bootstrap.php';

try {
    $pdo = db();
    $cats = $pdo->query("SELECT * FROM categories WHERE status != 'hidden' ORDER BY sort, id")->fetchAll();
    $byCat = [];
    foreach ($pdo->query('SELECT * FROM items WHERE hidden = 0 ORDER BY sort, id') as $it) $byCat[$it['category_id']][] = $it;
    $popular = $pdo->query("SELECT i.* FROM items i JOIN categories c ON c.id = i.category_id
        WHERE i.featured = 1 AND i.hidden = 0 AND c.status = 'active' ORDER BY c.sort, i.sort")->fetchAll();
} catch (Throwable $ex) {
    error_log('Jaru menu: ' . $ex->getMessage());
    http_response_code(503);
    exit('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Jaru Café</title><p style="font:16px sans-serif;padding:2rem">The menu is being updated. Please try again in a minute.</p>');
}
$cats = array_values(array_filter($cats, fn($c) => !empty($byCat[$c['id']])));

$name = setting('cafe_name');
$site = rtrim(setting('site_url') ?: 'https://jarucafe.shop/', '/');
$logo = setting('logo');
$cover = setting('cover');
$desc = setting('tagline') . ' · Menu of ' . $name . ', ' . setting('address');
$v = fn($f) => BASE . '/assets/' . $f . '?v=' . filemtime(__DIR__ . '/assets/' . $f);

/** Prices that are set, as [['label' => '16oz', 'cents' => 14000], ...] */
$priced = fn(array $it) => array_values(array_filter(prices($it), fn($p) => $p['cents'] !== null));

header('Cache-Control: no-cache');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($name) ?> Menu</title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="theme-color" content="#363215">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($name) ?> Menu">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($site) ?>/">
<?php if ($cover || $logo): ?><meta property="og:image" content="<?= e($site . '/uploads/' . rawurlencode($cover ?: $logo)) ?>"><?php endif ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($logo): ?><link rel="icon" href="<?= e(upload_url($logo, true)) ?>"><link rel="apple-touch-icon" href="<?= e(upload_url($logo, true)) ?>"><?php endif ?>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e($v('menu.css')) ?>">
<script src="<?= e($v('menu.js')) ?>" defer></script>
</head>
<body>
<header class="head">
  <div class="cover"<?php if ($cover): ?> style="--cover:url('<?= e(upload_url($cover)) ?>')"<?php endif ?>></div>
  <div class="wrap">
    <div class="shop">
      <?php if ($logo): ?><img class="logo" src="<?= e(upload_url($logo, true)) ?>" alt="" width="84" height="84"><?php endif ?>
      <div class="shop-text">
        <h1><?= e($name) ?></h1>
        <?php if (setting('tagline')): ?><p class="tagline"><?= e(setting('tagline')) ?></p><?php endif ?>
      </div>
    </div>
    <ul class="facts">
      <?php if (setting('address')): ?><li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><?= e(setting('address')) ?></li><?php endif ?>
      <?php if (setting('hours')): ?><li class="hours"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e(setting('hours')) ?></li><?php endif ?>
      <?php if (setting('facebook')): ?><li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/></svg><a href="<?= e(setting('facebook')) ?>" rel="noopener">Facebook</a></li><?php endif ?>
    </ul>
  </div>
</header>

<div class="wrap layout">
  <div class="bar">
    <label class="search">
      <span class="sr">Search the menu</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5 21 21"/></svg>
      <input type="search" id="q" placeholder="Search menu" autocomplete="off" enterkeyhint="search">
    </label>
    <nav class="cats" aria-label="Menu categories">
      <?php if ($popular): ?><a href="#sig">Popular</a><?php endif ?>
      <?php foreach ($cats as $c): ?><a href="#c<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a><?php endforeach ?>
    </nav>
  </div>

  <main id="menu">
    <p class="offline" id="offline" hidden>You're offline. Showing the last saved menu.</p>
    <p class="count" id="count" aria-live="polite"></p>

    <?php if ($popular): ?>
    <section class="pop" id="sig" aria-labelledby="pop-h">
      <h2 id="pop-h">Popular</h2>
      <div class="pop-track">
        <?php foreach ($popular as $f): $fp = $priced($f); ?>
        <button type="button" class="sig-card" data-open="i<?= (int)$f['id'] ?>">
          <span class="pop-pic"><?php if ($f['photo'] !== ''): ?><img src="<?= e(upload_url($f['photo'], true)) ?>" alt="" loading="lazy" decoding="async" width="480" height="360"><?php endif ?>
            <?php if ($f['sold_out']): ?><span class="badge out">Sold out</span><?php endif ?></span>
          <span class="pop-name"><?= e($f['name']) ?></span>
          <?php if ($fp): ?><span class="pop-price"><?= count($fp) > 1 ? 'from ' : '' ?><?= e(peso(min(array_column($fp, 'cents')))) ?></span><?php endif ?>
        </button>
        <?php endforeach ?>
      </div>
    </section>
    <?php endif ?>

    <?php foreach ($cats as $c): $soon = $c['status'] === 'soon'; ?>
    <section class="cat<?= $soon ? ' soon' : '' ?>" id="c<?= (int)$c['id'] ?>">
      <div class="cat-head">
        <h2><?= e($c['name']) ?></h2>
        <?php if ($soon): ?><span class="badge soon">Coming soon</span><?php endif ?>
      </div>
      <?php if ($c['note'] !== ''): ?><p class="note"><?= e($c['note']) ?></p><?php endif ?>
      <ul class="items">
      <?php foreach ($byCat[$c['id']] as $it): $ps = $priced($it); ?>
        <li class="item<?= $it['sold_out'] ? ' out' : '' ?>" id="i<?= (int)$it['id'] ?>"
            data-s="<?= e(mb_strtolower($it['name'] . ' ' . $it['description'] . ' ' . $c['name'])) ?>"
            data-cat="<?= e($c['name']) ?>"<?= $soon ? ' data-soon' : '' ?><?php if ($it['photo'] !== ''): ?> data-full="<?= e(upload_url($it['photo'])) ?>"<?php endif ?>>
          <div class="txt">
            <h3><?= e($it['name']) ?></h3>
            <?php if ($it['description'] !== ''): ?><p class="desc"><?= e($it['description']) ?></p><?php endif ?>
            <div class="meta">
              <?php if ($ps && !$soon): ?><span class="price"><?php foreach ($ps as $p): ?><span><?php if ($p['label'] !== ''): ?><small><?= e($p['label']) ?></small> <?php endif ?><?= e(peso($p['cents'])) ?></span><?php endforeach ?></span><?php endif ?>
              <?php if ($it['sold_out'] && !$soon): ?><span class="badge out">Sold out · Ubos na</span><?php endif ?>
            </div>
          </div>
          <?php if ($it['photo'] !== ''): ?><img class="pic" src="<?= e(upload_url($it['photo'], true)) ?>" alt="" loading="lazy" decoding="async" width="480" height="480"><?php endif ?>
          <button type="button" class="open" aria-haspopup="dialog" aria-label="<?= e($it['name']) ?>, details"></button>
        </li>
      <?php endforeach ?>
      </ul>
    </section>
    <?php endforeach ?>
    <p class="empty" id="none" hidden>No items match your search.</p>
  </main>
</div>

<footer class="foot">
  <p><b><?= e($name) ?></b><?= setting('address') ? ' · ' . e(setting('address')) : '' ?></p>
  <p>Prices may change without notice.</p>
</footer>

<a class="to-top" id="top" href="#" aria-label="Back to top" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg></a>

<dialog id="sheet" aria-labelledby="sh-name">
  <div class="sh-grip" aria-hidden="true"></div>
  <button type="button" class="sh-close" aria-label="Close"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
  <div class="sh-ph"><img alt=""></div>
  <div class="sh-body">
    <p class="sh-cat"></p>
    <h2 id="sh-name"></h2>
    <p class="sh-desc"></p>
    <ul class="sh-prices"></ul>
    <p class="sh-flag"></p>
  </div>
</dialog>
</body>
</html>
