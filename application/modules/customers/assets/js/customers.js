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
        nikInput.addEventListener('input', function () { nikInput.value = nikInput.value.replace(/\D/g, '').slice(0, 16); updateCustomerCode(); });
        updateCustomerCode();
    }

    document.querySelectorAll('[data-uppercase-input]').forEach(function (input) {
        input.addEventListener('input', function () { input.value = input.value.toLocaleUpperCase('id-ID'); });
    });

    const customerSearchModal = document.querySelector('[data-customer-search-modal]');
    const customerSearchOpen = document.querySelector('[data-customer-search-open]');
    if (customerSearchModal && customerSearchOpen) {
        // Keep the fixed overlay relative to the viewport, not to an animated page container.
        document.body.appendChild(customerSearchModal);
        const closeCustomerSearch = function () {
            customerSearchModal.classList.remove('is-open');
            customerSearchModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('customer-search-open');
            customerSearchOpen.focus();
        };
        customerSearchOpen.addEventListener('click', function () {
            customerSearchModal.classList.add('is-open');
            customerSearchModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('customer-search-open');
            const firstInput = customerSearchModal.querySelector('input:not([type="hidden"]),select');
            if (firstInput) window.setTimeout(function () { firstInput.focus(); }, 80);
        });
        customerSearchModal.querySelectorAll('[data-customer-search-close]').forEach(function (button) {
            button.addEventListener('click', closeCustomerSearch);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && customerSearchModal.classList.contains('is-open')) closeCustomerSearch();
        });
    }

    document.querySelectorAll('[data-phone-input]').forEach(function (input) {
        input.addEventListener('blur', function () {
            let digits = input.value.replace(/\D/g, '');
            if (digits.indexOf('620') === 0) digits = '62' + digits.slice(3);
            else if (digits.indexOf('0') === 0) digits = '62' + digits.slice(1);
            else if (digits.indexOf('8') === 0) digits = '62' + digits;
            input.value = digits;
        });
    });

    const ktpFile = document.querySelector('[data-ktp-file]');
    const ktpPreview = document.querySelector('[data-ktp-preview]');
    const ktpOcrState = document.querySelector('[data-ktp-ocr-state]');
    const customerNameInput = document.querySelector('[data-customer-name-input]');
    const ktpViewButton = document.querySelector('[data-ktp-form-view]');
    const ktpFormModal = document.querySelector('[data-ktp-form-modal]');
    const ktpFormModalImage = document.querySelector('[data-ktp-form-modal-image]');
    const customerForm = document.querySelector('[data-customer-form]');
    const ocrBlocker = document.querySelector('[data-ktp-ocr-blocker]');
    const ocrBlockerStatus = document.querySelector('[data-ktp-blocker-status]');
    const afterKtpControls = customerForm ? Array.from(customerForm.querySelectorAll('input, select, textarea, button')).filter(function(control){return control!==ktpFile&&control!==ktpViewButton&&control.type!=='hidden'}) : [];
    function lockCustomerForm(locked){if(!customerForm||customerForm.dataset.mode!=='create')return;afterKtpControls.forEach(function(control){control.disabled=locked});customerForm.classList.toggle('is-waiting-ktp',locked)}
    function showOcrBlocker(){if(customerForm)customerForm.inert=true;if(ocrBlocker){ocrBlocker.hidden=false;document.body.style.overflow='hidden'}if(ocrBlockerStatus)ocrBlockerStatus.textContent='Mohon tunggu dan jangan menutup halaman.'}
    function hideOcrBlocker(){if(customerForm)customerForm.inert=false;if(ocrBlocker)ocrBlocker.hidden=true;document.body.style.overflow=''}
    if(customerForm&&customerForm.dataset.mode==='create'&&(!ktpFile.files||!ktpFile.files.length))lockCustomerForm(true);
    function loadTesseract(){if(window.Tesseract)return Promise.resolve(window.Tesseract);return new Promise(function(resolve,reject){const script=document.createElement('script');script.src='https://cdn.jsdelivr.net/npm/tesseract.js@7/dist/tesseract.min.js';script.onload=function(){resolve(window.Tesseract)};script.onerror=function(){reject(new Error('OCR gagal dimuat'))};document.head.appendChild(script)})}
    async function prepareKtpImage(file){const image=await createImageBitmap(file);const scale=Math.max(1,Math.min(3,1800/image.width));const canvas=document.createElement('canvas');canvas.width=Math.round(image.width*scale);canvas.height=Math.round(image.height*scale);const context=canvas.getContext('2d',{willReadFrequently:true});context.drawImage(image,0,0,canvas.width,canvas.height);const pixels=context.getImageData(0,0,canvas.width,canvas.height);for(let i=0;i<pixels.data.length;i+=4){const gray=.299*pixels.data[i]+.587*pixels.data[i+1]+.114*pixels.data[i+2];const enhanced=Math.max(0,Math.min(255,(gray-128)*1.65+128));pixels.data[i]=pixels.data[i+1]=pixels.data[i+2]=enhanced}context.putImageData(pixels,0,0);const top=document.createElement('canvas');top.width=canvas.width;top.height=Math.round(canvas.height*.72);top.getContext('2d').drawImage(canvas,0,0,canvas.width,top.height,0,0,top.width,top.height);image.close();return{full:canvas,top:top}}
    function nikScore(nik){if(!/^\d{16}$/.test(nik))return-1;let score=0;const region=nik.slice(0,6);if(!/^0+$/.test(region))score+=2;let day=Number(nik.slice(6,8));const month=Number(nik.slice(8,10));if(day>40)day-=40;if(day>=1&&day<=31)score+=4;if(month>=1&&month<=12)score+=4;return score}
    function extractNik(text){const substitutions={O:'0',Q:'0',D:'0',I:'1',L:'1','|':'1',Z:'2',S:'5',G:'6',B:'8'};const lines=(text||'').toUpperCase().split(/\n+/);const sources=lines.filter(function(line){return /N[IL1]K/.test(line)}).concat(lines);let best='',bestScore=-1;sources.forEach(function(line,index){const clean=line.replace(/[OQDIL|ZSGB]/g,function(char){return substitutions[char]||char});const groups=clean.match(/(?:\d[\s.:\-]*){16}/g)||[];groups.forEach(function(group){const candidate=group.replace(/\D/g,'').slice(0,16);const score=nikScore(candidate)+(/N[IL1]K/.test(line)?10:0)-index*.001;if(score>bestScore){best=candidate;bestScore=score}})});return bestScore>=8?best:''}
    function extractName(text){const labels=/TEMPAT|TGL|LAHIR|JENIS|KELAMIN|ALAMAT|AGAMA|STATUS|PEKERJAAN|KEWARGANEGARAAN|BERLAKU/i;const lines=(text||'').replace(/\r/g,'').split('\n').map(function(line){return line.trim()}).filter(Boolean);const clean=function(value){return(value||'').split(labels)[0].replace(/[^A-Za-zÀ-ÿ.'\- ]/g,' ').replace(/\s+/g,' ').trim().toLocaleUpperCase('id-ID')};for(let i=0;i<lines.length;i++){if(!/N\s*[A4]\s*M\s*[A4]|N[4V]?MA/i.test(lines[i]))continue;let value=lines[i].replace(/^.*?(?:N\s*[A4]\s*M\s*[A4]|N[4V]?MA)\s*[:;=\-]?\s*/i,'');if(clean(value).length<3&&lines[i+1]&&!labels.test(lines[i+1]))value=lines[i+1];value=clean(value);if(value.length>=3&&!/PROVINSI|KABUPATEN|KOTA|NIK/.test(value))return value}const nikIndex=lines.findIndex(function(line){return /N[IL1]K/i.test(line)||extractNik(line)});if(nikIndex>=0){for(let j=nikIndex+1;j<Math.min(lines.length,nikIndex+4);j++){if(labels.test(lines[j]))break;const fallback=clean(lines[j].replace(/^[^:;=]*[:;=]\s*/,''));if(fallback.length>=3&&/[A-Z]{3}/.test(fallback)&&!/NIK|PROVINSI|KABUPATEN|KOTA/.test(fallback))return fallback}}return''}
    function extractKtpData(text){return{nik:extractNik(text),name:extractName(text)}}
    if(ktpFile&&ktpPreview){ktpFile.addEventListener('change',async function(){const file=ktpFile.files&&ktpFile.files[0];if(!file)return;showOcrBlocker();ktpPreview.src=URL.createObjectURL(file);if(ktpViewButton)ktpViewButton.disabled=false;if(ktpOcrState)ktpOcrState.textContent='Memperjelas gambar KTP...';try{const images=await prepareKtpImage(file);const Tesseract=await loadTesseract();const worker=await Tesseract.createWorker('ind+eng',1,{logger:function(message){if(message.status==='recognizing text'){const progress='Membaca KTP '+Math.round((message.progress||0)*100)+'%';if(ktpOcrState)ktpOcrState.textContent=progress;if(ocrBlockerStatus)ocrBlockerStatus.textContent=progress}}});await worker.setParameters({tessedit_pageseg_mode:'6',preserve_interword_spaces:'1'});const topResult=await worker.recognize(images.top);let data=extractKtpData(topResult.data.text);if(!data.nik||!data.name){if(ocrBlockerStatus)ocrBlockerStatus.textContent='Memeriksa ulang seluruh bagian KTP...';const fullResult=await worker.recognize(images.full);const fallback=extractKtpData(fullResult.data.text);data={nik:data.nik||fallback.nik,name:data.name||fallback.name}}await worker.terminate();if(data.nik&&nikInput){nikInput.value=data.nik;updateCustomerCode()}if(data.name&&customerNameInput)customerNameInput.value=data.name;if(ktpOcrState)ktpOcrState.textContent=data.nik&&data.name?'NIK dan nama berhasil dibaca. Mohon periksa kembali.':data.nik||data.name?'Sebagian data terbaca. Lengkapi dan periksa kembali.':'NIK dan nama belum terbaca. Silakan isi manual.';lockCustomerForm(false);hideOcrBlocker()}catch(error){if(ktpOcrState)ktpOcrState.textContent='OCR gagal. Silakan isi NIK dan nama secara manual.';lockCustomerForm(false);hideOcrBlocker()}})}
    if(ktpViewButton&&ktpFormModal&&ktpFormModalImage){ktpViewButton.addEventListener('click',function(){if(!ktpPreview.src)return;ktpFormModalImage.src=ktpPreview.src;ktpFormModal.classList.add('is-open');ktpFormModal.setAttribute('aria-hidden','false');document.body.classList.add('modal-open')});document.querySelectorAll('[data-ktp-form-close]').forEach(function(button){button.addEventListener('click',function(){ktpFormModal.classList.remove('is-open');ktpFormModal.setAttribute('aria-hidden','true');document.body.classList.remove('modal-open')})})}

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

    document.querySelectorAll('.customer-status-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const willActivate = button.dataset.active !== '1';
            const customerName = button.dataset.customerName || 'pelanggan';
            const question = willActivate
                ? 'Aktifkan pelanggan ' + customerName + ' dan enable PPP Secret?'
                : 'Nonaktifkan pelanggan ' + customerName + ', disable PPP Secret, dan putus sesi aktif?';

            AppAlert.confirm(question, {
                icon: willActivate ? 'question' : 'warning',
                confirmButtonText: willActivate ? 'Ya, aktifkan' : 'Ya, nonaktifkan'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                const formData = new FormData(); formData.append('customer_id', button.dataset.customerId);
                button.disabled = true;
                fetch(button.dataset.toggleUrl, { method: 'POST', body: formData, headers: { Accept: 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        return AppAlert.notify(data.message || 'Proses perubahan status selesai.', data.success ? 'success' : 'error').then(function () {
                            if (data.success) window.location.reload();
                        });
                    })
                    .catch(function () { AppAlert.notify('Gagal mengubah status pelanggan.', 'error'); })
                    .finally(function () { button.disabled = false; });
            });
        });
    });

    const bulkIsolationButton = document.querySelector('[data-customer-bulk-isolation]');
    if (bulkIsolationButton) {
        bulkIsolationButton.addEventListener('click', function () {
            const confirmationMessage = 'Isolir seluruh pelanggan yang sudah melewati jatuh tempo dan belum membayar? Profile PPP Secret pelanggan yang memenuhi syarat akan diubah ke ISOLIR dan sesi aktifnya diputus.';
            let confirmationCountdown = null;
            const confirmation = typeof Swal === 'undefined'
                ? Promise.resolve({ isConfirmed: window.confirm(confirmationMessage) })
                : AppAlert.confirm(confirmationMessage, {
                title: 'Konfirmasi Isolir Massal',
                icon: 'warning',
                confirmButtonText: 'Tunggu 3 detik...',
                cancelButtonText: 'Batal',
                didOpen: function () {
                    const confirmButton = Swal.getConfirmButton();
                    let seconds = 3;
                    confirmButton.disabled = true;
                    confirmationCountdown = window.setInterval(function () {
                        seconds -= 1;
                        if (seconds > 0) {
                            confirmButton.textContent = 'Tunggu ' + seconds + ' detik...';
                            return;
                        }
                        window.clearInterval(confirmationCountdown);
                        confirmationCountdown = null;
                        confirmButton.disabled = false;
                        confirmButton.textContent = 'Ya, proses isolir';
                    }, 1000);
                },
                willClose: function () {
                    if (confirmationCountdown) window.clearInterval(confirmationCountdown);
                }
            });
            confirmation.then(function (result) {
                if (!result.isConfirmed) return;
                bulkIsolationButton.disabled = true;
                if (window.AppLoader) window.AppLoader.show();
                fetch(bulkIsolationButton.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        return AppAlert.notify(data.message || 'Proses isolir selesai.', data.success ? 'success' : 'error').then(function () {
                            window.location.reload();
                        });
                    })
                    .catch(function () { AppAlert.notify('Tidak dapat memproses isolir pelanggan jatuh tempo.', 'error'); })
                    .finally(function () {
                        bulkIsolationButton.disabled = false;
                        if (window.AppLoader) window.AppLoader.hide(0);
                    });
            });
        });
    }

    const bulkRestoreButton = document.querySelector('[data-customer-bulk-restore]');
    if (bulkRestoreButton) {
        bulkRestoreButton.addEventListener('click', function () {
            const confirmationMessage = 'Pulihkan seluruh pelanggan yang sedang diisolir? PPP Secret akan dikembalikan ke profile paket masing-masing dan sesi isolir akan diputus.';
            let confirmationCountdown = null;
            const confirmation = typeof Swal === 'undefined'
                ? Promise.resolve({ isConfirmed: window.confirm(confirmationMessage) })
                : AppAlert.confirm(confirmationMessage, {
                title: 'Konfirmasi Pemulihan Massal',
                icon: 'warning',
                confirmButtonText: 'Tunggu 3 detik...',
                cancelButtonText: 'Batal',
                didOpen: function () {
                    const confirmButton = Swal.getConfirmButton();
                    let seconds = 3;
                    confirmButton.disabled = true;
                    confirmationCountdown = window.setInterval(function () {
                        seconds -= 1;
                        if (seconds > 0) {
                            confirmButton.textContent = 'Tunggu ' + seconds + ' detik...';
                            return;
                        }
                        window.clearInterval(confirmationCountdown);
                        confirmationCountdown = null;
                        confirmButton.disabled = false;
                        confirmButton.textContent = 'Ya, pulihkan semua';
                    }, 1000);
                },
                willClose: function () {
                    if (confirmationCountdown) window.clearInterval(confirmationCountdown);
                }
            });

            confirmation.then(function (result) {
                if (!result.isConfirmed) return;
                bulkRestoreButton.disabled = true;
                if (window.AppLoader) window.AppLoader.show();
                fetch(bulkRestoreButton.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        return AppAlert.notify(data.message || 'Pemulihan isolir selesai.', data.success ? 'success' : 'error').then(function () {
                            window.location.reload();
                        });
                    })
                    .catch(function () { AppAlert.notify('Tidak dapat memulihkan pelanggan yang diisolir.', 'error'); })
                    .finally(function () {
                        bulkRestoreButton.disabled = false;
                        if (window.AppLoader) window.AppLoader.hide(0);
                    });
            });
        });
    }

    document.querySelectorAll('.customer-isolation-action').forEach(function (button) {
        button.addEventListener('click', function () {
            const restoring = button.dataset.mode === 'restore';
            const customerName = button.dataset.customerName || 'pelanggan';
            const question = restoring
                ? 'Pulihkan ' + customerName + ' ke profile paket dan putus sesi isolir?'
                : 'Isolir pelanggan ' + customerName + ' sekarang? PPP Secret akan dipindahkan ke profile ISOLIR dan sesi aktif akan diputus.';
            AppAlert.confirm(question, {
                icon: restoring ? 'question' : 'warning',
                confirmButtonText: restoring ? 'Ya, pulihkan' : 'Ya, isolir sekarang',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                const formData = new FormData(); formData.append('customer_id', button.dataset.customerId);
                button.disabled = true;
                if (window.AppLoader) window.AppLoader.show();
                fetch(button.dataset.url, { method: 'POST', body: formData, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        return AppAlert.notify(data.message || 'Proses isolir selesai.', data.success ? 'success' : 'error').then(function () { if (data.success) window.location.reload(); });
                    })
                    .catch(function () { AppAlert.notify('Tidak dapat memproses action isolir.', 'error'); })
                    .finally(function () { button.disabled = false; if (window.AppLoader) window.AppLoader.hide(0); });
            });
        });
    });

    document.querySelectorAll('.customer-remote-ont').forEach(function (button) {
        button.addEventListener('click', function () {
            const remoteWindow = window.open('about:blank', '_blank');
            if (remoteWindow) {
                remoteWindow.opener = null;
                remoteWindow.document.write('<title>Menyiapkan Remote ONT</title><p style="font-family:Arial;padding:24px">Menyiapkan NAT Forward-ONT...</p>');
            }
            const formData = new FormData();
            formData.append('customer_id', button.dataset.customerId);
            button.disabled = true;
            fetch(button.dataset.url, { method: 'POST', body: formData, credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.success || !data.remote || !data.remote.url) throw new Error(data.message || 'Remote ONT gagal disiapkan.');
                    if (remoteWindow) remoteWindow.location.replace(data.remote.url);
                    else window.open(data.remote.url, '_blank', 'noopener');
                    return AppAlert.notify(data.message || 'NAT Forward-ONT berhasil diperbarui.', 'success');
                })
                .catch(function (error) {
                    if (remoteWindow) remoteWindow.close();
                    AppAlert.notify(error.message || 'Gagal menyiapkan remote ONT.', 'error');
                })
                .finally(function () { button.disabled = false; });
        });
    });

    document.querySelectorAll('.customer-copy-secret').forEach(function (button) {
        button.addEventListener('click', function () {
            const value = button.dataset.copy || '';
            navigator.clipboard.writeText(value).then(function () { AppAlert.notify(value + ' berhasil disalin.', 'success'); })
                .catch(function () { AppAlert.notify('Gagal menyalin data.', 'error'); });
        });
    });

    const detailModal = document.getElementById('customerDetailModal');
    const detailKtpButton = document.querySelector('[data-detail-ktp]');
    const detailWhatsapp = document.querySelector('[data-detail-whatsapp]');
    const detailMapButton = document.querySelector('[data-customer-map-popup]');
    const customerAppShell = detailModal ? detailModal.closest('.app-shell') : null;
    let customerDetailBackdrop = null;
    let customerMapModal = null;
    let detailMapData = null;

    function whatsappNumber(value) {
        let number = String(value || '').replace(/\D/g, '');
        if (number.indexOf('0') === 0) number = '62' + number.substring(1);
        else if (number.indexOf('8') === 0) number = '62' + number;
        return number;
    }

    function closeCustomerMap() {
        if (!customerMapModal) return;
        customerMapModal.remove();
        customerMapModal = null;
    }

    function closeCustomerDetail() {
        if (!detailModal) return;
        closeCustomerMap();
        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        if (customerDetailBackdrop) customerDetailBackdrop.remove();
        customerDetailBackdrop = null;
        document.body.classList.remove('pppoe-modal-open');
        if (customerAppShell) customerAppShell.inert = false;
    }

    function openCustomerDetail(card) {
        if (!detailModal || !card) return;
        if (detailModal.classList.contains('is-open')) closeCustomerDetail();
        detailModal.querySelectorAll('[data-detail]').forEach(function (field) {
            field.textContent = card.dataset[field.dataset.detail] || '-';
        });
        detailModal.querySelectorAll('[data-detail-copy]').forEach(function (button) {
            button.dataset.copy = card.dataset[button.dataset.detailCopy] || '';
        });
        if (detailKtpButton) {
            detailKtpButton.dataset.ktpSrc = card.dataset.ktpSrc || '';
            detailKtpButton.dataset.ktpName = card.dataset.name || 'Pelanggan';
            detailKtpButton.disabled = !card.dataset.ktpSrc;
        }
        if (detailWhatsapp) {
            const phone = whatsappNumber(card.dataset.phone);
            const validPhone = /^62\d{8,13}$/.test(phone);
            detailWhatsapp.href = validPhone ? 'https://wa.me/' + phone : '#';
            detailWhatsapp.classList.toggle('is-disabled', !validPhone);
            detailWhatsapp.setAttribute('aria-disabled', validPhone ? 'false' : 'true');
        }
        const latitude = Number(card.dataset.latitude);
        const longitude = Number(card.dataset.longitude);
        detailMapData = Number.isFinite(latitude) && Number.isFinite(longitude) && (latitude !== 0 || longitude !== 0)
            ? { latitude: latitude, longitude: longitude, name: card.dataset.name || 'Pelanggan' }
            : null;
        if (detailMapButton) {
            detailMapButton.disabled = !detailMapData;
            detailMapButton.classList.toggle('is-disabled', !detailMapData);
        }
        customerDetailBackdrop = document.createElement('div');
        customerDetailBackdrop.className = 'pppoe-modal-backdrop';
        customerDetailBackdrop.addEventListener('click', closeCustomerDetail);
        document.body.appendChild(customerDetailBackdrop);
        document.body.appendChild(detailModal);
        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('pppoe-modal-open');
        if (customerAppShell) customerAppShell.inert = true;
    }

    document.querySelectorAll('[data-customer-detail]').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.target.closest('a, button, summary, details')) return;
            openCustomerDetail(card);
        });
        card.addEventListener('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a, button, summary, details')) {
                event.preventDefault(); openCustomerDetail(card);
            }
        });
    });
    document.querySelectorAll('[data-customer-detail-close]').forEach(function (button) { button.addEventListener('click', closeCustomerDetail); });
    if (detailWhatsapp) detailWhatsapp.addEventListener('click', function (event) { if (detailWhatsapp.classList.contains('is-disabled')) event.preventDefault(); });
    if (detailMapButton) detailMapButton.addEventListener('click', function () {
        if (!detailMapData) return;
        closeCustomerMap();
        const coordinates = detailMapData.latitude + ',' + detailMapData.longitude;
        const query = encodeURIComponent(coordinates);
        const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + query;
        customerMapModal = document.createElement('div');
        customerMapModal.className = 'pppoe-map-modal';
        customerMapModal.innerHTML = '<div class="pppoe-map-modal-backdrop" data-customer-map-close></div><section class="pppoe-map-modal-panel" role="dialog" aria-modal="true" aria-label="Lokasi pelanggan"><header><div><small>Lokasi Pelanggan</small><strong></strong></div><button type="button" data-customer-map-close aria-label="Tutup peta"><i class="fa-solid fa-xmark"></i></button></header><iframe title="Peta lokasi pelanggan" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><footer><span><i class="fa-solid fa-location-crosshairs"></i></span><a target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-diamond-turn-right"></i>Buka di Maps</a></footer></section>';
        customerMapModal.querySelector('header strong').textContent = detailMapData.name;
        customerMapModal.querySelector('iframe').src = 'https://maps.google.com/maps?q=' + query + '&z=18&output=embed';
        customerMapModal.querySelector('footer span').appendChild(document.createTextNode(coordinates));
        customerMapModal.querySelector('footer a').href = mapsUrl;
        customerMapModal.querySelectorAll('[data-customer-map-close]').forEach(function (button) { button.addEventListener('click', closeCustomerMap); });
        document.body.appendChild(customerMapModal);
        requestAnimationFrame(function () { if (customerMapModal) customerMapModal.classList.add('is-open'); });
    });
    const customerActionMenus = Array.from(document.querySelectorAll('[data-customer-action-menu]'));
    function closeCustomerActionMenu(menu) {
        if (!menu) return;
        menu.removeAttribute('open');
        const card = menu.closest('.customer-profile-card');
        if (card) card.classList.remove('has-open-action');
    }
    function closeCustomerActionMenus(except) {
        customerActionMenus.forEach(function (menu) { if (menu !== except) closeCustomerActionMenu(menu); });
    }
    customerActionMenus.forEach(function (menu) {
        menu.addEventListener('toggle', function () {
            const card = menu.closest('.customer-profile-card');
            if (menu.open) {
                closeCustomerActionMenus(menu);
                if (card) card.classList.add('has-open-action');
            } else if (card) card.classList.remove('has-open-action');
        });
        menu.addEventListener('click', function (event) {
            event.stopPropagation();
            if (event.target.closest('.customer-action-item')) setTimeout(function () { closeCustomerActionMenu(menu); }, 0);
        });
    });
    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-customer-action-menu]')) closeCustomerActionMenus();
    });

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

    if (detailKtpButton) detailKtpButton.addEventListener('click', function () {
        if (!detailKtpButton.dataset.ktpSrc) return;
        modalImage.src = detailKtpButton.dataset.ktpSrc;
        modalTitle.textContent = 'Foto KTP - ' + (detailKtpButton.dataset.ktpName || 'Pelanggan');
        document.body.appendChild(modal);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    });

    document.querySelectorAll('[data-ktp-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modalImage.src = '';
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeCustomerActionMenus();
        if (modal.classList.contains('is-open')) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            modalImage.src = '';
        } else if (customerMapModal && customerMapModal.classList.contains('is-open')) closeCustomerMap();
        else if (detailModal && detailModal.classList.contains('is-open')) closeCustomerDetail();
    });

    const paymentModal = document.getElementById('paymentModal');
    const paymentCustomerId = document.getElementById('paymentCustomerId');
    const paymentCustomerName = document.getElementById('paymentCustomerName');
    const paymentCustomerCode = document.getElementById('paymentCustomerCode');
    const paymentCustomerPrice = document.getElementById('paymentCustomerPrice');
    const paymentForm = document.querySelector('[data-customer-payment-form]');
    const paymentSubmit = document.querySelector('[data-payment-submit]');

    if (!paymentModal || !paymentCustomerId || !paymentCustomerName || !paymentCustomerCode || !paymentCustomerPrice) {
        return;
    }

    document.querySelectorAll('.customer-pay-button').forEach(function (button) {
        button.addEventListener('click', function () {
            if (window.AppLoader) window.AppLoader.hide(0);
            paymentCustomerId.value = button.dataset.customerId || '';
            paymentCustomerName.textContent = button.dataset.customerName || '-';
            paymentCustomerCode.textContent = button.dataset.customerCode || '-';
            paymentCustomerPrice.textContent = button.dataset.customerPrice || '-';
            paymentModal.classList.add('is-open');
            paymentModal.setAttribute('aria-hidden', 'false');
            if (paymentSubmit) { paymentSubmit.disabled = false; paymentSubmit.style.pointerEvents = 'auto'; }
        });
    });

    if (paymentForm) paymentForm.addEventListener('invalid', function () {
        if (window.AppLoader) window.AppLoader.hide(0);
    }, true);

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
