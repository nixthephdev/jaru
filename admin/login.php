<?php
require __DIR__ . '/../inc/bootstrap.php';
start_session();

try {
    $hash = setting('password_hash');
} catch (Throwable $ex) {
    error_log('Jaru admin: ' . $ex->getMessage());
    exit('<p style="font:16px sans-serif;padding:2rem">Cannot open the menu database. Make sure the <b>data/</b> folder exists and is writable (755), then reload.</p>');
}
$firstRun = $hash === '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw = (string)($_POST['password'] ?? '');
    if ($firstRun) {
        if (strlen($pw) < 8) {
            $error = 'Use at least 8 characters.';
        } elseif ($pw !== ($_POST['password2'] ?? '')) {
            $error = 'The two passwords do not match.';
        } else {
            set_setting('password_hash', password_hash($pw, PASSWORD_DEFAULT));
            log_activity('Admin password created');
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('index.php');
        }
    } elseif (login_locked($ip)) {
        $error = 'Too many wrong tries. Wait 15 minutes and try again.';
    } elseif (password_verify($pw, $hash)) {
        db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        redirect('index.php');
    } else {
        db()->prepare('INSERT INTO login_attempts (ip, at) VALUES (?, ?)')->execute([$ip, time()]);
        $error = 'Wrong password.';
    }
}
$logo = setting('logo');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Log in · Jaru admin</title>
<?php if ($logo): ?><link rel="icon" href="<?= e(upload_url($logo, true)) ?>"><?php endif ?>
<link rel="stylesheet" href="<?= e(BASE . '/assets/admin.css?v=' . filemtime(ROOT . '/assets/admin.css')) ?>">
</head>
<body class="login">
<form method="post" class="card narrow">
  <?php if ($logo): ?><img src="<?= e(upload_url($logo, true)) ?>" alt="Jaru Café" width="96" height="96" class="login-logo"><?php endif ?>
  <h1><?= $firstRun ? 'Create your admin password' : 'Menu editor' ?></h1>
  <?php if ($firstRun): ?><p class="hint">First time here. Pick a password only you know (at least 8 characters).</p><?php endif ?>
  <?php if ($error): ?><p class="alert bad" role="alert"><?= e($error) ?></p><?php endif ?>
  <?= csrf_field() ?>
  <label>Password<input type="password" name="password" required autocomplete="<?= $firstRun ? 'new-password' : 'current-password' ?>" autofocus></label>
  <?php if ($firstRun): ?><label>Type it again<input type="password" name="password2" required autocomplete="new-password"></label><?php endif ?>
  <button class="btn primary big"><?= $firstRun ? 'Save password' : 'Log in' ?></button>
</form>
</body>
</html>
