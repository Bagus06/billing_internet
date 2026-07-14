<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="<?= ($app_theme ?? 'dark') === 'light' ? '#eef4fb' : '#050914' ?>">
<meta name="color-scheme" content="light dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>">
<meta name="application-name" content="<?= html_escape(app_setting('isp_name', 'ISP BATARA NET')) ?>">
<meta name="app-base-url" content="<?= base_url() ?>">
<title><?= html_escape($title ?? app_setting('isp_name', 'ISP BATARA NET')) ?></title>
