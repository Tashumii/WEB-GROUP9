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
                <input type="date" id="dateFrom" class="form-control ui-input" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="dateTo" class="field-label">To</label>
                <input type="date" id="dateTo" class="form-control ui-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="button" class="btn btn-pill active w-100" id="applyAnalyticsBtn">Apply</button>
            </div>
        </div>
        <div id="analyticsMsg" class="text-danger mt-2 d-none" style="font-size:.8rem;"></div>
    </div>
</section>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label" id="totalRevenueLabel">Total revenue</div>
            <div class="stat-value" id="totalRevenueValue">₱0</div>
            <div class="stat-note" id="totalRevenueNote">0 transactions</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Best product</div>
            <div class="stat-value" id="bestProductValue">—</div>
            <div class="stat-note" id="bestProductNote">No sales in range</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Best service</div>
            <div class="stat-value" id="bestServiceValue">—</div>
            <div class="stat-note" id="bestServiceNote">No sales in range</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- revenue chart -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <strong class="d-block mb-3" id="trendTitle">Daily revenue</strong>
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
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#111;margin-right:4px;"></span>Products <span id="splitProductsLabel">0%</span></span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#c9c9c9;margin-right:4px;"></span>Services <span id="splitServicesLabel">0%</span></span>
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
                <strong class="d-block mb-3" id="topItemsTitle">Top items</strong>
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
                        <tbody id="topItemsBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mt-0 d-none" id="comparisonSection">
    <div class="col-12">
        <section class="ui-card">
            <div class="ui-card-body">
                <strong class="d-block mb-3">Business comparison</strong>
                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Business</th>
                                <th class="text-end">Sales</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Products</th>
                                <th class="text-end">Services</th>
                            </tr>
                        </thead>
                        <tbody id="comparisonBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    var els = {
        from: document.getElementById('dateFrom'),
        to: document.getElementById('dateTo'),
        apply: document.getElementById('applyAnalyticsBtn'),
        message: document.getElementById('analyticsMsg'),
        pills: document.getElementById('rangePills'),
        topBody: document.getElementById('topItemsBody'),
        comparisonSection: document.getElementById('comparisonSection'),
        comparisonBody: document.getElementById('comparisonBody')
    };

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.font.size = 11;

    var revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Revenue (₱)',
                data: [],
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
                y: { grid: { color: '#f0f0f0' }, ticks: { callback: function (v) { return '₱' + Number(v).toLocaleString(); } } }
            }
        }
    });

    var splitChart = new Chart(document.getElementById('splitChart'), {
        type: 'doughnut',
        data: {
            labels: ['Products', 'Services'],
            datasets: [{ data: [0, 0], backgroundColor: ['#111', '#c9c9c9'], borderWidth: 0 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            cutout: '65%'
        }
    });

    function localDateString(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function formatDate(value, withYear) {
        if (!value) return '';
        var parts = value.split('-');
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        var options = { month: 'short', day: 'numeric' };
        if (withYear) options.year = 'numeric';
        return date.toLocaleDateString(undefined, options);
    }

    function periodLabel(from, to) {
        if (!from || !to) return '';
        if (from.slice(0, 7) === to.slice(0, 7)) {
            var parts = from.split('-');
            var date = new Date(Number(parts[0]), Number(parts[1]) - 1, 1);
            return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        }
        return formatDate(from, true) + ' - ' + formatDate(to, true);
    }

    function setActivePill(range) {
        els.pills.querySelectorAll('.btn-pill').forEach(function (pill) {
            pill.classList.toggle('active', pill.dataset.range === range);
        });
    }

    function render(data) {
        var kpi = data.kpi || {};
        var label = periodLabel(data.period.from, data.period.to);

        document.getElementById('totalRevenueLabel').textContent = 'Total revenue - ' + label;
        document.getElementById('totalRevenueValue').textContent = peso(kpi.total_revenue || 0);
        document.getElementById('totalRevenueNote').textContent = (kpi.total_transactions || 0) + ' transactions';

        document.getElementById('bestProductValue').textContent = kpi.best_product ? kpi.best_product.name : '—';
        document.getElementById('bestProductNote').textContent = kpi.best_product
            ? peso(kpi.best_product.revenue) + ' · ' + kpi.best_product.units + ' units'
            : 'No sales in range';

        document.getElementById('bestServiceValue').textContent = kpi.best_service ? kpi.best_service.name : '—';
        document.getElementById('bestServiceNote').textContent = kpi.best_service
            ? peso(kpi.best_service.revenue) + ' · ' + kpi.best_service.units + ' units'
            : 'No sales in range';

        document.getElementById('trendTitle').textContent = 'Daily revenue — ' + label;
        document.getElementById('topItemsTitle').textContent = 'Top items — ' + label;

        revenueChart.data.labels = data.chart.labels || [];
        revenueChart.data.datasets[0].data = data.chart.revenue_series || [];
        revenueChart.update();

        var productPct = data.chart.products_percentage || 0;
        var servicePct = data.chart.services_percentage || 0;
        splitChart.data.datasets[0].data = [productPct, servicePct];
        splitChart.update();
        document.getElementById('splitProductsLabel').textContent = productPct + '%';
        document.getElementById('splitServicesLabel').textContent = servicePct + '%';

        els.topBody.innerHTML = '';
        var topItems = data.top_items || [];
        if (!topItems.length) {
            els.topBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No sales in this range.</td></tr>';
        } else {
            topItems.forEach(function (item) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + item.rank + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(item.name) + '</td>' +
                    '<td>' + escapeHtml(item.type) + '</td>' +
                    '<td class="text-end">' + Number(item.units) + '</td>' +
                    '<td class="text-end fw-semibold">' + peso(item.revenue) + '</td>';
                els.topBody.appendChild(tr);
            });
        }

        var comparison = data.comparison || [];
        els.comparisonBody.innerHTML = '';
        if (!comparison.length) {
            els.comparisonSection.classList.add('d-none');
        } else {
            comparison.forEach(function (business) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td class="fw-semibold">' + escapeHtml(business.name) + '</td>' +
                    '<td class="text-end">' + Number(business.sales_count) + '</td>' +
                    '<td class="text-end fw-semibold">' + peso(business.total_revenue) + '</td>' +
                    '<td class="text-end">' + peso(business.product_revenue) + '</td>' +
                    '<td class="text-end">' + peso(business.service_revenue) + '</td>';
                els.comparisonBody.appendChild(tr);
            });
            els.comparisonSection.classList.remove('d-none');
        }
    }

    function loadAnalytics() {
        var params = {
            date_from: els.from.value,
            date_to: els.to.value
        };
        params.compare = 'true';

        els.message.classList.add('d-none');
        els.apply.disabled = true;
        Api.get('analytics.php', params).then(function (data) {
            render(data);
        }).catch(function (err) {
            els.message.textContent = err.message;
            els.message.classList.remove('d-none');
        }).then(function () {
            els.apply.disabled = false;
        });
    }

    function applyRange(range) {
        var today = localDateString(new Date());

        if (range === 'today') {
            els.from.value = today;
            els.to.value = today;
        } else if (range === 'week') {
            var weekStart = new Date();
            weekStart.setDate(weekStart.getDate() - 7);
            els.from.value = localDateString(weekStart);
            els.to.value = today;
        } else if (range === 'month') {
            var monthStart = new Date();
            monthStart.setDate(1);
            els.from.value = localDateString(monthStart);
            els.to.value = today;
        } else if (range === 'custom') {
            els.from.focus();
            return;
        }

        loadAnalytics();
    }

    els.pills.addEventListener('click', function (event) {
        var pill = event.target.closest('.btn-pill');
        if (!pill) return;
        setActivePill(pill.dataset.range);
        applyRange(pill.dataset.range);
    });

    els.from.addEventListener('change', function () { setActivePill('custom'); });
    els.to.addEventListener('change', function () { setActivePill('custom'); });

    els.apply.addEventListener('click', function () {
        loadAnalytics();
        var original = 'Apply';
        els.apply.textContent = 'Applied!';
        setTimeout(function () {
            if (els.apply.textContent === 'Applied!') els.apply.textContent = original;
        }, 1500);
    });

    loadAnalytics();
})();
</script>

<?php include 'includes/footer.php'; ?>

