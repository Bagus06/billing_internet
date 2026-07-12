        </div>
        <?php if (app_setting('footer_text', '')): ?>
            <footer class="app-footer"><?= html_escape(app_setting('footer_text')) ?> &copy; <?= date('Y') ?></footer>
        <?php endif; ?>
    </div>

    <div class="app-loader is-active" id="appLoader" aria-live="polite" aria-label="Loading">
        <div class="app-loader-panel">
            <div class="app-loader-ring" aria-hidden="true"></div>
            <strong>Memuat...</strong>
            <span>Mohon tunggu sebentar</span>
        </div>
    </div>

    <script src="<?= base_url('assets/js/i18n.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/i18n.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= filemtime(FCPATH . 'assets/js/app.js') ?>"></script>
    <?php foreach (($scripts ?? []) as $script): ?>
        <script src="<?= html_escape($script) ?>"></script>
    <?php endforeach; ?>
    <?php if (!empty($module_jsload) && is_file($module_jsload)): ?>
        <?php require $module_jsload; ?>
    <?php endif; ?>
</body>

</html>
