<?php
defined('BASEPATH') or exit('No direct script access allowed');
$data = isset($view_data) && is_array($view_data) ? $view_data : [];
$moduleName = preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($data['module_name'] ?? '')));
$modulePath = $moduleName !== '' ? APPPATH . 'modules/' . $moduleName . '/' : '';
$data['module_name'] = $moduleName;
$data['module_cssload'] = $modulePath !== '' && is_file($modulePath . 'cssload.php') ? $modulePath . 'cssload.php' : null;
$data['module_jsload'] = $modulePath !== '' && is_file($modulePath . 'jsload.php') ? $modulePath . 'jsload.php' : null;
$defaultTheme = in_array(app_setting('default_theme', 'dark'), ['light', 'dark'], true) ? app_setting('default_theme', 'dark') : 'dark';
$authUser = $this->session->userdata('auth_user');
$userTheme = is_array($authUser) && isset($authUser['preferred_theme']) && in_array($authUser['preferred_theme'], ['light', 'dark'], true) ? $authUser['preferred_theme'] : null;
$cookieTheme = isset($_COOKIE['app_theme']) && in_array($_COOKIE['app_theme'], ['light', 'dark'], true) ? $_COOKIE['app_theme'] : null;
$data['app_theme'] = $userTheme ?: ($authUser ? $defaultTheme : ($cookieTheme ?: $defaultTheme));
$data['theme_source'] = $authUser ? 'user' : 'device';
extract($data, EXTR_SKIP);
?>
<!doctype html>
<html lang="<?= app_language() ?>" data-app-language="<?= app_language() ?>" data-app-theme="<?= html_escape($app_theme) ?>" data-default-theme="<?= html_escape($defaultTheme) ?>" data-theme-source="<?= html_escape($theme_source) ?>" data-theme-save-url="<?= site_url('preferences/theme') ?>">
<head>
    <?php $this->load->view('template/meta', $data); ?>
    <?php $this->load->view('template/css', $data); ?>
</head>
<body class="<?= html_escape($body_class ?? '') ?>">
    <?php if (empty($hide_navigation)): ?>
        <div class="app-shell">
            <?php $this->load->view('template/header', $data); ?>
            <div class="app-content">
                <?php $this->load->view($content_view, $data); ?>
            </div>
            <?php $this->load->view('template/footer', $data); ?>
        </div>
    <?php else: ?>
        <?php $this->load->view($content_view, $data); ?>
    <?php endif; ?>

    <div class="app-loader is-active" id="appLoader" aria-live="polite" aria-label="Loading">
        <div class="app-loader-panel"><div class="app-loader-ring" aria-hidden="true"></div><strong>Memuat...</strong><span>Mohon tunggu sebentar</span></div>
    </div>
    <?php $this->load->view('template/js', $data); ?>
</body>
</html>
