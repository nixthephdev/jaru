<?php
require __DIR__ . '/../inc/admin.php';

// [label, hint, type]. Every empty field is simply hidden on the website.
$fields = [
    'cafe_name' => ['Café name', '', 'text'],
    'tagline' => ['Tagline', 'Big headline on the home page', 'text'],
    'address' => ['Address', 'e.g. 123 Magsaysay Ave, Naga City', 'text'],
    'landmark' => ['Landmark', 'Helps people find you, e.g. "Beside Rosam, near SSS"', 'text'],
    'map_query' => ['Map location', 'Optional. What Google Maps should search, if the address alone finds the wrong place', 'text'],
    'hours' => ['Opening hours', 'One line each, e.g. "Mon–Fri 8am–9pm"', 'textarea'],
    'phone' => ['Phone', 'e.g. 0917 123 4567', 'tel'],
    'email' => ['Email', '', 'email'],
    'facebook' => ['Facebook page link', 'https://facebook.com/…', 'url'],
    'instagram' => ['Instagram link', 'https://instagram.com/…', 'url'],
    'about_text' => ['Our story', 'Shown on the About page. One paragraph per line. The first line also appears on the home page.', 'textarea'],
    'site_url' => ['Website address', 'Used for the QR code and link previews', 'url'],
];
admin_top('Settings', 'settings.php');
?>
<h1>Settings</h1>
<form method="post" action="actions.php" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?><input type="hidden" name="a" value="save_settings">
  <?php foreach ($fields as $k => [$label, $hint, $type]): ?>
    <label><?= e($label) ?><?php if ($hint): ?> <span class="tl"><?= e($hint) ?></span><?php endif ?>
      <?php if ($type === 'textarea'): ?>
        <textarea name="<?= $k ?>" rows="<?= $k === 'about_text' ? 6 : 3 ?>" maxlength="3000"><?= e(setting($k)) ?></textarea>
      <?php else: ?>
        <input name="<?= $k ?>" type="<?= $type ?>" value="<?= e(setting($k)) ?>" maxlength="200"<?= $k === 'cafe_name' ? ' required' : '' ?>>
      <?php endif ?>
    </label>
  <?php endforeach ?>
  <fieldset>
    <legend>Logo</legend>
    <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'), true)) ?>" alt="" class="preview" width="120" height="120"><?php endif ?>
    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
    <p class="hint">Square works best. Used in the site header, QR cards and browser tab.</p>
  </fieldset>
  <fieldset>
    <legend>Home page photo</legend>
    <?php if (setting('hero')): ?><img src="<?= e(upload_url(setting('hero'), true)) ?>" alt="" class="preview" width="120" height="150"><?php endif ?>
    <input type="file" name="hero" accept="image/jpeg,image/png,image/webp">
    <p class="hint">A tall photo (portrait) of your best drink. Shown big on the home page.</p>
  </fieldset>
  <fieldset>
    <legend>Café photo</legend>
    <?php if (setting('cover')): ?><img src="<?= e(upload_url(setting('cover'), true)) ?>" alt="" class="preview wide" width="240" height="126"><?php endif ?>
    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
    <p class="hint">A wide photo of the café. Used on the About page and when the link is shared on Messenger/FB.</p>
  </fieldset>
  <button class="btn primary big">Save settings</button>
</form>

<h2>Change password</h2>
<form method="post" action="actions.php" class="card form">
  <?= csrf_field() ?><input type="hidden" name="a" value="change_password">
  <label>Current password<input type="password" name="current" required autocomplete="current-password"></label>
  <label>New password <span class="tl">at least 8 characters</span><input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
  <label>New password again<input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
  <button class="btn primary big">Change password</button>
</form>
<?php admin_bottom();
