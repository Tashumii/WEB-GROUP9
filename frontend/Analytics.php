<?php

$pageTitle    = 'Analytics';
$pageSubtitle = 'Sales trends and business performance insights';
$activePage   = 'analytics';

include 'includes/header.php';
?>

<!-- date range -->
<section class="ui-card mb-3">
    <div class="ui-card-body">
        <div class="d-flex flex-wrap gap-2 mb-3" id="rangePills">
            <button type="button" class="btn btn-pill" data-range="today">Today</button>
            <button type="button" class="btn btn-pill" data-range="week">This week</button>
            <button type="button" class="btn btn-pill active" data-range="month">This month</button>
            <button type="button" class="btn btn-pill" data-range="custom">Custom range</button>
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label for="dateFrom" class="field-label">From</label>
                <input type="date" id="dateFrom" class="form-control ui-input" value="2026-10-01">
            </div>
            <div class="col-12 col-md-4">
                <label for="dateTo" class="field-label">To</label>
                <input type="date" id="dateTo" class="form-control ui-input" value="2026-10-31">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-pill active w-100" id="applyAnalyticsBtn">Apply</button>
            </div>
        </div>
    </div>
</section>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Total revenue - October</div>
            <div class="stat-value">₱166,116</div>
            <div class="stat-note">+8% vs September</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Best product</div>
            <div class="stat-value">A4 paper</div>
            <div class="stat-note">₱102,600 in October</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Best service</div>
            <div class="stat-value">Photocopy</div>
            <div class="stat-note">₱31,400 in October</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- revenue chart -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <strong class="d-block mb-3">Daily revenue — October 2026</strong>
                <canvas id="revenueChart" height="130"></canvas>
            </div>
        </section>
    </div>

    <!-- split chart -->
    <div class="col-xl-4">
        <section class="ui-card h-100">
            <div class="ui-card-body d-flex flex-column align-items-center">
                <strong class="d-block mb-3 align-self-start">Products vs Services</strong>
                <canvas id="splitChart" style="max-width:220px;max-height:220px;"></canvas>
                <div class="d-flex gap-3 mt-3" style="font-size:.78rem;">
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#111;margin-right:4px;"></span>Products 62%</span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#c9c9c9;margin-right:4px;"></span>Services 38%</span>
                </div>
            </div>
        </section>
    </div>

</div>

<!-- top items -->
<div class="row g-3 mt-0">
    <div class="col-12">
        <section class="ui-card">
            <div class="ui-card-body">
                <strong class="d-block mb-3">Top items — October 2026</strong>
                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th class="text-end">Units sold</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td class="fw-semibold">A4 paper</td>
                                <td>Product</td>
                                <td class="text-end">360</td>
                                <td class="text-end fw-semibold">₱102,600</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td class="fw-semibold">Photocopy</td>
                                <td>Service</td>
                                <td class="text-end">15,700</td>
                                <td class="text-end fw-semibold">₱31,400</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td class="fw-semibold">Colored Printing</td>
                                <td>Service</td>
                                <td class="text-end">1,972</td>
                                <td class="text-end fw-semibold">₱29,580</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td class="fw-semibold">Ballpen black</td>
                                <td>Product</td>
                                <td class="text-end">251</td>
                                <td class="text-end fw-semibold">₱2,008</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td class="fw-semibold">Crayons</td>
                                <td>Product</td>
                                <td class="text-end">30</td>
                                <td class="text-end fw-semibold">₱1,050</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    // chart defaults
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.font.size = 11;

    var days = [];
    for (var d = 1; d <= 31; d++) { days.push('Oct ' + d); }
    var revenue = [3200,4100,2800,5300,6100,4800,3900,7200,5600,4400,3100,6800,7500,5200,4600,8100,6300,4900,5800,7100,6700,4300,5500,6900,8200,5100,4700,6200,7800,5900,4100];

    // revenue chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Revenue (₱)',
                data: revenue,
                borderColor: '#111',
                backgroundColor: 'rgba(17,17,17,0.07)',
                borderWidth: 2,
                pointRadius: 2,
                fill: true,
                tension: 0.35
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                y: { grid: { color: '#f0f0f0' }, ticks: { callback: function (v) { return '₱' + v.toLocaleString(); } } }
            }
        }
    });

    // split chart
    var splitChart = new Chart(document.getElementById('splitChart'), {
        type: 'doughnut',
        data: {
            labels: ['Products', 'Services'],
            datasets: [{ data: [62, 38], backgroundColor: ['#111', '#c9c9c9'], borderWidth: 0 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            cutout: '65%'
        }
    });

    // range pills
    document.querySelectorAll('#rangePills .btn-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#rangePills .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');

            var range = b.dataset.range;
            var today = new Date().toISOString().split('T')[0];

            if (range === 'today') {
                document.getElementById('dateFrom').value = today;
                document.getElementById('dateTo').value = today;
            } else if (range === 'week') {
                var d = new Date();
                d.setDate(d.getDate() - 7);
                document.getElementById('dateFrom').value = d.toISOString().split('T')[0];
                document.getElementById('dateTo').value = today;
            } else if (range === 'month') {
                var d = new Date();
                d.setDate(1);
                document.getElementById('dateFrom').value = d.toISOString().split('T')[0];
                document.getElementById('dateTo').value = today;
            }
        });
    });

    // apply button
    document.getElementById('applyAnalyticsBtn').addEventListener('click', function () {
        var from = document.getElementById('dateFrom').value;
        var to = document.getElementById('dateTo').value;
        var btn = document.getElementById('applyAnalyticsBtn');
        btn.textContent = 'Applied!';
        setTimeout(function () { btn.textContent = 'Apply'; }, 1500);
    });
</script>

<?php include 'includes/footer.php'; ?>

