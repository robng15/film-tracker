<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id && ($_GET['csrf'] ?? '') == ($_SESSION['user_id'] ?? '')) {
    db()->prepare('DELETE FROM films WHERE id=?')->execute([$id]);
}
header('Location: ' . BASE_URL . 'index.php');
exit;
