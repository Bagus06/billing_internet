<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (app_setting('footer_text', '')): ?>
    <footer class="app-footer"><?= html_escape(app_setting('footer_text')) ?> &copy; <?= date('Y') ?></footer>
<?php endif; ?>
