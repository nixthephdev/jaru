<?php
require __DIR__ . '/../inc/admin.php';

// The QR opens the Menu page, not the home page: people scanning at a table want the menu.
$url = rtrim(setting('site_url') ?: 'https://jarucafe.shop/', '/') . '/menu';
$short = preg_replace('~^https?://(www\.)?~', '', $url);
$layouts = [1 => 'Counter standee (1 per page)', 4 => 'Tables (4 per A4)', 8 => 'Small (8 per A4)'];
$n = (int)($_GET['n'] ?? 4);
if (!isset($layouts[$n])) $n = 4;
$logo = setting('logo');

admin_top('QR code', 'qr.php');
?>
<div class="noprint">
  <h1>QR code</h1>
  <p class="hint">Points to <b><?= e($url) ?></b>. Change it in <a href="settings.php">Settings</a>.</p>
  <div class="toolbar">
    <?php foreach ($layouts as $k => $label): ?><a class="btn<?= $k === $n ? ' primary' : '' ?>" href="?n=<?= $k ?>"><?= e($label) ?></a><?php endforeach ?>
  </div>
  <div class="toolbar">
    <button class="btn primary big" id="print">🖨 Print</button>
    <button class="btn big" id="png">⬇ Download QR as PNG<small>High resolution, for print shops</small></button>
  </div>
  <p class="hint">Print tip: choose A4, scale 100%, margins "Default". Test-scan one card before printing many.</p>
</div>

<?php $chalk = BASE . '/assets/chalk.jpg?v=' . filemtime(ROOT . '/assets/chalk.jpg'); ?>
<div class="sheet n<?= $n ?>" id="sheet" style="--chalk-art:url('<?= e($chalk) ?>')"
     data-url="<?= e($url) ?>" data-short="<?= e($short) ?>" data-name="<?= e(setting('cafe_name')) ?>"
     data-logo="<?= e($logo ? upload_url($logo) : '') ?>" data-chalk="<?= e($chalk) ?>">
  <?php for ($i = 0; $i < $n; $i++): ?>
  <div class="qr-card">
    <p class="qr-kicker"><?= e(setting('cafe_name')) ?></p>
    <div class="qr-box">
      <div class="qr"></div>
      <?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="" class="qr-logo"><?php endif ?>
    </div>
    <p class="qr-cta">Scan to see our menu</p>
    <p class="qr-url"><?= e($short) ?></p>
  </div>
  <?php endfor ?>
</div>
<script src="<?= e(BASE . '/assets/qrcode.js?v=' . filemtime(ROOT . '/assets/qrcode.js')) ?>" defer></script>
<?php admin_bottom();
