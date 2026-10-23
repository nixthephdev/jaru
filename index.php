<?php
// Home page.
require __DIR__ . '/inc/site.php';

$pdo = db();
$popular = $pdo->query("SELECT i.* FROM items i JOIN categories c ON c.id = i.category_id
    WHERE i.featured = 1 AND i.hidden = 0 AND c.status = 'active' ORDER BY c.sort, i.sort LIMIT 6")->fetchAll();
$soon = $pdo->query("SELECT c.*, (SELECT photo FROM items WHERE category_id = c.id AND hidden = 0 AND photo != '' ORDER BY sort, id LIMIT 1) AS photo,
        (SELECT COUNT(*) FROM items WHERE category_id = c.id AND hidden = 0) AS n
    FROM categories c WHERE c.status = 'soon' AND EXISTS (SELECT 1 FROM items WHERE category_id = c.id AND hidden = 0) ORDER BY c.sort")->fetchAll();
$priced = fn(array $it) => array_values(array_filter(prices($it), fn($p) => $p['cents'] !== null));
$story = lines('about_text');
$hero = setting('hero') ?: setting('cover');
$cover = setting('cover');

site_header('', 'home');
?>
<main>
  <section class="hero chalk">
    <div class="wrap hero-in">
      <div class="hero-txt">
        <p class="script">Welcome to <?= e(setting('cafe_name')) ?></p>
        <h1><?= e(setting('tagline') ?: setting('cafe_name')) ?></h1>
        <?php if (setting('address')): ?>
        <p class="hero-addr"><?= icon('pin') ?><span><?= e(setting('address')) ?><?php if (setting('landmark')): ?><br><small><?= e(setting('landmark')) ?></small><?php endif ?></span></p>
        <?php endif ?>
        <div class="ctas">
          <a class="btn" href="<?= e(page('menu')) ?>">View our menu</a>
          <a class="btn ghost" href="<?= e(page('visit')) ?>">Find us</a>
        </div>
      </div>
      <?php if ($hero): ?>
      <div class="hero-art"><img class="arch" src="<?= e(upload_url($hero)) ?>" alt="" width="1200" height="1500" fetchpriority="high"></div>
      <?php endif ?>
    </div>
  </section>

  <section class="wrap quick" aria-label="Opening hours and contact">
    <?php if (lines('hours')): ?>
    <div class="q-card">
      <span class="q-ic"><?= icon('clock') ?></span>
      <div><p class="q-h">Opening hours</p><?php foreach (lines('hours') as $h): ?><p><?= e($h) ?></p><?php endforeach ?></div>
    </div>
    <?php endif ?>
    <a class="q-card" href="<?= e(maps_link()) ?>" rel="noopener">
      <span class="q-ic"><?= icon('pin') ?></span>
      <div><p class="q-h">Find us</p><p><?= e(setting('address')) ?></p><?php if (setting('landmark')): ?><p class="dim"><?= e(setting('landmark')) ?></p><?php endif ?></div>
    </a>
    <?php if (setting('phone')): ?>
    <a class="q-card" href="<?= e(tel(setting('phone'))) ?>">
      <span class="q-ic"><?= icon('phone') ?></span>
      <div><p class="q-h">Call us</p><p><?= e(setting('phone')) ?></p><?php if (setting('email')): ?><p class="dim"><?= e(setting('email')) ?></p><?php endif ?></div>
    </a>
    <?php endif ?>
  </section>

  <?php if ($popular): ?>
  <section class="wrap block">
    <header class="sec-head">
      <p class="script">Customer favourites</p>
      <h2>Our best-loved picks</h2>
    </header>
    <ul class="tiles">
      <?php foreach ($popular as $f): $fp = $priced($f); ?>
      <li>
        <a href="<?= e(page('menu')) ?>#i<?= (int)$f['id'] ?>">
          <span class="tile-ph"><?php if ($f['photo'] !== ''): ?><img src="<?= e(upload_url($f['photo'], true)) ?>" alt="" loading="lazy" decoding="async" width="480" height="480"><?php endif ?></span>
          <span class="tile-txt">
            <span class="tile-name"><?= e($f['name']) ?></span>
            <?php if ($fp): ?><span class="tile-price"><?= count($fp) > 1 ? 'from ' : '' ?><?= e(peso(min(array_column($fp, 'cents')))) ?></span><?php endif ?>
          </span>
        </a>
      </li>
      <?php endforeach ?>
    </ul>
    <p class="center"><a class="btn" href="<?= e(page('menu')) ?>">See the full menu</a></p>
  </section>
  <?php endif ?>

  <?php if ($soon): ?>
  <section class="soon-band chalk">
    <div class="wrap">
      <header class="sec-head">
        <p class="script">Something new is brewing</p>
        <h2>Coming soon</h2>
      </header>
      <div class="soon-grid">
        <?php foreach ($soon as $s): ?>
        <a class="soon-card" href="<?= e(page('menu')) ?>#c<?= (int)$s['id'] ?>">
          <?php if ($s['photo']): ?><img src="<?= e(upload_url($s['photo'], true)) ?>" alt="" loading="lazy" width="480" height="480"><?php endif ?>
          <span>
            <span class="soon-name"><?= e($s['name']) ?></span>
            <?php if ($s['note'] !== ''): ?><span class="soon-note"><?= e($s['note']) ?></span><?php endif ?>
            <span class="soon-n"><?= (int)$s['n'] ?> drinks</span>
          </span>
        </a>
        <?php endforeach ?>
      </div>
    </div>
  </section>
  <?php endif ?>

  <?php if ($story): ?>
  <section class="wrap split">
    <?php if ($cover): ?><figure><img class="arch" src="<?= e(upload_url($cover)) ?>" alt="Inside <?= e(setting('cafe_name')) ?>" loading="lazy" width="1200" height="630"></figure><?php endif ?>
    <div>
      <p class="script">Our story</p>
      <h2><?= e(setting('cafe_name')) ?></h2>
      <?php foreach (array_slice($story, 0, 2) as $p): ?><p class="body"><?= e($p) ?></p><?php endforeach ?>
      <a class="link" href="<?= e(page('about')) ?>">Read our story <?= icon('arrow') ?></a>
    </div>
  </section>
  <?php endif ?>
</main>
<?php site_footer();
