<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title ?? 'ISP BATARA NET') ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body class="<?= html_escape($body_class ?? '') ?>">
    <div class="app-shell">
        <header class="app-topbar">
            <a href="<?= site_url('/') ?>" class="app-brand">
                <img src="<?= base_url('assets/img/logo.jpeg') ?>" alt="ISP BATARA NET">
                <span>
                    <strong>ISP BATARA NET</strong>
                    <small>Mikrotik Network Tools</small>
                </span>
            </a>
            <nav class="app-nav" aria-label="Menu utama">
                <a href="<?= site_url('/') ?>">Home</a>
                <a href="<?= site_url('customers') ?>">Pelanggan</a>
                <a href="<?= site_url('payments') ?>">Pembayaran</a>
                <a href="<?= site_url('monitoring') ?>">Monitoring</a>
                <a href="<?= site_url('profile') ?>">Profile</a>
                <a href="<?= site_url('logout') ?>">Logout</a>
            </nav>
        </header>

        <div class="app-content">
