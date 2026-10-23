<?php
require __DIR__ . '/../inc/admin.php';

$cats = categories();
$byCat = [];
foreach (db()->query('SELECT * FROM items ORDER BY sort, id') as $it) $byCat[$it['category_id']][] = $it;
$soldCount = (int)db()->query('SELECT COUNT(*) FROM items WHERE sold_out = 1')->fetchColumn();
$activity = db()->query('SELECT * FROM activity ORDER BY id DESC LIMIT 30')->fetchAll();
$status = ['active' => '', 'soon' => 'Coming soon', 'hidden' => 'Hidden'];

admin_top('Menu', 'index.php');
?>
<div class="toolbar">
  <form method="post" action="actions.php">
    <?= csrf_field() ?><input type="hidden" name="a" value="all_available">
    <button class="btn primary big" <?= $soldCount ? '' : 'disabled' ?>>✓ Mark all available<small>Lahat available na (<?= $soldCount ?> sold out)</small></button>
  </form>
  <a class="btn big" href="item.php">+ Add item<small>Magdagdag</small></a>
  <a class="btn big" href="category.php">+ Add category</a>
</div>
<p class="hint">Drag ☰ to reorder (or use ▲▼). Tap <b>Sold out</b> when an item runs out.</p>

<div class="cats" data-sort="category">
<?php foreach ($cats as $c): ?>
  <section class="cat" data-id="<?= (int)$c['id'] ?>" id="cat<?= (int)$c['id'] ?>">
    <div class="cat-head">
      <span class="handle" title="Drag to reorder" aria-hidden="true">☰</span>
      <h2><?= e($c['name']) ?><?php if ($status[$c['status']]): ?> <span class="tag <?= e($c['status']) ?>"><?= $status[$c['status']] ?></span><?php endif ?></h2>
      <span class="moves">
        <?php foreach (['up' => '▲', 'down' => '▼'] as $dir => $sym): ?>
        <form method="post" action="actions.php"><?= csrf_field() ?><input type="hidden" name="a" value="move"><input type="hidden" name="type" value="category"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn icon" aria-label="Move <?= e($c['name']) ?> <?= $dir ?>"><?= $sym ?></button></form>
        <?php endforeach ?>
      </span>
      <a class="btn small" href="category.php?id=<?= (int)$c['id'] ?>">Edit</a>
    </div>
    <ul class="items" data-sort="item">
    <?php foreach ($byCat[$c['id']] ?? [] as $it): $p = price_text($it); ?>
      <li class="row<?= $it['sold_out'] ? ' out' : '' ?><?= $it['hidden'] ? ' hid' : '' ?>" data-id="<?= (int)$it['id'] ?>" id="item<?= (int)$it['id'] ?>">
        <span class="handle" title="Drag to reorder" aria-hidden="true">☰</span>
        <?php if ($it['photo']): ?><img src="<?= e(upload_url($it['photo'], true)) ?>" alt="" width="48" height="48" loading="lazy"><?php else: ?><span class="nophoto"></span><?php endif ?>
        <a class="name" href="item.php?id=<?= (int)$it['id'] ?>">
          <b><?= $it['featured'] ? '★ ' : '' ?><?= e($it['name']) ?></b>
          <small><?= $p !== '' ? e($p) : '<i class="warn">No price yet</i>' ?><?= $it['hidden'] ? ' · Hidden' : '' ?></small>
        </a>
        <form method="post" action="actions.php">
          <?= csrf_field() ?><input type="hidden" name="a" value="toggle_sold"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
          <button class="btn sold <?= $it['sold_out'] ? 'is-out' : '' ?>" aria-pressed="<?= $it['sold_out'] ? 'true' : 'false' ?>">
            <?= $it['sold_out'] ? 'Sold out<small>Ubos na</small>' : 'Available<small>Mayroon</small>' ?>
          </button>
        </form>
        <span class="moves">
          <?php foreach (['up' => '▲', 'down' => '▼'] as $dir => $sym): ?>
          <form method="post" action="actions.php"><?= csrf_field() ?><input type="hidden" name="a" value="move"><input type="hidden" name="type" value="item"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn icon" aria-label="Move <?= e($it['name']) ?> <?= $dir ?>"><?= $sym ?></button></form>
          <?php endforeach ?>
        </span>
      </li>
    <?php endforeach ?>
    </ul>
    <?php if (empty($byCat[$c['id']])): ?><p class="hint">No items yet. <a href="item.php?category=<?= (int)$c['id'] ?>">Add one</a>.</p><?php endif ?>
  </section>
<?php endforeach ?>
</div>
<input type="hidden" id="csrf" value="<?= e(csrf_token()) ?>">

<section class="card">
  <h2>Backup</h2>
  <p class="hint">Downloads a zip of the menu database and all photos. Keep it on your phone or Google Drive.</p>
  <a class="btn big" href="backup.php">⬇ Download backup</a>
</section>

<section class="card">
  <h2>Recent changes</h2>
  <?php if (!$activity): ?><p class="hint">Nothing yet.</p><?php endif ?>
  <ol class="activity">
    <?php foreach ($activity as $a): ?><li><time><?= e(date('M j, g:i a', strtotime($a['at']))) ?></time> <?= e($a['what']) ?></li><?php endforeach ?>
  </ol>
</section>

<form method="post" action="actions.php" class="logout"><?= csrf_field() ?><input type="hidden" name="a" value="logout"><button class="btn">Log out</button></form>
<?php admin_bottom();
