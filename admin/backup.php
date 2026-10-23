<?php
// Streams a zip of data/menu.sqlite + uploads/. Read-only, so a plain GET link is fine.
require __DIR__ . '/../inc/admin.php';

if (!class_exists('ZipArchive')) {
    flash('Backup needs the PHP "zip" extension. Turn it on in cPanel > Select PHP Version > Extensions.', 'bad');
    redirect('index.php');
}

$tmpDir = sys_get_temp_dir();
$snap = tempnam($tmpDir, 'jarudb');
$zipFile = tempnam($tmpDir, 'jaruzip');
@unlink($snap);
try {
    // Consistent copy even if someone is saving at the same moment.
    db()->exec('VACUUM INTO ' . db()->quote($snap));
} catch (Throwable $ex) {
    copy(DB_FILE, $snap);
}

$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::OVERWRITE);
$zip->addFile($snap, 'data/menu.sqlite');
foreach (glob(UPLOAD_DIR . '/*.jpg') ?: [] as $f) $zip->addFile($f, 'uploads/' . basename($f));
$zip->close();

log_activity('Backup downloaded');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="jaru-menu-backup-' . date('Y-m-d') . '.zip"');
header('Content-Length: ' . filesize($zipFile));
header('Cache-Control: no-store');
readfile($zipFile);
@unlink($zipFile);
@unlink($snap);
