<?php
// About / Our story. Text comes from Settings > "Our story" (one paragraph per line).
require __DIR__ . '/inc/site.php';

$story = lines('about_text');
$cover = setting('cover');
// A few menu photos make a small gallery.
$photos = db()->query("SELECT i.name, i.photo FROM items i JOIN categories c ON c.id = i.category_id
    WHERE i.photo != '' AND i.hidden = 0 AND c.status = 'active' ORDER BY i.featured DESC, c.sort, i.sort LIMIT 6")->fetchAll();

site_header('About', 'about', $story[0] ?? '');
?>
<main>
  <section class="page-head chalk">
    <div class="wrap">
      <p class="script"><?= e(setting('tagline')) ?></p>
      <h1>Our story</h1>
    </div>
  </section>

  <?php if ($cover): ?>
  <figure class="wrap wide-ph"><img src="<?= e(upload_url($cover)) ?>" alt="Inside <?= e(setting('cafe_name')) ?>" width="1200" height="630"></figure>
  <?php endif ?>

  <section class="wrap prose">
    <?php foreach ($story as $p): ?><p><?= e($p) ?></p><?php endforeach ?>
    <p><a class="link" href="<?= e(page('menu')) ?>">See what we serve <span aria-hidden="true">→</span></a></p>
  </section>

  <?php if ($photos): ?>
  <section class="wrap gallery" aria-label="From our menu">
    <?php foreach ($photos as $ph): ?>
    <figure><img src="<?= e(upload_url($ph['photo'], true)) ?>" alt="<?= e($ph['name']) ?>" loading="lazy" width="480" height="480"><figcaption><?= e($ph['name']) ?></figcaption></figure>
    <?php endforeach ?>
  </section>
  <?php endif ?>
</main>
<?php site_footer();
