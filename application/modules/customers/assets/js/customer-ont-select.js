(function ($) {
    'use strict';
    function initialize() {
        if (!$ || !$.fn || !$.fn.select2) return;
        $('[data-ont-select]').each(function () {
            const select = $(this);
            if (select.hasClass('select2-hidden-accessible')) return;
            select.select2({ width: '100%', placeholder: select.data('placeholder') || 'Cari perangkat ONT...', allowClear: true });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})(window.jQuery);
