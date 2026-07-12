<?php $success = $this->session->flashdata('success'); $error = $this->session->flashdata('error'); ?>
<?php if ($success): ?><div class="app-flash-message" data-type="success" data-message="<?= html_escape($success) ?>" hidden></div><?php endif; ?>
<?php if ($error): ?><div class="app-flash-message" data-type="error" data-message="<?= html_escape($error) ?>" hidden></div><?php endif; ?>
