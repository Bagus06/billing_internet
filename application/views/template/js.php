<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= base_url('assets/js/i18n.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/i18n.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
<?php foreach (($scripts ?? []) as $script): ?>
    <script src="<?= html_escape($script) ?>"></script>
<?php endforeach; ?>
<?php if (!empty($module_jsload) && is_file($module_jsload)): ?>
    <?php require $module_jsload; ?>
<?php endif; ?>
