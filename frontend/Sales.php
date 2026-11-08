<?php

$pageTitle    = 'Sales history';
$pageSubtitle = 'Every recorded sale, with quantity, unit price and total';
$activePage   = 'sales';

include 'includes/header.php';
?>

<!-- range pills -->
<section class="ui-card mb-3">
    <div class="ui-card-body">

        <!-- Quick range pills -->
        <div class="d-flex flex-wrap gap-2 mb-3" id="rangePills">
            <button type="button" class="btn btn-pill" data-range="today">Today</button>
            <button type="button" class="btn btn-pill active" data-range="week">This week</button>
            <button type="button" class="btn btn-pill" data-range="month">This month</button>
            <button type="button" class="btn btn-pill" data-range="custom">Custom range</button>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <label for="dateFrom" class="field-label">From</label>
                <input type="date" id="dateFrom" class="form-control ui-input" value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="dateTo" class="field-label">To</label>
                <input type="date" id="dateTo" class="form-control ui-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label for="typeFilter" class="field-label">Type</label>
                <select id="typeFilter" class="form-select ui-input">
                    <option value="all" selected>Products &amp; Services</option>
                    <option value="product">Products</option>
                    <option value="service">Services</option>
                </select>
            </div>
        </div>

        <div>
            <label for="search" class="field-label">Product or service</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" id="search" class="form-control ui-input border-start-0"
                    placeholder="Search by name">
            </div>
        </div>

    </div>
</section>

<!-- Stat cards for the selected range -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label" id="salesTotalLabel">Total sales</div>
            <div class="stat-value" id="salesTotal">₱0</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Records in range</div>
            <div class="stat-value" id="recordCount">0</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Products vs services</div>
            <div class="stat-value" id="mixValue">0% / 0%</div>
        </div>
    </div>
</section>

<!-- Sales table -->
<section class="ui-card">
    <div class="ui-card-body">
        <div class="table-responsive">
            <table class="table sales-table align-middle mb-0" id="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody id="salesBody"></tbody>
            </table>
        </div>
        <div id="emptyState" class="text-center text-muted py-4 d-none">No sales found for these filters.</div>
    </div>
</section>

<script>
(function () {
    var els = {
        body: document.getElementById('salesBody'),
        dateFrom: document.getElementById('dateFrom'),
        dateTo: document.getElementById('dateTo'),
        type: document.getElementById('typeFilter'),
        search: document.getElementById('search'),
        total: document.getElementById('salesTotal'),
        totalLabel: document.getElementById('salesTotalLabel'),
        count: document.getElementById('recordCount'),
        mix: document.getElementById('mixValue'),
        empty: document.getElementById('emptyState'),
        pills: document.getElementById('rangePills')
    };

    function localDateString(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function formatDate(value) {
        if (!value) return '';
        var parts = value.split('-');
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function setActivePill(range) {
        els.pills.querySelectorAll('.btn-pill').forEach(function (pill) {
            pill.classList.toggle('active', pill.dataset.range === range);
        });
    }

    function render(data) {
        var rows = data.sales || [];
        var summary = data.summary || {};

        els.body.innerHTML = '';
        rows.forEach(function (sale) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(sale.date) + '</td>' +
                '<td>' + escapeHtml(sale.time) + '</td>' +
                '<td class="fw-semibold">' + escapeHtml(sale.item_name) + '</td>' +
                '<td>' + escapeHtml(sale.type) + '</td>' +
                '<td class="text-end">' + Number(sale.quantity) + '</td>' +
                '<td class="text-end">' + peso(sale.unit_price) + '</td>' +
                '<td class="text-end fw-semibold">' + peso(sale.total) + '</td>';
            els.body.appendChild(tr);
        });

        els.total.textContent = peso(summary.total_amount || 0);
        els.count.textContent = summary.record_count || 0;
        els.mix.textContent = (summary.product_percentage || 0) + '% / ' + (summary.service_percentage || 0) + '%';

        if (els.dateFrom.value && els.dateTo.value) {
            els.totalLabel.textContent = 'Total sales ' + formatDate(els.dateFrom.value) + ' - ' + formatDate(els.dateTo.value);
        } else {
            els.totalLabel.textContent = 'Total sales';
        }

        els.empty.classList.toggle('d-none', rows.length > 0);
    }

    function loadSales() {
        var params = {};
        if (els.dateFrom.value) params.date_from = els.dateFrom.value;
        if (els.dateTo.value) params.date_to = els.dateTo.value;
        if (els.type.value !== 'all') params.type = els.type.value;
        if (els.search.value.trim()) params.search = els.search.value.trim();

        Api.get('sales.php', params).then(render).catch(function (err) {
            els.body.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">' + escapeHtml(err.message) + '</td></tr>';
            els.empty.classList.add('d-none');
        });
    }

    function applyRange(range) {
        var today = localDateString(new Date());

        if (range === 'today') {
            els.dateFrom.value = today;
            els.dateTo.value = today;
        } else if (range === 'week') {
            var weekStart = new Date();
            weekStart.setDate(weekStart.getDate() - 7);
            els.dateFrom.value = localDateString(weekStart);
            els.dateTo.value = today;
        } else if (range === 'month') {
            var monthStart = new Date();
            monthStart.setDate(1);
            els.dateFrom.value = localDateString(monthStart);
            els.dateTo.value = today;
        } else if (range === 'custom') {
            els.dateFrom.focus();
            return;
        }

        loadSales();
    }

    els.pills.addEventListener('click', function (event) {
        var pill = event.target.closest('.btn-pill');
        if (!pill) return;
        setActivePill(pill.dataset.range);
        applyRange(pill.dataset.range);
    });

    els.dateFrom.addEventListener('change', function () {
        setActivePill('custom');
        loadSales();
    });
    els.dateTo.addEventListener('change', function () {
        setActivePill('custom');
        loadSales();
    });
    els.type.addEventListener('change', loadSales);
    els.search.addEventListener('input', debounce(loadSales, 300));

    loadSales();
})();
</script>

<?php include 'includes/footer.php'; ?>