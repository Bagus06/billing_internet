(function () {
    const nikInput = document.querySelector('[data-nik-input]');
    const codePreview = document.querySelector('[data-customer-code-preview]');

    function updateCustomerCode() {
        if (!nikInput || !codePreview) {
            return;
        }

        const digits = nikInput.value.replace(/\D/g, '');
        const suffix = digits.padStart(6, '0').slice(-6);
        codePreview.value = 'BTN-' + suffix;
    }

    if (nikInput && codePreview) {
        nikInput.addEventListener('input', updateCustomerCode);
        updateCustomerCode();
    }

    const packageSelect = document.querySelector('[data-package-select]');
    const packagePrice = document.querySelector('[data-package-price]');

    function formatRupiah(value) {
        const number = Number(value || 0);

        return number.toLocaleString('id-ID', {
            maximumFractionDigits: 0
        });
    }

    function updatePackagePrice() {
        if (!packageSelect || !packagePrice) {
            return;
        }

        const option = packageSelect.options[packageSelect.selectedIndex];
        packagePrice.value = option && option.dataset.price ? formatRupiah(option.dataset.price) : '';
    }

    if (packageSelect && packagePrice) {
        packageSelect.addEventListener('change', updatePackagePrice);
        updatePackagePrice();
    }

    const psbDate = document.querySelector('[data-psb-date]');
    const groupPreview = document.querySelector('[data-group-preview]');

    function updateGroupPreview() {
        if (!psbDate || !groupPreview || !psbDate.value) {
            return;
        }

        const day = Number(psbDate.value.split('-')[2]);
        groupPreview.value = day >= 16 ? 'Kelompok 2' : 'Kelompok 1';
    }

    if (psbDate && groupPreview) {
        psbDate.addEventListener('change', updateGroupPreview);
        updateGroupPreview();
    }

    const modal = document.getElementById('ktpModal');
    const modalImage = document.getElementById('ktpModalImage');
    const modalTitle = document.getElementById('ktpModalTitle');

    if (!modal || !modalImage || !modalTitle) {
        return;
    }

    document.querySelectorAll('.customer-ktp-button').forEach(function (button) {
        button.addEventListener('click', function () {
            modalImage.src = button.dataset.ktpSrc || '';
            modalTitle.textContent = 'Foto KTP - ' + (button.dataset.ktpName || 'Pelanggan');
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-ktp-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modalImage.src = '';
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modalImage.src = '';
        }
    });

    const paymentModal = document.getElementById('paymentModal');
    const paymentCustomerId = document.getElementById('paymentCustomerId');
    const paymentCustomerName = document.getElementById('paymentCustomerName');
    const paymentCustomerCode = document.getElementById('paymentCustomerCode');
    const paymentCustomerPrice = document.getElementById('paymentCustomerPrice');

    if (!paymentModal || !paymentCustomerId || !paymentCustomerName || !paymentCustomerCode || !paymentCustomerPrice) {
        return;
    }

    document.querySelectorAll('.customer-pay-button').forEach(function (button) {
        button.addEventListener('click', function () {
            paymentCustomerId.value = button.dataset.customerId || '';
            paymentCustomerName.textContent = button.dataset.customerName || '-';
            paymentCustomerCode.textContent = button.dataset.customerCode || '-';
            paymentCustomerPrice.textContent = button.dataset.customerPrice || '-';
            paymentModal.classList.add('is-open');
            paymentModal.setAttribute('aria-hidden', 'false');
        });
    });

    function closePaymentModal() {
        paymentModal.classList.remove('is-open');
        paymentModal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-payment-close]').forEach(function (button) {
        button.addEventListener('click', closePaymentModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && paymentModal.classList.contains('is-open')) {
            closePaymentModal();
        }
    });
})();
