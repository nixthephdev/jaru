<?php
// Every admin change is a POST to this file: CSRF checked, logged to activity, then redirect back.
require __DIR__ . '/../inc/admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_check();
if (!is_writable(DATA_DIR) || (file_exists(DB_FILE) && !is_writable(DB_FILE))) {
    flash('Cannot save: the data folder is not writable. Ask your web host to fix folder permissions.', 'bad');
    redirect('index.php');
}

$pdo = db();
$a = $_POST['a'] ?? '';
$id = (int)($_POST['id'] ?? 0);

function item(int $id): array
{
    $st = db()->prepare('SELECT * FROM items WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('That item no longer exists.', 'bad'); redirect('index.php'); }
    return $row;
}

function cat_name(int $id): string
{
    $st = db()->prepare('SELECT name FROM categories WHERE id = ?');
    $st->execute([$id]);
    return (string)$st->fetchColumn();
}

/** Rewrites sort = 1..n in the given id order. */
function resort(string $table, array $ids): void
{
    $st = db()->prepare("UPDATE $table SET sort = ? WHERE id = ?");
    foreach (array_values($ids) as $i => $rid) $st->execute([$i + 1, (int)$rid]);
}

/** Ids in the same list as $id (all categories, or the items of one category), in display order. */
function siblings(string $type, int $id): array
{
    if ($type === 'category') return db()->query('SELECT id FROM categories ORDER BY sort, id')->fetchAll(PDO::FETCH_COLUMN);
    $st = db()->prepare('SELECT id FROM items WHERE category_id = (SELECT category_id FROM items WHERE id = ?) ORDER BY sort, id');
    $st->execute([$id]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}

switch ($a) {
    case 'toggle_sold':
        $it = item($id);
        $new = $it['sold_out'] ? 0 : 1;
        $pdo->prepare('UPDATE items SET sold_out = ? WHERE id = ?')->execute([$new, $id]);
        log_activity($it['name'] . ($new ? ' marked sold out' : ' marked available'));
        redirect('index.php#item' . $id);

    case 'all_available':
        $n = $pdo->exec('UPDATE items SET sold_out = 0 WHERE sold_out = 1');
        log_activity("Marked all available ($n items)");
        flash("All items are available again ($n updated).");
        redirect('index.php');

    case 'move':
        $type = $_POST['type'] === 'category' ? 'category' : 'item';
        $ids = siblings($type, $id);
        $i = array_search($id, array_map('intval', $ids), true);
        $j = $i === false ? false : $i + ($_POST['dir'] === 'up' ? -1 : 1);
        if ($j !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            resort($type === 'category' ? 'categories' : 'items', $ids);
            log_activity('Reordered ' . ($type === 'category' ? 'categories' : 'items in ' . cat_name((int)item($id)['category_id'])));
        }
        redirect('index.php#' . ($type === 'category' ? 'cat' : 'item') . $id);

    case 'reorder': // drag and drop, called with fetch()
        $type = $_POST['type'] === 'category' ? 'category' : 'item';
        $ids = array_map('intval', (array)($_POST['ids'] ?? []));
        $current = array_map('intval', siblings($type, $ids[0] ?? 0));
        $sorted = $ids; sort($sorted); sort($current);
        if (!$ids || $sorted !== $current) { http_response_code(400); exit('bad order'); }
        resort($type === 'category' ? 'categories' : 'items', $ids);
        log_activity('Reordered ' . ($type === 'category' ? 'categories' : 'items in ' . cat_name((int)item($ids[0])['category_id'])));
        exit('ok');

    case 'save_item':
        $old = $id ? item($id) : null;
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'category_id' => (int)($_POST['category_id'] ?? 0),
            'description' => trim((string)($_POST['description'] ?? '')),
            'sold_out' => empty($_POST['sold_out']) ? 0 : 1,
            'hidden' => empty($_POST['hidden']) ? 0 : 1,
            'featured' => empty($_POST['featured']) ? 0 : 1,
        ];
        $rows = [];
        $error = '';
        foreach ((array)($_POST['price'] ?? []) as $i => $raw) {
            $label = trim((string)($_POST['size'][$i] ?? ''));
            $cents = parse_peso((string)$raw);
            if ($cents === false) $error = 'Price "' . $raw . '" is not a number. Use digits only, like 120 or 120.50.';
            if ($label !== '' || $cents !== null) $rows[] = ['label' => mb_substr($label, 0, 20), 'cents' => $cents === false ? null : $cents];
        }
        if (!$rows) $rows[] = ['label' => '', 'cents' => null];
        $data['prices'] = json_encode($rows, JSON_UNESCAPED_UNICODE);
        if ($data['name'] === '') $error = 'Please type a name.';
        if (cat_name($data['category_id']) === '') $error = 'Please pick a category.';

        $data['photo'] = $old['photo'] ?? '';
        if (!$error && !empty($_FILES['photo']['name'])) {
            try { $data['photo'] = save_upload($_FILES['photo']); } catch (RuntimeException $ex) { $error = $ex->getMessage(); }
        } elseif (!empty($_POST['remove_photo'])) {
            $data['photo'] = '';
        }
        if ($error) {
            $_SESSION['form'] = $data;
            flash($error, 'bad');
            redirect('item.php' . ($id ? "?id=$id" : ''));
        }

        if ($old) {
            $pdo->prepare('UPDATE items SET name = :name, category_id = :category_id, description = :description, prices = :prices,
                photo = :photo, sold_out = :sold_out, hidden = :hidden, featured = :featured WHERE id = ' . $id)->execute($data);
            if ($old['photo'] !== $data['photo']) delete_photo($old['photo']);
            $changes = [];
            if ($old['name'] !== $data['name']) $changes[] = 'renamed from "' . $old['name'] . '"';
            $op = price_text($old) ?: 'none'; $np = price_text($data) ?: 'none';
            if ($op !== $np) $changes[] = "price $op → $np";
            if ((int)$old['category_id'] !== $data['category_id']) $changes[] = 'moved to ' . cat_name($data['category_id']);
            if ($old['description'] !== $data['description']) $changes[] = 'description';
            if ($old['photo'] !== $data['photo']) $changes[] = $data['photo'] ? 'new photo' : 'photo removed';
            if ((int)$old['sold_out'] !== $data['sold_out']) $changes[] = $data['sold_out'] ? 'sold out' : 'available';
            if ((int)$old['hidden'] !== $data['hidden']) $changes[] = $data['hidden'] ? 'hidden' : 'shown';
            if ((int)$old['featured'] !== $data['featured']) $changes[] = $data['featured'] ? 'added to Popular' : 'removed from Popular';
            if ($changes) log_activity('Edited ' . $data['name'] . ': ' . implode(', ', $changes));
        } else {
            $st = $pdo->prepare('SELECT COALESCE(MAX(sort), 0) + 1 FROM items WHERE category_id = ?');
            $st->execute([$data['category_id']]);
            $data['sort'] = (int)$st->fetchColumn();
            $pdo->prepare('INSERT INTO items (name, category_id, description, prices, photo, sold_out, hidden, featured, sort)
                VALUES (:name, :category_id, :description, :prices, :photo, :sold_out, :hidden, :featured, :sort)')->execute($data);
            $id = (int)$pdo->lastInsertId();
            log_activity('Added ' . $data['name'] . ' to ' . cat_name($data['category_id']) . (price_text($data) ? ' (' . price_text($data) . ')' : ''));
        }
        flash('Saved ' . $data['name'] . '.');
        redirect('index.php#item' . $id);

    case 'delete_item':
        $it = item($id);
        $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
        delete_photo($it['photo']);
        log_activity('Deleted ' . $it['name']);
        flash('Deleted ' . $it['name'] . '.');
        redirect('index.php');

    case 'save_category':
        $name = trim((string)($_POST['name'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));
        $status = in_array($_POST['status'] ?? '', ['active', 'soon', 'hidden'], true) ? $_POST['status'] : 'active';
        if ($name === '') { flash('Please type a category name.', 'bad'); redirect('category.php' . ($id ? "?id=$id" : '')); }
        if ($id) {
            $oldName = cat_name($id);
            $pdo->prepare('UPDATE categories SET name = ?, note = ?, status = ? WHERE id = ?')->execute([$name, $note, $status, $id]);
            log_activity("Edited category $name" . ($oldName !== $name ? " (was $oldName)" : '') . ", status: $status");
        } else {
            $pdo->prepare('INSERT INTO categories (name, note, status, sort) VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort), 0) + 1 FROM categories))')
                ->execute([$name, $note, $status]);
            $id = (int)$pdo->lastInsertId();
            log_activity("Added category $name");
        }
        flash("Saved $name.");
        redirect('index.php#cat' . $id);

    case 'delete_category':
        $name = cat_name($id);
        $st = $pdo->prepare('SELECT photo FROM items WHERE category_id = ?');
        $st->execute([$id]);
        $photos = $st->fetchAll(PDO::FETCH_COLUMN);
        $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]); // items cascade
        foreach ($photos as $p) delete_photo($p);
        log_activity("Deleted category $name" . ($photos ? ' and its ' . count($photos) . ' items' : ''));
        flash("Deleted $name.");
        redirect('index.php');

    case 'save_settings':
        $changed = [];
        foreach (['cafe_name', 'tagline', 'address', 'hours', 'facebook', 'site_url'] as $k) {
            $v = trim((string)($_POST[$k] ?? ''));
            if (in_array($k, ['facebook', 'site_url'], true) && $v !== '' && !preg_match('~^https?://[^\s<>"]+$~i', $v)) {
                flash('Links must start with https://', 'bad'); redirect('settings.php');
            }
            if ($k === 'cafe_name' && $v === '') continue;
            if ($v !== setting($k)) { set_setting($k, $v); $changed[] = str_replace('_', ' ', $k); }
        }
        foreach (['logo', 'cover'] as $k) {
            if (empty($_FILES[$k]['name'])) continue;
            try {
                $old = setting($k);
                set_setting($k, save_upload($_FILES[$k]));
                delete_photo($old);
                $changed[] = $k === 'cover' ? 'header photo' : 'logo';
            } catch (RuntimeException $ex) {
                flash($ex->getMessage(), 'bad'); redirect('settings.php');
            }
        }
        if ($changed) log_activity('Settings changed: ' . implode(', ', $changed));
        flash($changed ? 'Settings saved.' : 'Nothing changed.');
        redirect('settings.php');

    case 'change_password':
        if (!password_verify((string)($_POST['current'] ?? ''), setting('password_hash'))) {
            flash('Current password is wrong.', 'bad');
        } elseif (strlen((string)$_POST['new']) < 8 || $_POST['new'] !== ($_POST['new2'] ?? '')) {
            flash('New passwords must match and be at least 8 characters.', 'bad');
        } else {
            set_setting('password_hash', password_hash((string)$_POST['new'], PASSWORD_DEFAULT));
            log_activity('Password changed');
            flash('Password changed.');
        }
        redirect('settings.php');

    case 'logout':
        $_SESSION = [];
        session_destroy();
        redirect('login.php');
}
redirect('index.php');
