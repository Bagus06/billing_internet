<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script src="<?= base_url('application/modules/customers/assets/js/customers.js') ?>?v=<?= filemtime(APPPATH . 'modules/customers/assets/js/customers.js') ?>"></script>
<script src="<?= base_url('application/modules/customers/assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
<script src="<?= base_url('application/modules/customers/assets/vendor/select2/select2.min.js') ?>"></script>
<script src="<?= base_url('application/modules/customers/assets/js/customer-maps.js') ?>?v=<?= filemtime(APPPATH . 'modules/customers/assets/js/customer-maps.js') ?>"></script>
<script src="<?= base_url('application/modules/customers/assets/js/customer-ont-select.js') ?>?v=<?= filemtime(APPPATH . 'modules/customers/assets/js/customer-ont-select.js') ?>"></script>
<?php $googleMapsKey = trim((string) ($_ENV['GOOGLE_MAPS_API_KEY'] ?? getenv('GOOGLE_MAPS_API_KEY') ?: '')); ?>
<?php if ($googleMapsKey !== ''): ?>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= rawurlencode($googleMapsKey) ?>&libraries=places&loading=async&callback=initCustomerMaps"></script>
<?php else: ?>
<script>window.customerMapsConfigurationError = 'GOOGLE_MAPS_API_KEY belum diatur pada .env'; window.initCustomerMaps();</script>
<?php endif; ?>
