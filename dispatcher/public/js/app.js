const POLL_INTERVAL  = 3000;  // ms — how often to refresh jobs + stats
const MAX_JOBS       = 50;    // max rows shown in job list
const CHART_POINTS   = 20;    // throughput chart history length
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;

let throughputHistory = Array(CHART_POINTS).fill(0);
let chart;

document.addEventListener('DOMContentLoaded', () => {
    initChart();
    initTypeToggle();
    refreshStats();
    refreshJobs();
    setInterval(refreshStats, POLL_INTERVAL);
    setInterval(refreshJobs, POLL_INTERVAL);
});

async function poll() {
    await Promise.all([
        refreshStats(),
        refreshJobs(),
    ]);
}

async function refreshStats() {
    try {
        const res  = await fetch('/api/stats');
        const data = await res.json();

        setText('stat-depth',      data.queue_depth);
        setText('stat-throughput', data.throughput);
        setText('stat-failure',    data.failure_rate + '%');
        setText('stat-p50',        data.latency.p50 + 's');
        setText('stat-p95',        data.latency.p95 + 's');
        setText('stat-p99',        data.latency.p99 + 's');

        // pulse queue depth when active
        const depthEl = document.getElementById('stat-depth');
        depthEl.classList.toggle('active', data.queue_depth > 0);

        // update throughput chart
        throughputHistory.push(data.throughput);
        throughputHistory.shift();
        chart.data.datasets[0].data = [...throughputHistory];
        chart.update('none'); // 'none' skips animation for smooth live feel

        // connection dot
        document.getElementById('connection-status').classList.add('live');

    } catch {
        document.getElementById('connection-status').classList.remove('live');
    }
}

async function refreshJobs() {
    try {
        const res  = await fetch('/api/dispatches?limit=' + MAX_JOBS);
        const data = await res.json();

        const jobs = data.data ?? data; // handle paginated or plain array

        document.getElementById('job-count').textContent = jobs.length;

        const tbody = document.getElementById('job-tbody');

        if (jobs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="empty">No jobs yet</td></tr>';
            return;
        }

        tbody.innerHTML = jobs.map(job => `
            <tr>
                <td>${job.id}</td>
                <td>${formatType(job.type)}</td>
                <td>${statusBadge(job.status)}</td>
                <td>${job.attempts}</td>
                <td>${timeAgo(job.created_at)}</td>
            </tr>
        `).join('');

    } catch (e) {
        console.error('Job list refresh failed:', e);
    }
}
async function submitJob() {
    const type   = document.getElementById('job-type').value;
    const result = document.getElementById('submit-result');
    const btn    = document.getElementById('submit-btn');

    const payload = type === 'payment'
        ? {
            amount:      parseFloat(document.getElementById('amount').value),
            customer_id: document.getElementById('customer-id').value,
          }
        : {
            product_id: parseInt(document.getElementById('product-id').value),
            quantity:   parseInt(document.getElementById('quantity').value),
          };

    btn.disabled = true;
    result.className = 'msg-info';
    result.textContent = 'Submitting...';

    try {
        const res  = await post('/api/dispatches', {
            type,
            payload,
            idempotency_key: crypto.randomUUID(),
        });

        const data = await res.json();

        if (res.ok) {
            result.className = 'msg-success';
            result.textContent = `Job #${data.id} created`;
        } else {
            result.className = 'msg-error';
            result.textContent = data.message ?? 'Request failed';
        }

    } catch {
        result.className = 'msg-error';
        result.textContent = 'Could not reach server';
    }

    btn.disabled = false;
}

async function fire50() {
    const btn    = document.getElementById('fire-btn');
    const result = document.getElementById('fire-result');

    btn.disabled = true;
    result.className = 'msg-info';
    result.textContent = 'Firing 50 jobs...';

    try {
        const res  = await post('/api/dispatches/batch', {
            count: 50,
            type: 'payment',
            payload: { amount: 49.99, customer_id: 'demo-cust' },
        });
        const data = await res.json();
        result.className = res.ok ? 'msg-success' : 'msg-error';
        result.textContent = res.ok
            ? `${data.created} jobs created`
            : (data.message ?? 'Request failed');
    } catch {
        result.className = 'msg-error';
        result.textContent = 'Could not reach server';
    }

    btn.disabled = false;
}

function initChart() {
    const ctx = document.getElementById('throughput-chart').getContext('2d');

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels:   Array(CHART_POINTS).fill(''),
            datasets: [{
                data:            [...throughputHistory],
                borderColor:     '#6366f1',
                backgroundColor: 'rgba(99,102,241,0.08)',
                borderWidth:     2,
                pointRadius:     0,
                fill:            true,
                tension:         0.4,
            }],
        },
        options: {
            responsive:       true,
            maintainAspectRatio: false,
            animation:        false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    display: false,
                },
                y: {
                    min: 0,
                    grid:   { color: 'rgba(255,255,255,0.04)' },
                    ticks:  { color: '#6b7280', font: { size: 11 } },
                },
            },
        },
    });
}
function initTypeToggle() {
    document.getElementById('job-type').addEventListener('change', e => {
        const isPayment = e.target.value === 'payment';
        document.getElementById('payment-fields').classList.toggle('hidden', !isPayment);
        document.getElementById('inventory-fields').classList.toggle('hidden', isPayment);
    });
}
function post(url, body) {
    return fetch(url, {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  CSRF,
        },
        body: JSON.stringify(body),
    });
}

function setText(id, value) {
    document.getElementById(id).textContent = value ?? '—';
}

function formatType(type) {
    return {
        payment:   'payment',
        inventory: 'inventory',
    }[type] ?? type;
}

function statusBadge(status) {
    return `<span class="badge-status badge-${status}">${status}</span>`;
}

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return diff + 's ago';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    return Math.floor(diff / 3600) + 'h ago';
}