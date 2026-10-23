<?php
require __DIR__ . '/../inc/admin.php';

$fields = [
    'cafe_name' => ['Café name', ''],
    'tagline' => ['Tagline', 'Shown under the logo'],
    'address' => ['Address', ''],
    'hours' => ['Opening hours', 'e.g. Mon–Sun 8am–10pm'],
    'facebook' => ['Facebook page link', 'https://facebook.com/…'],
    'site_url' => ['Menu website address', 'Used for the QR code and link previews'],
];
admin_top('Settings', 'settings.php');
?>
<h1>Settings</h1>
<form method="post" action="actions.php" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?><input type="hidden" name="a" value="save_settings">
  <?php foreach ($fields as $k => [$label, $hint]): ?>
    <label><?= e($label) ?><?php if ($hint): ?> <span class="tl"><?= e($hint) ?></span><?php endif ?>
      <input name="<?= $k ?>" value="<?= e(setting($k)) ?>" maxlength="200"<?= in_array($k, ['facebook', 'site_url'], true) ? ' type="url"' : '' ?><?= $k === 'cafe_name' ? ' required' : '' ?>></label>
  <?php endforeach ?>
  <fieldset>
    <legend>Logo</legend>
    <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'), true)) ?>" alt="" class="preview" width="120" height="120"><?php endif ?>
    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
    <p class="hint">Square works best. Used on the menu, QR cards and browser tab.</p>
  </fieldset>
  <fieldset>
    <legend>Header photo</legend>
    <?php if (setting('cover')): ?><img src="<?= e(upload_url(setting('cover'), true)) ?>" alt="" class="preview wide" width="240" height="126"><?php endif ?>
    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
    <p class="hint">A wide café photo. It is darkened behind the logo and used when the link is shared on Messenger/FB.</p>
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
