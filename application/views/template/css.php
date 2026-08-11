<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<link rel="icon" href="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>">
<link rel="apple-touch-icon" href="<?= base_url(app_setting('logo_path', 'assets/img/logo.jpeg')) ?>">
<link rel="manifest" href="<?= site_url('pwa/manifest') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/style.css') ?>">
<?php $tenantPrimary = app_setting('brand_primary_color', '#1687ff'); if (!preg_match('/^#[0-9a-fA-F]{6}$/', $tenantPrimary)) $tenantPrimary = '#1687ff'; ?>
<style>:root{--laser-blue:<?= html_escape(strtolower($tenantPrimary)) ?>;--tenant-primary:<?= html_escape(strtolower($tenantPrimary)) ?>}</style>
<?php foreach (($styles ?? []) as $style): ?>
    <link rel="stylesheet" href="<?= html_escape($style) ?>">
<?php endforeach; ?>
<?php if (!empty($module_cssload) && is_file($module_cssload)): ?>
    <?php require $module_cssload; ?>
<?php endif; ?>
