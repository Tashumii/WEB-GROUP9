<?php

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Overview of your shop activity';
$activePage   = 'dashboard';

include 'includes/header.php';
?>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Today's revenue</div>
            <div class="stat-value" id="todayRevenue">₱0</div>
            <div class="stat-note" id="todayNote">&nbsp;</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Revenue this month</div>
            <div class="stat-value" id="monthRevenue">₱0</div>
            <div class="stat-note" id="monthNote">&nbsp;</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Low stock items</div>
            <div class="stat-value" id="lowStockCount">0</div>
            <div class="stat-note" id="lowStockNote">Needs restocking</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- recent sales -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <strong>Recent sales</strong>
                    <a href="Sales.php" class="btn btn-pill">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody id="recentSalesBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- quick actions -->
    <div class="col-xl-4">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <strong class="d-block mb-3">Quick actions</strong>
                <div class="d-grid gap-2">
                    <a href="RecordSales.php" class="btn btn-pill active">
                        <i class="fa-solid fa-circle-plus me-1"></i> Record a sale
                    </a>
                    <a href="Products.php" class="btn btn-pill">
                        <i class="fa-solid fa-box me-1"></i> Manage products
                    </a>
                    <a href="Services.php" class="btn btn-pill">
                        <i class="fa-solid fa-print me-1"></i> Manage services
                    </a>
                    <a href="Analytics.php" class="btn btn-pill">
                        <i class="fa-solid fa-arrow-trend-up me-1"></i> View analytics
                    </a>
                </div>

                <hr class="my-3">

                <!-- stock alerts -->
                <strong class="d-block mb-2">Stock alerts</strong>
                <div id="stockAlerts"></div>
              </div>
        </section>
    </div>

</div>

<script>
(function () {
    function localDateString(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function friendlyDate(value) {
        var parts = value.split('-');
        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    var today = new Date();
    var todayStr = localDateString(today);
    var monthStart = todayStr.slice(0, 8) + '01';

    document.getElementById('todayNote').textContent = friendlyDate(todayStr);

    Promise.all([
        Api.get('analytics.php', { date_from: todayStr, date_to: todayStr }),
        Api.get('analytics.php', { date_from: monthStart, date_to: todayStr }),
        Api.get('sales.php', { date_from: monthStart, date_to: todayStr }),
        Api.get('products.php')
    ]).then(function (results) {
        var todayStats = results[0];
        var monthStats = results[1];
        var salesData = results[2];
        var productsData = results[3];

        document.getElementById('todayRevenue').textContent = peso(todayStats.kpi.total_revenue || 0);
        document.getElementById('monthRevenue').textContent = peso(monthStats.kpi.total_revenue || 0);
        document.getElementById('monthNote').textContent = 'Since ' + friendlyDate(monthStart);

        var lowStock = (productsData.products || []).filter(function (product) {
            return product.status === 'active' && Number(product.stock) <= 10;
        });
        document.getElementById('lowStockCount').textContent = lowStock.length;
        document.getElementById('lowStockNote').textContent = lowStock.length ? 'Needs restocking' : 'All stocked up';

        var alerts = document.getElementById('stockAlerts');
        alerts.innerHTML = '';
        if (!lowStock.length) {
            alerts.innerHTML = '<p class="text-muted mb-0" style="font-size:.82rem;">No low stock items.</p>';
        } else {
            lowStock.forEach(function (product, index) {
                var row = document.createElement('div');
                row.className = 'd-flex justify-content-between align-items-center py-2' + (index < lowStock.length - 1 ? ' border-bottom' : '');
                row.innerHTML =
                    '<span class="fw-semibold" style="font-size:.82rem;">' + escapeHtml(product.name) + '</span>' +
                    '<span class="status-badge status-low">' + Number(product.stock) + ' left</span>';
                alerts.appendChild(row);
            });
        }

        var recentBody = document.getElementById('recentSalesBody');
        recentBody.innerHTML = '';
        var recent = (salesData.sales || []).slice(0, 5);
        if (!recent.length) {
            recentBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No sales recorded this month.</td></tr>';
        } else {
            recent.forEach(function (sale) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + escapeHtml(sale.date) + '</td>' +
                    '<td>' + escapeHtml(sale.time) + '</td>' +
                    '<td class="fw-semibold">' + escapeHtml(sale.item_name) + '</td>' +
                    '<td>' + escapeHtml(sale.type) + '</td>' +
                    '<td class="text-end">' + Number(sale.quantity) + '</td>' +
                    '<td class="text-end fw-semibold">' + peso(sale.total) + '</td>';
                recentBody.appendChild(tr);
            });
        }
    }).catch(function (err) {
        document.getElementById('recentSalesBody').innerHTML =
            '<tr><td colspan="6" class="text-center text-danger py-4">' + escapeHtml(err.message) + '</td></tr>';
    });
})();
</script>

<?php include 'includes/footer.php'; ?>

