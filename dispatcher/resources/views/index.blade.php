<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>dispatch-engine</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
 
    <header>
        <div class="header-inner">
            <span class="logo">dispatch-engine</span>
            <span id="connection-status" class="status-dot"></span>
        </div>
    </header>
 
    <main>
 
        <section class="stats-bar">
            <div class="stat">
                <span class="stat-value" id="stat-depth">—</span>
                <span class="stat-label">Queue Depth</span>
            </div>
            <div class="stat">
                <span class="stat-value" id="stat-throughput">—</span>
                <span class="stat-label">Jobs / min</span>
            </div>
            <div class="stat">
                <span class="stat-value" id="stat-failure">—</span>
                <span class="stat-label">Failure Rate</span>
            </div>
            <div class="stat">
                <span class="stat-value" id="stat-p50">—</span>
                <span class="stat-label">p50 latency</span>
            </div>
            <div class="stat">
                <span class="stat-value" id="stat-p95">—</span>
                <span class="stat-label">p95 latency</span>
            </div>
            <div class="stat">
                <span class="stat-value" id="stat-p99">—</span>
                <span class="stat-label">p99 latency</span>
            </div>
        </section>
 
        <div class="grid">
            <div class="column">
 
                <div class="card">
                    <h2>Submit a job</h2>
 
                    <label>Type</label>
                    <select id="job-type">
                        <option value="payment">Payment</option>
                        <option value="inventory">Inventory Reservation</option>
                    </select>
 
                    <div id="payment-fields">
                        <label>Amount</label>
                        <input type="number" id="amount" value="49.99" step="0.01">
 
                        <label>Customer ID</label>
                        <input type="text" id="customer-id" value="cust_1">
                    </div>
 
                    <div id="inventory-fields" class="hidden">
                        <label>Product ID</label>
                        <input type="number" id="product-id" value="1">
 
                        <label>Quantity</label>
                        <input type="number" id="quantity" value="1">
                    </div>
 
                    <button id="submit-btn" onclick="submitJob()">Submit job</button>
 
                    <div id="submit-result"></div>
                </div>
 
                <div class="card demo-card">
                    <h2>Demo</h2>
                    <p>Fire 50 payment jobs at once and watch the queue react.</p>
                    <button id="fire-btn" class="btn-fire" onclick="fire50()">
                        Fire 50 jobs
                    </button>
                    <div id="fire-result"></div>
                </div>
 
                {{-- Throughput chart --}}
                <div class="card">
                    <h2>Throughput</h2>
                    <div style="position: relative; height: 140px;">
                        <canvas id="throughput-chart"></canvas>
                    </div>
                </div>
 
            </div>

            <div class="column">
                <div class="card">
                    <div class="job-list-header">
                        <h2>Jobs</h2>
                        <span id="job-count" class="badge">—</span>
                    </div>
                    <table id="job-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Attempts</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody id="job-tbody">
                            <tr>
                                <td colspan="5" class="empty">No jobs yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
 
        </div>
 
    </main>
 
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>