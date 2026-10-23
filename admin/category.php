<?php
require __DIR__ . '/../inc/admin.php';

$id = (int)($_GET['id'] ?? 0);
$cat = ['id' => 0, 'name' => '', 'note' => '', 'status' => 'active'];
$count = 0;
if ($id) {
    $st = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $st->execute([$id]);
    $cat = $st->fetch() ?: redirect('index.php');
    $st = db()->prepare('SELECT COUNT(*) FROM items WHERE category_id = ?');
    $st->execute([$id]);
    $count = (int)$st->fetchColumn();
}
$statuses = ['active' => 'Show on menu', 'soon' => 'Coming soon (shown, not orderable yet)', 'hidden' => 'Hidden'];

admin_top($id ? 'Edit category' : 'Add category');
?>
<h1><?= $id ? 'Edit category' : 'Add category' ?></h1>
<form method="post" action="actions.php" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="a" value="save_category"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
  <label>Name <input name="name" value="<?= e($cat['name']) ?>" required maxlength="60"></label>
  <label>Short note <span class="tl">optional, e.g. "Served with rice"</span>
    <input name="note" value="<?= e($cat['note']) ?>" maxlength="120"></label>
  <fieldset>
    <legend>Status</legend>
    <?php foreach ($statuses as $k => $label): ?>
      <label class="check big"><input type="radio" name="status" value="<?= $k ?>"<?= $cat['status'] === $k ? ' checked' : '' ?>> <?= e($label) ?></label>
    <?php endforeach ?>
  </fieldset>
  <button class="btn primary big">Save <small>I-save</small></button>
  <a class="btn" href="index.php">Cancel</a>
</form>

<?php if ($id): ?>
<form method="post" action="actions.php" class="danger" data-confirm="Delete the category <?= e($cat['name']) ?><?= $count ? " and its $count items" : '' ?>? This cannot be undone.">
  <?= csrf_field() ?><input type="hidden" name="a" value="delete_category"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
  <button class="btn bad">Delete category<?= $count ? " and its $count items" : '' ?></button>
</form>
<?php endif ?>
<?php admin_bottom();
