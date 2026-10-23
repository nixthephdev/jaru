<?php
// Visit us: address, map, hours, contact. All from Settings; empty fields are hidden.
require __DIR__ . '/inc/site.php';

$query = setting('map_query') ?: setting('cafe_name') . ', ' . setting('address');
$embed = 'https://www.google.com/maps?output=embed&q=' . rawurlencode($query);
$hours = lines('hours');
$phone = setting('phone');

site_header('Visit', 'visit', 'Find ' . setting('cafe_name') . ' at ' . setting('address') . '. Opening hours, directions and contact.');
?>
<main>
  <section class="page-head chalk">
    <div class="wrap">
      <p class="script">We'd love to see you</p>
      <h1>Visit us</h1>
    </div>
  </section>

  <section class="wrap visit">
    <div class="visit-info">
      <div class="v-card">
        <span class="q-ic"><?= icon('pin') ?></span>
        <div>
          <p class="q-h">Address</p>
          <p><?= e(setting('address')) ?></p>
          <?php if (setting('landmark')): ?><p class="dim"><?= e(setting('landmark')) ?></p><?php endif ?>
          <a class="btn small" href="<?= e(maps_link()) ?>" rel="noopener">Get directions</a>
        </div>
      </div>
      <div class="v-card">
        <span class="q-ic"><?= icon('clock') ?></span>
        <div>
          <p class="q-h">Opening hours</p>
          <?php if ($hours): ?>
            <?php foreach ($hours as $h): ?><p><?= e($h) ?></p><?php endforeach ?>
          <?php else: ?>
            <p class="dim">Opening hours will be posted soon.</p>
          <?php endif ?>
        </div>
      </div>
      <?php if ($phone || setting('email') || setting('facebook') || setting('instagram')): ?>
      <div class="v-card">
        <span class="q-ic"><?= icon('phone') ?></span>
        <div>
          <p class="q-h">Contact</p>
          <?php if ($phone): ?><p><a href="<?= e(tel($phone)) ?>"><?= e($phone) ?></a></p><?php endif ?>
          <?php if (setting('email')): ?><p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p><?php endif ?>
          <?php if (setting('instagram')): ?><p><a href="<?= e(setting('instagram')) ?>" rel="noopener">Instagram</a></p><?php endif ?>
          <?php if (setting('facebook')): ?><p><a href="<?= e(setting('facebook')) ?>" rel="noopener">Facebook</a></p><?php endif ?>
        </div>
      </div>
      <?php endif ?>
    </div>
    <div class="map">
      <iframe src="<?= e($embed) ?>" title="Map to <?= e(setting('cafe_name')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </section>
</main>
<?php site_footer();
