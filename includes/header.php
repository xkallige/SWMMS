<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/app.css" rel="stylesheet">
    <?= $extraHead ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<div class="utility-bar"><div class="container d-flex justify-content-between align-items-center"><span>Serving Port Harcourt &amp; Obio/Akpor communities</span><span class="utility-right">Community reporting <span class="utility-dot"></span> Live operations</span></div></div>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="<?= $pageClass ?? 'container py-4' ?>">
