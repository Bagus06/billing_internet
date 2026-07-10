(function () {
    const splitterDataEl = document.getElementById('splitter-loss-data');
    const splitterData = splitterDataEl ? JSON.parse(splitterDataEl.textContent || '{}') : {};
    const splitterCards = Array.from(document.querySelectorAll('.splitter-card'));

    function toNumber(name, fallback = 0) {
        const input = document.querySelector(`[name="${name}"]`);
        const value = input ? parseFloat(input.value) : NaN;

        return Number.isFinite(value) ? value : fallback;
    }

    function formatNumber(value) {
        return Number(value).toFixed(2);
    }

    function getSplitterLoss(card) {
        const type = card.querySelector('.splitter-type').value;
        const ratio = card.querySelector('.splitter-ratio').value;
        const port = card.querySelector('.splitter-port').value;

        if (!type || !ratio || !port || !splitterData[type] || !splitterData[type][ratio]) {
            return null;
        }

        const loss = splitterData[type][ratio][port];

        return Number.isFinite(Number(loss)) ? Number(loss) : null;
    }

    function addStep(steps, device, loss, output) {
        steps.push({
            device,
            loss,
            output
        });
    }

    function renderSteps(steps) {
        const body = document.getElementById('detailStepsBody');

        body.innerHTML = steps.map(function (step, index) {
            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${step.device}</td>
                    <td class="text-end">${formatNumber(step.loss)}</td>
                    <td class="text-end"><strong>${formatNumber(step.output)}</strong></td>
                </tr>
            `;
        }).join('');
    }

    function setRxStatus(rxPower) {
        const box = document.getElementById('rxResultBox');
        const statusEl = document.getElementById('resultStatus');
        let status = 'Normal';
        let colorClass = 'rx-ideal';

        if (rxPower >= -8) {
            status = 'Terlalu Kuat';
            colorClass = 'rx-strong';
        } else if (rxPower < -28) {
            status = 'Terlalu Lemah';
            colorClass = 'rx-weak';
        }

        box.className = `rx-card shadow-sm result-box ${colorClass}`;
        statusEl.textContent = status;
    }

    function calculateCascade() {
        const txPower = toNumber('tx_power', 4);
        const fiberKm = toNumber('fiber', 0);
        const attenuation = toNumber('attenuation', 0.35);
        const connectorQty = toNumber('connector', 0);
        const connectorLossVal = toNumber('connector_loss', 0.2);
        const spliceQty = toNumber('splice', 0);
        const spliceLossVal = toNumber('splice_loss', 0.05);
        const margin = toNumber('margin', 0);

        let currentPower = txPower;
        let totalLoss = 0;
        const steps = [];

        addStep(steps, 'Tx Power OLT', 0, currentPower);

        const fiberLoss = fiberKm * attenuation;
        currentPower -= fiberLoss;
        totalLoss += fiberLoss;
        addStep(steps, `Fiber Cable (${fiberKm} Km)`, fiberLoss, currentPower);

        const connectorLoss = connectorQty * connectorLossVal;
        currentPower -= connectorLoss;
        totalLoss += connectorLoss;
        addStep(steps, `Connector (${connectorQty} pcs)`, connectorLoss, currentPower);

        const spliceLoss = spliceQty * spliceLossVal;
        currentPower -= spliceLoss;
        totalLoss += spliceLoss;
        addStep(steps, `Fusion Splice (${spliceQty} titik)`, spliceLoss, currentPower);

        splitterCards.forEach(function (card, index) {
            const type = card.querySelector('.splitter-type').value;
            const ratio = card.querySelector('.splitter-ratio').value;
            const port = card.querySelector('.splitter-port').value;
            const loss = getSplitterLoss(card);
            const lossInfo = card.querySelector('.splitter-loss-info');
            const outputInfo = card.querySelector('.splitter-output-info');

            if (loss === null) {
                lossInfo.innerHTML = 'Loss: -';
                outputInfo.innerHTML = 'Output Laser: -';
                return;
            }

            currentPower -= loss;
            totalLoss += loss;

            lossInfo.innerHTML = 'Loss: <strong>' + formatNumber(loss) + ' dB</strong>';
            outputInfo.innerHTML = 'Output Laser: <strong>' + formatNumber(currentPower) + ' dBm</strong>';

            addStep(
                steps,
                `Splitter ${index + 1} - ${type} ${ratio} / Output ${port}`,
                loss,
                currentPower
            );
        });

        const totalLossWithMargin = totalLoss + margin;
        const rxPower = txPower - totalLossWithMargin;

        document.getElementById('resultTxPower').textContent = formatNumber(txPower);
        document.getElementById('resultTotalLoss').textContent = formatNumber(totalLossWithMargin);
        document.getElementById('resultRxPower').textContent = formatNumber(rxPower);

        setRxStatus(rxPower);
        renderSteps(steps);
    }

    function fillRatio(card) {
        const typeSelect = card.querySelector('.splitter-type');
        const ratioSelect = card.querySelector('.splitter-ratio');
        const portSelect = card.querySelector('.splitter-port');
        const type = typeSelect.value;

        ratioSelect.innerHTML = '<option value="">Ratio</option>';
        portSelect.innerHTML = '<option value="">Output Port</option>';

        if (!type || !splitterData[type]) {
            calculateCascade();
            return;
        }

        Object.keys(splitterData[type]).forEach(function (ratio) {
            ratioSelect.innerHTML += `<option value="${ratio}">${ratio}</option>`;
        });

        calculateCascade();
    }

    function fillPort(card) {
        const typeSelect = card.querySelector('.splitter-type');
        const ratioSelect = card.querySelector('.splitter-ratio');
        const portSelect = card.querySelector('.splitter-port');
        const type = typeSelect.value;
        const ratio = ratioSelect.value;

        portSelect.innerHTML = '<option value="">Output Port</option>';

        if (!type || !ratio || !splitterData[type][ratio]) {
            calculateCascade();
            return;
        }

        Object.keys(splitterData[type][ratio]).forEach(function (port) {
            const loss = splitterData[type][ratio][port];
            portSelect.innerHTML += `<option value="${port}">${port} (${loss} dB)</option>`;
        });

        calculateCascade();
    }

    splitterCards.forEach(function (card) {
        const typeSelect = card.querySelector('.splitter-type');
        const ratioSelect = card.querySelector('.splitter-ratio');
        const portSelect = card.querySelector('.splitter-port');

        typeSelect.addEventListener('change', function () {
            fillRatio(card);
        });

        ratioSelect.addEventListener('change', function () {
            fillPort(card);
        });

        portSelect.addEventListener('change', calculateCascade);
    });

    document.querySelectorAll('.calc-input').forEach(function (input) {
        input.addEventListener('input', calculateCascade);
    });

    calculateCascade();
})();
