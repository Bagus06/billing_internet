(function () {
    'use strict';
    const modal = document.querySelector('[data-profit-print-modal]');
    const openButton = document.querySelector('[data-profit-print-open]');
    if (!modal || !openButton) return;
    const yearField = modal.querySelector('[data-profit-print-year]');
    const monthField = modal.querySelector('[data-profit-print-month]');
    let periods = {};
    let monthLabels = {};
    try {
        periods = JSON.parse(modal.dataset.printPeriods || '{}');
        monthLabels = JSON.parse(modal.dataset.monthLabels || '{}');
    } catch (error) {
        periods = {};
        monthLabels = {};
    }

    const updateMonths = function () {
        if (!yearField || !monthField) return;
        const availableMonths = (periods[yearField.value] || []).slice().sort(function (a, b) { return b - a; });
        monthField.innerHTML = '';
        availableMonths.forEach(function (month) {
            const option = document.createElement('option');
            option.value = month;
            option.textContent = monthLabels[month] || month;
            monthField.appendChild(option);
        });
        monthField.disabled = availableMonths.length === 0;
    };

    const closeModal = function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('profit-print-open');
        openButton.focus();
    };
    const openModal = function () {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('profit-print-open');
        const firstField = modal.querySelector('select');
        if (firstField) window.setTimeout(function () { firstField.focus(); }, 60);
    };

    openButton.addEventListener('click', openModal);
    if (yearField) yearField.addEventListener('change', updateMonths);
    modal.querySelectorAll('[data-profit-print-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });
    const form = modal.querySelector('[data-profit-print-form]');
    if (form) form.addEventListener('submit', function () { window.setTimeout(closeModal, 100); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();
