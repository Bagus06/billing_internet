(function () {
    const app = document.getElementById('monitoringApp');
    if (!app) return;
    const appShell = app.closest('.app-shell');

    const sessionsUrl = app.dataset.sessionsUrl;
    const disconnectUrl = app.dataset.disconnectUrl;
    const connectUrl = app.dataset.connectUrl;
    const remoteOntUrl = app.dataset.remoteOntUrl;
    const trafficUrl = app.dataset.trafficUrl;
    const isMobile = window.matchMedia('(max-width: 767.98px), (pointer: coarse)').matches;
    const refreshMilliseconds = Math.max(10, Number(app.dataset.refreshSeconds) || 30) * 1000;
    const trafficMilliseconds = Math.max(isMobile ? 5 : 2, Number(app.dataset.trafficSeconds) || 3) * 1000;
    const activeCount = document.getElementById('activeSessionCount');
    const offlineCount = document.getElementById('offlineSessionCount');
    const totalCount = document.getElementById('totalSecretCount');
    const message = document.getElementById('monitoringMessage');
    const cards = document.getElementById('pppoeSessionCards');
    const searchInput = document.getElementById('pppoeSearch');
    const sortSelect = document.getElementById('pppoeSort');
    const resultCount = document.getElementById('pppoeResultCount');
    let allRows = [];
    let activeModal = null;
    let activeModalSource = null;
    let activeBackdrop = null;
    let activeMapModal = null;
    const trafficSamples = new Map();
    const trafficElements = new Map();
    let sessionsLoading = false;
    let trafficLoading = false;
    let renderPending = false;

    function escapeHtml(value) {
        return String(value === undefined || value === null || value === '' ? '-' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function setMessage(text, danger) {
        message.textContent = text;
        message.classList.toggle('text-danger', !!danger);
        message.classList.toggle('text-muted', !danger);
    }

    function detail(label, value, wide, action) {
        const icons = {
            Nama: 'fa-user', ID: 'fa-hashtag', Kode: 'fa-id-badge', NIK: 'fa-address-card',
            Telepon: 'fa-phone', Alamat: 'fa-location-dot', Paket: 'fa-box-open', Kelompok: 'fa-users',
            Pelanggan: 'fa-user-check', 'ONT OLT': 'fa-network-wired', 'RX Optical': 'fa-wave-square',
            Sinyal: 'fa-signal', Username: 'fa-at', Status: 'fa-circle-check', Uptime: 'fa-clock',
            'Last OFF': 'fa-power-off', 'IP Address': 'fa-globe', 'Local Address': 'fa-house-signal',
            'Caller ID': 'fa-fingerprint', Profile: 'fa-gauge-high', Service: 'fa-gears',
            Komentar: 'fa-note-sticky', Router: 'fa-server'
        };
        const icon = icons[label] || 'fa-circle-info';
        const content = action ? `<div class="pppoe-detail-value-action"><strong>${escapeHtml(value)}</strong>${action}</div>` : `<strong>${escapeHtml(value)}</strong>`;
        return `<div class="pppoe-detail-item${wide ? ' is-wide' : ''}${action ? ' has-action' : ''}"><span><i class="fa-solid ${icon}" aria-hidden="true"></i>${label}</span>${content}</div>`;
    }

    function detailGroup(title, icon, content) {
        return `<section class="pppoe-detail-group"><div class="pppoe-detail-group-title"><i class="fa-solid ${icon}" aria-hidden="true"></i>${title}</div><div class="pppoe-detail-group-body">${content}</div></section>`;
    }

    function mapButton(row) {
        const latitude = Number(row.customer_latitude);
        const longitude = Number(row.customer_longitude);
        const hasCoordinates = row.customer_latitude !== null && row.customer_latitude !== undefined && row.customer_latitude !== '' && row.customer_longitude !== null && row.customer_longitude !== undefined && row.customer_longitude !== '';
        const valid = hasCoordinates && Number.isFinite(latitude) && Number.isFinite(longitude) && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
        if (!valid) return '<button type="button" class="pppoe-map-button is-disabled" disabled title="Koordinat pelanggan belum tersedia"><i class="fa-solid fa-map-location-dot"></i>Lihat Peta</button>';
        return `<button type="button" class="pppoe-map-button btn-view-customer-map" data-latitude="${escapeHtml(latitude)}" data-longitude="${escapeHtml(longitude)}" data-customer-name="${escapeHtml(row.customer_name)}" title="Lihat lokasi pelanggan"><i class="fa-solid fa-map-location-dot"></i>Lihat Peta</button>`;
    }

    function whatsappButton(phone, customerName) {
        let number = String(phone || '').replace(/\D+/g, '');
        if (number.startsWith('0')) number = '62' + number.slice(1);
        else if (number.startsWith('8')) number = '62' + number;
        const valid = /^62\d{8,13}$/.test(number);
        if (!valid) return '<button type="button" class="pppoe-whatsapp-button is-disabled" disabled title="Nomor WhatsApp tidak valid"><i class="fa-brands fa-whatsapp"></i>Chat</button>';
        return `<a class="pppoe-whatsapp-button" href="https://wa.me/${number}" target="_blank" rel="noopener noreferrer" title="Chat WhatsApp ${escapeHtml(customerName)}"><i class="fa-brands fa-whatsapp"></i>Chat</a>`;
    }

    function opticalLabel(row) {
        return row.optical_rx === null || row.optical_rx === undefined ? 'N/A' : `${row.optical_rx} dBm`;
    }

    function formatUptime(value) {
        const uptime = String(value || '');
        if (!uptime || uptime === '-') return '-';
        const weeks = Number((uptime.match(/(\d+)w/) || [0, 0])[1]);
        const days = Number((uptime.match(/(\d+)d/) || [0, 0])[1]);
        let hours = Number((uptime.match(/(\d+)h/) || [0, 0])[1]);
        let minutes = Number((uptime.match(/(\d+)m/) || [0, 0])[1]);
        const clock = uptime.match(/(\d+):(\d+)(?::\d+)?/);
        if (clock) {
            hours = Number(clock[1]);
            minutes = Number(clock[2]);
        }
        return `${(weeks * 7) + days}D ${hours}H ${minutes}M`;
    }

    function formatRate(bytesPerSecond) {
        const bits = Math.max(0, Number(bytesPerSecond) || 0) * 8;
        if (bits >= 1000000) return `${(bits / 1000000).toFixed(1)} Mbps`;
        if (bits >= 1000) return `${(bits / 1000).toFixed(1)} Kbps`;
        return `${Math.round(bits)} bps`;
    }

    function updateTraffic(rows) {
        const now = Date.now();
        rows.forEach(function (row) {
            const key = trafficKey(row.router_id, row.username);
            const previous = trafficSamples.get(key);
            row.download_rate = 0;
            row.upload_rate = 0;
            if (previous && now > previous.time) {
                const seconds = (now - previous.time) / 1000;
                row.download_rate = Math.max(0, (Number(row.bytes_out || 0) - previous.bytesOut) / seconds);
                row.upload_rate = Math.max(0, (Number(row.bytes_in || 0) - previous.bytesIn) / seconds);
            }
            trafficSamples.set(key, { time: now, bytesIn: Number(row.bytes_in || 0), bytesOut: Number(row.bytes_out || 0) });
        });
    }

    function trafficKey(routerId, username) {
        return `${routerId}:${String(username || '').toLowerCase()}`;
    }

    function applyTrafficSamples(samples) {
        const now = Date.now();
        samples.forEach(function (sample) {
            const key = trafficKey(sample.router_id, sample.username);
            const previous = trafficSamples.get(key);
            let downloadRate = 0;
            let uploadRate = 0;
            if (previous && now > previous.time) {
                const seconds = (now - previous.time) / 1000;
                downloadRate = Math.max(0, (Number(sample.bytes_out || 0) - previous.bytesOut) / seconds);
                uploadRate = Math.max(0, (Number(sample.bytes_in || 0) - previous.bytesIn) / seconds);
            }
            trafficSamples.set(key, { time: now, bytesIn: Number(sample.bytes_in || 0), bytesOut: Number(sample.bytes_out || 0) });
            const row = allRows.find(function (item) { return trafficKey(item.router_id, item.username) === key; });
            if (row) {
                row.download_rate = downloadRate;
                row.upload_rate = uploadRate;
            }
            const elements = trafficElements.get(key) || [];
            elements.forEach(function (element) {
                const down = element.querySelector('.traffic-download');
                const up = element.querySelector('.traffic-upload');
                if (down) down.textContent = formatRate(downloadRate);
                if (up) up.textContent = formatRate(uploadRate);
            });
        });
    }

    function renderCards(rows) {
        trafficElements.clear();
        if (!rows.length) {
            cards.innerHTML = '<div class="pppoe-empty text-muted">Tidak ada PPP Secret yang ditemukan.</div>';
            return;
        }

        cards.innerHTML = rows.map(function (row) {
            const online = row.status === 'ON';
            const secretDisabled = row.disabled === true || row.disabled === 1 || row.disabled === 'true';
            const disconnect = !secretDisabled && row.secret_id ? `
                <button type="button" class="pppoe-disconnect btn-disconnect-session"
                    data-router-id="${escapeHtml(row.router_id)}" data-active-id="${escapeHtml(row.active_id)}"
                    data-secret-id="${escapeHtml(row.secret_id)}">
                    <i class="fa-solid fa-power-off"></i> Disconnect
                </button>` : '';
            const cardDisconnect = !secretDisabled && row.secret_id ? `
                <button type="button" class="pppoe-card-disconnect btn-disconnect-session"
                    data-router-id="${escapeHtml(row.router_id)}" data-active-id="${escapeHtml(row.active_id)}"
                    data-secret-id="${escapeHtml(row.secret_id)}"
                    title="Putuskan sesi PPPoE aktif">
                    <i class="fa-solid fa-power-off"></i> Disconnect
                </button>` : '';
            const connect = secretDisabled && row.secret_id ? `
                <button type="button" class="pppoe-card-connect btn-connect-secret"
                    data-router-id="${escapeHtml(row.router_id)}" data-secret-id="${escapeHtml(row.secret_id)}"
                    title="Aktifkan PPP Secret">
                    <i class="fa-solid fa-plug-circle-check"></i> Connect
                </button>` : '';
            const popupConnect = secretDisabled && row.secret_id ? `
                <button type="button" class="pppoe-disconnect is-connect btn-connect-secret"
                    data-router-id="${escapeHtml(row.router_id)}" data-secret-id="${escapeHtml(row.secret_id)}">
                    <i class="fa-solid fa-plug-circle-check"></i> Connect
                </button>` : '';
            const remoteOnt = online ? `
                <button type="button" class="pppoe-remote-ont btn-remote-ont"
                    data-router-id="${escapeHtml(row.router_id)}" data-username="${escapeHtml(row.username)}"
                    title="Arahkan Forward-ONT lalu buka halaman ONT">
                    <i class="fa-solid fa-router"></i> Remote
                </button>` : '';

            return `<article class="pppoe-user-card ${online ? 'is-on' : 'is-off'}" tabindex="0">
                <div class="pppoe-card-head">
                    <span class="pppoe-avatar"><i class="fa-solid fa-user"></i></span>
                    <span class="monitoring-badge ${online ? 'is-online' : 'is-offline'}">${online ? 'ON' : 'OFF'}</span>
                </div>
                <strong class="pppoe-username">${escapeHtml(row.username)}</strong>
                <span class="pppoe-customer-name">${escapeHtml(row.customer_name)}</span>
                <span class="pppoe-router"><i class="fa-solid fa-server"></i> ${escapeHtml(row.router)} &middot; ${escapeHtml(row.profile)}</span>
                <div class="pppoe-uptime">
                    <div class="pppoe-metric"><small>Uptime</small><strong>${escapeHtml(formatUptime(row.uptime))}</strong></div>
                    <span class="optical-signal is-${escapeHtml(row.optical_status)}"><i class="fa-solid fa-wave-square"></i> ${escapeHtml(opticalLabel(row))}</span>
                    <div class="pppoe-traffic" data-traffic-key="${escapeHtml(trafficKey(row.router_id, row.username))}" title="Kecepatan realtime">
                        <span><i class="fa-solid fa-arrow-down"></i><b class="traffic-download">${escapeHtml(formatRate(row.download_rate))}</b></span>
                        <span><i class="fa-solid fa-arrow-up"></i><b class="traffic-upload">${escapeHtml(formatRate(row.upload_rate))}</b></span>
                    </div>
                </div>
                <div class="pppoe-card-actions">
                    ${cardDisconnect}
                    ${connect}
                    ${remoteOnt}
                    <button type="button" class="pppoe-detail-button"><i class="fa-solid fa-circle-info"></i> Detail</button>
                </div>
                <div class="pppoe-hover-detail" role="tooltip">
                    <div class="pppoe-detail-title"><h6><i class="fa-solid fa-address-card" aria-hidden="true"></i> Detail Pelanggan</h6><button type="button" class="pppoe-detail-close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div>
                    ${detailGroup('Data Pelanggan', 'fa-user', `
                        ${detail('Nama', row.customer_name, true)}
                        ${detail('ID', row.customer_id)}
                        ${detail('Kode', row.customer_code)}
                        ${detail('NIK', row.customer_nik)}
                        ${detail('Telepon', row.customer_phone, false, whatsappButton(row.customer_phone, row.customer_name))}
                        ${detail('Alamat', row.customer_address, true)}
                        <div class="pppoe-detail-map-action">${mapButton(row)}</div>
                    `)}
                    ${detailGroup('Layanan', 'fa-box-open', `
                        ${detail('Paket', row.customer_package)}
                        ${detail('Kelompok', row.customer_group)}
                        ${detail('Pelanggan', row.customer_status)}
                        ${detail('Username', row.username, true)}
                        ${detail('Profile', row.profile)}
                        ${detail('Service', row.service)}
                        ${detail('Router', row.router, true)}
                        ${detail('Komentar', row.comment, true)}
                    `)}
                    ${detailGroup('Koneksi & Traffic', 'fa-chart-line', `
                        ${detail('Status', row.status)}
                        ${detail('Uptime', formatUptime(row.uptime))}
                        ${detail('Download', formatRate(row.download_rate))}
                        ${detail('Upload', formatRate(row.upload_rate))}
                        ${detail('Last OFF', row.last_off)}
                        ${detail('IP Address', row.address)}
                        ${detail('Local Address', row.local_address)}
                        ${detail('Caller ID', row.caller_id)}
                    `)}
                    ${detailGroup('Perangkat ONT', 'fa-router', `
                        ${detail('ONT OLT', row.ont_name)}
                        ${detail('RX Optical', opticalLabel(row))}
                        ${detail('Sinyal', row.optical_status)}
                    `)}
                    <div class="pppoe-detail-actions">${disconnect}${popupConnect}${remoteOnt}</div>
                </div>
            </article>`;
        }).join('');
        cards.querySelectorAll('.pppoe-traffic[data-traffic-key]').forEach(function (element) {
            const key = element.dataset.trafficKey;
            if (!trafficElements.has(key)) trafficElements.set(key, []);
            trafficElements.get(key).push(element);
        });
    }

    function compareText(a, b) {
        return String(a || '').localeCompare(String(b || ''), 'id', { sensitivity: 'base' });
    }

    function applyFilters(preserveOpenDetail) {
        const keyword = String(searchInput.value || '').trim().toLowerCase();
        const sort = sortSelect.value;
        const rows = allRows.filter(function (row) {
            if (!keyword) return true;
            return [row.customer_name, row.customer_nik, row.customer_id, row.customer_code].some(function (value) {
                return String(value === null || value === undefined ? '' : value).toLowerCase().includes(keyword);
            });
        });

        rows.sort(function (a, b) {
            if (sort === 'name_asc') return compareText(a.customer_name, b.customer_name);
            if (sort === 'name_desc') return compareText(b.customer_name, a.customer_name);
            if (sort === 'nik_asc') return compareText(a.customer_nik, b.customer_nik);
            if (sort === 'id_asc') return Number(a.customer_id || Infinity) - Number(b.customer_id || Infinity);
            if (sort === 'id_desc') return Number(b.customer_id || -1) - Number(a.customer_id || -1);
            if (sort === 'signal_strong') return Number(b.optical_rx ?? -999) - Number(a.optical_rx ?? -999);
            if (sort === 'signal_weak') return Number(a.optical_rx ?? 999) - Number(b.optical_rx ?? 999);
            if (a.status !== b.status) return a.status === 'ON' ? -1 : 1;
            return compareText(a.customer_name, b.customer_name);
        });

        resultCount.textContent = `${rows.length} dari ${allRows.length} pelanggan`;
        if (preserveOpenDetail && activeModal) {
            renderPending = true;
            return;
        }
        closeDetail();
        renderCards(rows);
        renderPending = false;
    }

    function openDetail(card) {
        closeDetail();
        const modal = card.querySelector('.pppoe-hover-detail');
        if (!modal) return;
        activeModal = modal;
        activeModalSource = card;
        activeBackdrop = document.createElement('div');
        activeBackdrop.className = 'pppoe-modal-backdrop';
        document.body.appendChild(activeBackdrop);
        card.classList.add('is-detail-open');
        document.body.appendChild(modal);
        modal.classList.add('is-open');
        document.body.classList.add('pppoe-modal-open');
        if (appShell) appShell.inert = true;
    }

    function closeDetail() {
        closeMap();
        if (!activeModal) return;
        activeModal.classList.remove('is-open');
        if (activeModalSource && activeModalSource.isConnected) {
            activeModalSource.classList.remove('is-detail-open');
            activeModalSource.appendChild(activeModal);
        } else {
            activeModal.remove();
        }
        activeModal = null;
        activeModalSource = null;
        if (activeBackdrop) activeBackdrop.remove();
        activeBackdrop = null;
        document.body.classList.remove('pppoe-modal-open');
        if (appShell) appShell.inert = false;
        if (renderPending) applyFilters(false);
    }

    function openMap(button) {
        closeMap();
        const latitude = Number(button.dataset.latitude), longitude = Number(button.dataset.longitude);
        if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;
        const query = encodeURIComponent(latitude + ',' + longitude);
        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${query}`;
        activeMapModal = document.createElement('div');
        activeMapModal.className = 'pppoe-map-modal';
        activeMapModal.innerHTML = `<div class="pppoe-map-modal-backdrop" data-close-customer-map></div><section class="pppoe-map-modal-panel" role="dialog" aria-modal="true" aria-label="Lokasi pelanggan"><header><div><small>Lokasi Pelanggan</small><strong>${escapeHtml(button.dataset.customerName)}</strong></div><button type="button" data-close-customer-map aria-label="Tutup peta"><i class="fa-solid fa-xmark"></i></button></header><iframe src="https://maps.google.com/maps?q=${query}&z=18&output=embed" title="Peta lokasi ${escapeHtml(button.dataset.customerName)}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe><footer><span><i class="fa-solid fa-location-crosshairs"></i>${escapeHtml(latitude)}, ${escapeHtml(longitude)}</span><a href="${mapsUrl}" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-diamond-turn-right"></i>Buka di Maps</a></footer></section>`;
        document.body.appendChild(activeMapModal);
        requestAnimationFrame(function () { if (activeMapModal) activeMapModal.classList.add('is-open'); });
    }

    function closeMap() {
        if (!activeMapModal) return;
        activeMapModal.remove();
        activeMapModal = null;
    }

    async function loadSessions() {
        if (sessionsLoading || document.hidden) return;
        sessionsLoading = true;
        try {
            const response = await fetch(sessionsUrl, { loader: false, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            activeCount.textContent = data.active_sessions || 0;
            offlineCount.textContent = data.offline_sessions || 0;
            totalCount.textContent = data.total_secrets || 0;
            allRows = data.rows || [];
            updateTraffic(allRows);
            applyFilters(true);
            setMessage(data.errors && data.errors.length ? data.errors.join(' | ') : 'Data berhasil diperbarui.', data.errors && data.errors.length);
        } catch (error) {
            setMessage('Gagal mengambil data monitoring.', true);
        } finally {
            sessionsLoading = false;
        }
    }

    async function disconnectSession(button) {
        const formData = new FormData();
        formData.append('router_id', button.dataset.routerId);
        formData.append('active_id', button.dataset.activeId);
        formData.append('secret_id', button.dataset.secretId);
        button.disabled = true;
        try {
            const response = await fetch(disconnectUrl, { method: 'POST', body: formData, headers: { Accept: 'application/json' } });
            const data = await response.json();
            setMessage(data.message || 'Perintah disconnect selesai.', !data.success);
            await loadSessions();
        } catch (error) {
            setMessage('Gagal mengirim perintah disconnect.', true);
        } finally {
            button.disabled = false;
        }
    }

    async function loadTraffic() {
        if (trafficLoading || document.hidden) return;
        trafficLoading = true;
        try {
            const response = await fetch(trafficUrl, { loader: false, headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            applyTrafficSamples(data.rows || []);
        } catch (error) {
            // Polling utama tetap berjalan jika endpoint traffic sesaat gagal.
        } finally {
            trafficLoading = false;
        }
    }

    async function connectSecret(button) {
        const formData = new FormData();
        formData.append('router_id', button.dataset.routerId);
        formData.append('secret_id', button.dataset.secretId);
        button.disabled = true;
        try {
            const response = await fetch(connectUrl, { method: 'POST', body: formData, headers: { Accept: 'application/json' } });
            const data = await response.json();
            setMessage(data.message || 'Perintah connect selesai.', !data.success);
            await loadSessions();
        } catch (error) {
            setMessage('Gagal mengaktifkan PPP Secret.', true);
        } finally {
            button.disabled = false;
        }
    }

    async function remoteOnt(button) {
        const remoteWindow = window.open('about:blank', '_blank');
        if (remoteWindow) {
            remoteWindow.opener = null;
            remoteWindow.document.write('<title>Menyiapkan Remote ONT</title><p style="font-family:Arial;padding:24px">Menyiapkan NAT Forward-ONT...</p>');
        }
        const formData = new FormData();
        formData.append('router_id', button.dataset.routerId);
        formData.append('username', button.dataset.username);
        button.disabled = true;
        try {
            const response = await fetch(remoteOntUrl, { method: 'POST', body: formData, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!data.success || !data.remote || !data.remote.url) throw new Error(data.message || 'Remote ONT gagal disiapkan.');
            setMessage(data.message || 'NAT Forward-ONT berhasil diperbarui.', false);
            if (remoteWindow) remoteWindow.location.replace(data.remote.url);
            else window.open(data.remote.url, '_blank', 'noopener');
        } catch (error) {
            if (remoteWindow) remoteWindow.close();
            setMessage(error.message || 'Gagal menyiapkan remote ONT.', true);
            AppAlert.notify(error.message || 'Gagal menyiapkan remote ONT.', 'error');
        } finally {
            button.disabled = false;
        }
    }

    cards.addEventListener('click', function (event) {
        const detailButton = event.target.closest('.pppoe-detail-button');

        if (detailButton) {
            openDetail(detailButton.closest('.pppoe-user-card'));
            return;
        }
    });

    document.addEventListener('click', function (event) {
        const closeButton = event.target.closest('.pppoe-detail-close');
        const disconnectButton = event.target.closest('.btn-disconnect-session');
        const connectButton = event.target.closest('.btn-connect-secret');
        const remoteOntButton = event.target.closest('.btn-remote-ont');
        const mapButton = event.target.closest('.btn-view-customer-map');
        const mapClose = event.target.closest('[data-close-customer-map]');
        if (mapClose) { closeMap(); return; }
        if (mapButton) { openMap(mapButton); return; }
        if (activeMapModal && event.target.closest('.pppoe-map-modal-panel')) return;
        if (closeButton) { closeDetail(); return; }
        if (disconnectButton) {
            AppAlert.confirm('Disconnect user dan nonaktifkan PPP Secret ini?', { icon: 'warning', confirmButtonText: 'Ya, disconnect' }).then(function (result) {
                if (result.isConfirmed) disconnectSession(disconnectButton);
            });
            return;
        }
        if (connectButton) {
            AppAlert.confirm('Aktifkan kembali PPP Secret user ini?', { confirmButtonText: 'Ya, aktifkan' }).then(function (result) {
                if (result.isConfirmed) connectSecret(connectButton);
            });
            return;
        }
        if (remoteOntButton) { remoteOnt(remoteOntButton); return; }
        if (activeModal && !event.target.closest('.pppoe-hover-detail') && !event.target.closest('.pppoe-detail-button')) closeDetail();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (activeMapModal) { closeMap(); return; }
            closeDetail();
        }
    });

    searchInput.addEventListener('input', function () { applyFilters(false); });
    sortSelect.addEventListener('change', function () { applyFilters(false); });

    loadSessions().then(function () {
        setTimeout(loadTraffic, 500);
        setInterval(loadTraffic, trafficMilliseconds);
    });
    setInterval(loadSessions, refreshMilliseconds);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) { loadSessions(); loadTraffic(); }
    });
})();
