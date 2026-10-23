<?php
require __DIR__ . '/../inc/admin.php';

$id = (int)($_GET['id'] ?? 0);
$item = ['id' => 0, 'category_id' => (int)($_GET['category'] ?? 0), 'name' => '', 'description' => '',
    'prices' => '[]', 'photo' => '', 'sold_out' => 0, 'hidden' => 0, 'featured' => 0];
if ($id) {
    $st = db()->prepare('SELECT * FROM items WHERE id = ?');
    $st->execute([$id]);
    $item = $st->fetch() ?: redirect('index.php');
}
// Form re-filled after a validation error.
if (isset($_SESSION['form'])) { $item = array_merge($item, $_SESSION['form']); unset($_SESSION['form']); }
$rows = prices($item);
while (count($rows) < 3) $rows[] = ['label' => '', 'cents' => null];

admin_top($id ? 'Edit ' . $item['name'] : 'Add item', $id ? '' : 'item.php');
?>
<h1><?= $id ? 'Edit item' : 'Add item' ?></h1>
<form method="post" action="actions.php" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="a" value="save_item"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

  <label>Name <span class="tl">Pangalan</span>
    <input name="name" value="<?= e($item['name']) ?>" required maxlength="80"></label>

  <label>Category <span class="tl">Kategorya</span>
    <select name="category_id" required>
      <?php foreach (categories() as $c): ?><option value="<?= (int)$c['id'] ?>"<?= (int)$c['id'] === (int)$item['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
    </select></label>

  <label>Description <span class="tl">Paglalarawan (optional)</span>
    <textarea name="description" rows="2" maxlength="300"><?= e($item['description']) ?></textarea></label>

  <fieldset>
    <legend>Price <span class="tl">Presyo</span></legend>
    <p class="hint">One price: leave Size blank. Different sizes: fill one row per size, e.g. 12oz / 16oz.</p>
    <?php foreach ($rows as $i => $r): ?>
    <div class="price-row">
      <input name="size[]" value="<?= e($r['label']) ?>" placeholder="Size (e.g. 16oz)" maxlength="20" aria-label="Size <?= $i + 1 ?>">
      <span class="peso">₱</span><input name="price[]" value="<?= $r['cents'] === null ? '' : e(rtrim(rtrim(number_format($r['cents'] / 100, 2, '.', ''), '0'), '.')) ?>" inputmode="decimal" placeholder="0" aria-label="Price <?= $i + 1 ?>">
    </div>
    <?php endforeach ?>
  </fieldset>

  <fieldset>
    <legend>Photo <span class="tl">Litrato</span></legend>
    <?php if ($item['photo']): ?>
      <img src="<?= e(upload_url($item['photo'], true)) ?>" alt="" class="preview" width="160" height="160">
      <label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove photo</label>
    <?php endif ?>
    <img id="crop-preview" class="preview" alt="" hidden>
    <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp">
    <label class="check"><input type="checkbox" id="square" checked> Crop to square (recommended)</label>
    <p class="hint">JPG, PNG or WEBP. Big phone photos are made smaller automatically.</p>
  </fieldset>

  <label class="check big"><input type="checkbox" name="featured" value="1"<?= $item['featured'] ? ' checked' : '' ?>> ★ Popular: show in the Popular row at the top <span class="tl">Bestseller</span></label>
  <label class="check big"><input type="checkbox" name="sold_out" value="1"<?= $item['sold_out'] ? ' checked' : '' ?>> Sold out <span class="tl">Ubos na</span></label>
  <label class="check big"><input type="checkbox" name="hidden" value="1"<?= $item['hidden'] ? ' checked' : '' ?>> Hide from menu <span class="tl">Itago</span></label>

  <button class="btn primary big">Save <small>I-save</small></button>
  <a class="btn" href="index.php">Cancel</a>
</form>

<?php if ($id): ?>
<form method="post" action="actions.php" class="danger" data-confirm="Delete <?= e($item['name']) ?>? This cannot be undone.">
  <?= csrf_field() ?><input type="hidden" name="a" value="delete_item"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
  <button class="btn bad">Delete item <small>Burahin</small></button>
</form>
<?php endif ?>
<?php admin_bottom();
