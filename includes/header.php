<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($page_title ?? APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top shadow">
    <div class="container-fluid px-3">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>index.php">
            <i class="bi bi-film text-warning"></i> <?= APP_NAME ?>
        </a>
        <div class="d-flex align-items-center gap-2 d-md-none">
            <a href="<?= BASE_URL ?>add.php" class="btn btn-sm btn-warning fw-bold"><i class="bi bi-plus-lg"></i></a>
            <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php"><i class="bi bi-collection-play me-1"></i>Films</a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a class="nav-link" href="<?= BASE_URL ?>add.php"><i class="bi bi-plus-circle me-1"></i>Add Film</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item d-md-none">
                    <a class="nav-link" href="<?= BASE_URL ?>settings.php"><i class="bi bi-sliders2 me-1"></i>Settings</a>
                </li>
                <li class="nav-item d-md-none">
                    <a class="nav-link" href="<?= BASE_URL ?>import.php"><i class="bi bi-upload me-1"></i>Import CSV</a>
                </li>
                <li class="nav-item d-md-none">
                    <a class="nav-link" href="<?= BASE_URL ?>export.php"><i class="bi bi-download me-1"></i>Export CSV</a>
                </li>
                <li class="nav-item d-md-none">
                    <a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                </li>
                <li class="nav-item dropdown d-none d-md-block">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear-fill"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>settings.php"><i class="bi bi-sliders2 me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>import.php"><i class="bi bi-upload me-2"></i>Import CSV</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>export.php"><i class="bi bi-download me-2"></i>Export CSV</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-3">
