<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$db      = db();
$msg     = $error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_watcher') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            try {
                $db->prepare('INSERT INTO watchers (name) VALUES (?)')->execute([$name]);
                $msg = 'Watcher added.';
            } catch (Exception $e) {
                $error = 'Name already exists.';
            }
        }

    } elseif ($action === 'delete_watcher') {
        $id = (int)($_POST['watcher_id'] ?? 0);
        if ($id) $db->prepare('DELETE FROM watchers WHERE id=?')->execute([$id]);
        $msg = 'Watcher removed.';

    } elseif ($action === 'rename_watcher') {
        $id   = (int)($_POST['watcher_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id && $name) {
            $db->prepare('UPDATE watchers SET name=? WHERE id=?')->execute([$name, $id]);
            $msg = 'Watcher renamed.';
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $user    = $db->prepare('SELECT password_hash FROM users WHERE id=?');
        $user->execute([$_SESSION['user_id']]);
        $user = $user->fetch();
        if (!password_verify($current, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $db->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            $msg = 'Password changed.';
        }
    }
}

$watchers   = $db->query('SELECT * FROM watchers ORDER BY name')->fetchAll();
$page_title = 'Settings — ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container px-3 pb-5" style="max-width:600px">
<h4 class="fw-bold mb-4">Settings</h4>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible"><?= e($msg) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

<!-- Watchers -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold bg-dark text-white"><i class="bi bi-people me-2"></i>Watchers</div>
    <div class="card-body">
        <table class="table table-sm mb-3">
            <tbody>
            <?php foreach ($watchers as $w): ?>
            <tr>
                <td class="align-middle fw-semibold"><?= e($w['name']) ?></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-secondary me-1"
                            onclick="renameWatcher(<?= $w['id'] ?>, '<?= e(addslashes($w['name'])) ?>')">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" class="d-inline" onsubmit="return confirm('Remove <?= e(addslashes($w['name'])) ?>?')">
                        <input type="hidden" name="action" value="delete_watcher">
                        <input type="hidden" name="watcher_id" value="<?= $w['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" class="d-flex gap-2">
            <input type="hidden" name="action" value="add_watcher">
            <input type="text" name="name" class="form-control" placeholder="New watcher name…" required>
            <button class="btn btn-warning fw-semibold text-nowrap"><i class="bi bi-plus-lg me-1"></i>Add</button>
        </form>
    </div>
</div>

<!-- Change Password -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold bg-dark text-white"><i class="bi bi-lock me-2"></i>Change Password</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="current" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm" class="form-control" required>
            </div>
            <button class="btn btn-warning fw-semibold">Update Password</button>
        </form>
    </div>
</div>

</div>

<!-- Rename watcher modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post">
            <input type="hidden" name="action" value="rename_watcher">
            <input type="hidden" name="watcher_id" id="renameId">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Rename Watcher</h5></div>
                <div class="modal-body">
                    <input type="text" name="name" id="renameName" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = <<<'JS'
function renameWatcher(id, name) {
    document.getElementById('renameId').value = id;
    document.getElementById('renameName').value = name;
    new bootstrap.Modal(document.getElementById('renameModal')).show();
}
JS;
require_once __DIR__ . '/includes/footer.php';
?>
