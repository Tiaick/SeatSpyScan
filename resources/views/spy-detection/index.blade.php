@extends('web::layouts.grids.12')

@section('title', 'Spy Detection')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Spy Detection</h3>
            </div>
            <div class="card-body">
                <form id="spy-detection-form" class="form-inline">
                    @csrf
                    <div class="form-group">
                        <label for="character_name" class="mr-2">Character name</label>
                        <input type="text" class="form-control mr-2" id="character_name" name="character_name" placeholder="Character name">
                    </div>
                    <button type="submit" class="btn btn-primary">Start scan</button>
                </form>
                <div id="spy-detection-status" class="mt-3 text-muted"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3" id="spy-detection-results" style="display:none;">
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Results</h3>
                <button type="button" id="spy-download-json" class="btn btn-sm btn-outline-secondary">Download JSON</button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="h4" id="spy-score">0</span>
                    <span class="badge badge-secondary" id="spy-risk-badge">low</span>
                </div>
                <div class="mb-3">
                    <label for="spy-filter">Filter severity</label>
                    <select id="spy-filter" class="form-control form-control-sm" style="max-width: 240px;">
                        <option value="all">All</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div id="spy-findings"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
(() => {
    const form = document.getElementById('spy-detection-form');
    const statusEl = document.getElementById('spy-detection-status');
    const resultsEl = document.getElementById('spy-detection-results');
    const scoreEl = document.getElementById('spy-score');
    const riskBadgeEl = document.getElementById('spy-risk-badge');
    const findingsEl = document.getElementById('spy-findings');
    const filterEl = document.getElementById('spy-filter');
    const downloadBtn = document.getElementById('spy-download-json');
    const storageKey = 'seat_spy_scan_last_result';

    let lastResult = null;

    function setStatus(text) {
        statusEl.textContent = text || '';
    }

    function riskBadgeClass(level) {
        switch (level) {
            case 'critical': return 'badge-danger';
            case 'high': return 'badge-warning';
            case 'medium': return 'badge-info';
            case 'low': return 'badge-success';
            default: return 'badge-secondary';
        }
    }

    function renderEvidenceTable(evidence) {
        if (!Array.isArray(evidence) || evidence.length === 0) {
            return '';
        }
        const columns = Object.keys(evidence[0]);
        const header = columns.map(col => `<th>${col}</th>`).join('');
        const rows = evidence.map(row => {
            const cells = columns.map(col => `<td>${row[col] ?? ''}</td>`).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
        return `
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr>${header}</tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderFindings(findings, filter) {
        if (!Array.isArray(findings)) {
            findingsEl.innerHTML = '';
            return;
        }
        const filtered = filter && filter !== 'all'
            ? findings.filter(item => item.severity === filter)
            : findings;

        if (filtered.length === 0) {
            findingsEl.innerHTML = '<div class="text-muted">No findings for this filter.</div>';
            return;
        }

        findingsEl.innerHTML = filtered.map(item => `
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between">
                    <strong>${item.title}</strong>
                    <span class="badge ${riskBadgeClass(item.severity)}">${item.severity}</span>
                </div>
                <div class="text-muted">${item.detail}</div>
                <div class="mt-2">${renderEvidenceTable(item.evidence)}</div>
            </div>
        `).join('');
    }

    function renderResult(result) {
        lastResult = result;
        resultsEl.style.display = 'block';
        scoreEl.textContent = result.score ?? 0;
        riskBadgeEl.textContent = result.risk_level || 'low';
        riskBadgeEl.className = 'badge ' + riskBadgeClass(result.risk_level);
        renderFindings(result.findings || [], filterEl.value);
        sessionStorage.setItem(storageKey, JSON.stringify(result));
    }

    function downloadJson() {
        if (!lastResult) return;
        const dataStr = JSON.stringify(lastResult, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'spy-detection-result.json';
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    async function pollToken(token) {
        const statusUrl = `/spy-detection/scan/${token}`;
        setStatus('Scan queued. Waiting for completion...');
        for (let i = 0; i < 60; i++) {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            if (payload.status === 'completed') {
                setStatus('Scan completed.');
                renderResult(payload.result);
                return;
            }
            if (payload.status === 'failed') {
                setStatus(payload.error || 'Scan failed.');
                return;
            }
            await new Promise(r => setTimeout(r, 2000));
        }
        setStatus('Scan still running. Please try again.');
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const name = document.getElementById('character_name').value.trim();
        if (!name) {
            setStatus('Please enter a character name.');
            return;
        }
        setStatus('Scanning...');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch('/spy-detection/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ character_name: name })
        });

        const payload = await response.json();
        if (payload.status === 'completed') {
            setStatus('Scan completed.');
            renderResult(payload.result);
        } else if (payload.status === 'pending') {
            await pollToken(payload.token);
        } else {
            setStatus(payload.error || 'Scan failed.');
        }
    });

    filterEl.addEventListener('change', () => {
        if (lastResult) {
            renderFindings(lastResult.findings || [], filterEl.value);
        }
    });

    downloadBtn.addEventListener('click', downloadJson);

    const cached = sessionStorage.getItem(storageKey);
    if (cached) {
        try {
            renderResult(JSON.parse(cached));
            setStatus('Loaded last result from session storage.');
        } catch (e) {
            sessionStorage.removeItem(storageKey);
        }
    }
})();
</script>
@endpush
