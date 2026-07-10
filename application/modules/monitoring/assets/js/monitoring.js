(function () {
    const app = document.getElementById('monitoringApp');

    if (!app) {
        return;
    }

    const summaryUrl = app.dataset.summaryUrl;
    const sessionsUrl = app.dataset.sessionsUrl;
    const disconnectUrl = app.dataset.disconnectUrl;
    const routerOnlineCount = document.getElementById('routerOnlineCount');
    const activeSessionCount = document.getElementById('activeSessionCount');
    const pollingStatus = document.getElementById('pollingStatus');
    const monitoringMessage = document.getElementById('monitoringMessage');
    const resourceRowsBody = document.getElementById('routerResourceRows');
    const rowsBody = document.getElementById('pppoeSessionRows');

    function escapeHtml(value) {
        return String(value || '-')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setMessage(message, danger = false) {
        monitoringMessage.textContent = message;
        monitoringMessage.classList.toggle('text-danger', danger);
        monitoringMessage.classList.toggle('text-muted', !danger);
    }

    function formatMemory(value) {
        const bytes = Number(value);

        if (!Number.isFinite(bytes)) {
            return escapeHtml(value);
        }

        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(unitIndex === 0 ? 0 : 2)} ${units[unitIndex]}`;
    }

    function renderResources(routers) {
        if (!routers.length) {
            resourceRowsBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Tidak ada resource router yang bisa ditampilkan.
                    </td>
                </tr>
            `;
            return;
        }

        resourceRowsBody.innerHTML = routers.map(function (router) {
            const isOnline = router.status === 'online';

            return `
                <tr>
                    <td>${escapeHtml(router.name)}</td>
                    <td>${escapeHtml(router.host)}</td>
                    <td>
                        <span class="monitoring-badge ${isOnline ? 'is-online' : 'is-offline'}">
                            ${isOnline ? 'Online' : 'Offline'}
                        </span>
                    </td>
                    <td>${escapeHtml(router.uptime)}</td>
                    <td class="text-end">${escapeHtml(router.cpu_load)}${router.cpu_load !== '-' ? '%' : ''}</td>
                    <td class="text-end">${formatMemory(router.free_memory)}</td>
                </tr>
            `;
        }).join('');
    }

    function renderRows(rows) {
        if (!rows.length) {
            rowsBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">
                        Tidak ada PPPoE active session.
                    </td>
                </tr>
            `;
            return;
        }

        rowsBody.innerHTML = rows.map(function (row) {
            return `
                <tr>
                    <td>${escapeHtml(row.username)}</td>
                    <td>${escapeHtml(row.address)}</td>
                    <td>${escapeHtml(row.caller_id)}</td>
                    <td>${escapeHtml(row.uptime)}</td>
                    <td>${escapeHtml(row.router)}</td>
                    <td><span class="monitoring-badge is-online">${escapeHtml(row.status)}</span></td>
                    <td>
                        <button
                            type="button"
                            class="monitoring-action btn-disconnect-session"
                            data-router-id="${escapeHtml(row.router_id)}"
                            data-active-id="${escapeHtml(row.active_id)}"
                        >
                            Disconnect
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function updateCounters(data) {
        routerOnlineCount.textContent = data.routers_online || 0;
        activeSessionCount.textContent = data.active_sessions || 0;
    }

    function showErrors(errors) {
        if (errors && errors.length) {
            setMessage(errors.join(' | '), true);
            return;
        }

        setMessage('Data berhasil diperbarui.');
    }

    async function loadSessions() {
        pollingStatus.textContent = '...';

        try {
            const response = await fetch(sessionsUrl, {
                loader: false,
                headers: {
                    Accept: 'application/json'
                }
            });
            const data = await response.json();

            updateCounters(data);
            renderResources(data.routers || []);
            renderRows(data.rows || []);
            showErrors(data.errors || []);
        } catch (error) {
            setMessage('Gagal mengambil data monitoring.', true);
        } finally {
            pollingStatus.textContent = '30s';
        }
    }

    async function disconnectSession(button) {
        const formData = new FormData();
        formData.append('router_id', button.dataset.routerId);
        formData.append('active_id', button.dataset.activeId);

        button.disabled = true;
        button.textContent = '...';

        try {
            const response = await fetch(disconnectUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json'
                }
            });
            const data = await response.json();
            setMessage(data.message || 'Perintah disconnect selesai.', !data.success);
            loadSessions();
        } catch (error) {
            setMessage('Gagal mengirim perintah disconnect.', true);
        } finally {
            button.disabled = false;
            button.textContent = 'Disconnect';
        }
    }

    rowsBody.addEventListener('click', function (event) {
        const button = event.target.closest('.btn-disconnect-session');

        if (!button) {
            return;
        }

        if (confirm('Disconnect user ini?')) {
            disconnectSession(button);
        }
    });

    loadSessions();
    setInterval(loadSessions, 30000);
})();
