<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$db_exists = file_exists(DB_PATH);
$error = $success = '';

if ($db_exists) {
    // Check if users table already has a user
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    } catch (Exception $e) {
        // DB exists but may not have the schema yet — fall through to setup
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!$username || !$password) {
        $error = 'Username and password are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            // Create DB directory if needed
            if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0755, true);

            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA journal_mode = WAL');

            // Run schema
            $schema = file_get_contents(__DIR__ . '/db/schema.sql');
            $pdo->exec($schema);

            // Insert admin user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?,?)')->execute([$username, $hash]);

            $success = 'Setup complete! <a href="' . BASE_URL . 'login.php" class="alert-link">Login now &rarr;</a>';
        } catch (Exception $e) {
            $error = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center min-vh-100">
<div class="card shadow-lg" style="width:100%;max-width:420px">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <i class="bi bi-film text-warning" style="font-size:3rem"></i>
            <h2 class="mt-2 fw-bold"><?= APP_NAME ?></h2>
            <p class="text-muted mb-0">First-time setup</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php else: ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" name="username" class="form-control form-control-lg" required
                       autocomplete="off" value="<?= e($_POST['username'] ?? '') ?>" autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm Password</label>
                <input type="password" name="confirm" class="form-control form-control-lg" required>
            </div>
            <button class="btn btn-warning btn-lg w-100 fw-bold">Create Account &amp; Setup Database</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
