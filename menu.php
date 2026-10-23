<?php
// Menu page: this is where the QR code points (jarucafe.shop/menu).
require __DIR__ . '/inc/site.php';

$pdo = db();
$cats = $pdo->query("SELECT * FROM categories WHERE status != 'hidden' ORDER BY sort, id")->fetchAll();
$byCat = [];
foreach ($pdo->query('SELECT * FROM items WHERE hidden = 0 ORDER BY sort, id') as $it) $byCat[$it['category_id']][] = $it;
$cats = array_values(array_filter($cats, fn($c) => !empty($byCat[$c['id']])));
$popular = $pdo->query("SELECT i.* FROM items i JOIN categories c ON c.id = i.category_id
    WHERE i.featured = 1 AND i.hidden = 0 AND c.status = 'active' ORDER BY c.sort, i.sort")->fetchAll();
$priced = fn(array $it) => array_values(array_filter(prices($it), fn($p) => $p['cents'] !== null));

site_header('Menu', 'menu', 'Menu of ' . setting('cafe_name') . ', ' . setting('address') . '. Coffee, matcha, non-coffee drinks, meals and cakes.');
?>
<main class="menu">
  <section class="page-head chalk">
    <div class="wrap">
      <p class="script">Coffee, matcha &amp; comfort food</p>
      <h1>Our menu</h1>
    </div>
  </section>

  <div class="bar">
    <div class="wrap bar-in">
      <label class="search">
        <span class="sr">Search the menu</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5 21 21"/></svg>
        <input type="search" id="q" placeholder="Search" autocomplete="off" enterkeyhint="search">
      </label>
      <nav class="cats" aria-label="Menu categories">
        <?php if ($popular): ?><a href="#sig">Popular</a><?php endif ?>
        <?php foreach ($cats as $c): ?><a href="#c<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a><?php endforeach ?>
      </nav>
    </div>
  </div>

  <div class="wrap">
    <p class="offline" id="offline" hidden>You're offline. Showing the last saved menu.</p>
    <p class="count" id="count" aria-live="polite"></p>

    <?php if ($popular): ?>
    <section class="pop" id="sig" aria-labelledby="pop-h">
      <h2 id="pop-h">Popular</h2>
      <div class="pop-track">
        <?php foreach ($popular as $f): $fp = $priced($f); ?>
        <button type="button" class="sig-card" data-open="i<?= (int)$f['id'] ?>">
          <span class="pop-pic"><?php if ($f['photo'] !== ''): ?><img src="<?= e(upload_url($f['photo'], true)) ?>" alt="" loading="lazy" decoding="async" width="480" height="480"><?php endif ?>
            <?php if ($f['sold_out']): ?><span class="tag out">Sold out</span><?php endif ?></span>
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
        <?php if ($soon): ?><span class="tag soon">Coming soon</span><?php endif ?>
        <?php if ($c['note'] !== ''): ?><p class="note"><?= e($c['note']) ?></p><?php endif ?>
      </div>
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
              <?php if ($it['sold_out'] && !$soon): ?><span class="tag out">Sold out · Ubos na</span><?php endif ?>
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
    <p class="fine">Prices may change without notice.</p>
  </div>
</main>

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
<?php site_footer();
