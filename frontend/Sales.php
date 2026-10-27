<?php

$pageTitle    = 'Sales history';
$pageSubtitle = 'Every recorded sale, with quantity, unit price and total';
$activePage   = 'sales-history';

include 'includes/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="style.css">

<!-- Button for today, weeks, month -->
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
                <input type="date" id="dateFrom" class="form-control ui-input" value="2026-09-28">
            </div>
            <div class="col-12 col-md-4">
                <label for="dateTo" class="field-label">To</label>
                <input type="date" id="dateTo" class="form-control ui-input" value="2026-10-05">
            </div>
            <div class="col-12 col-md-4">
                <label for="typeFilter" class="field-label">Type</label>
                <select id="typeFilter" class="form-select ui-input">
                    <option value="all" selected>Products &amp; Services</option>
                    <option value="Product">Products</option>
                    <option value="Service">Services</option>
                </select>
            </div>
        </div>

        <div>
            <label for="search" class="field-label">Product or service</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
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
            <div class="stat-label">Total sales Sep 28 - Oct 04</div>
            <div class="stat-value">₱53,000</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Records in range</div>
            <div class="stat-value" id="recordCount">214</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Products vs services</div>
            <div class="stat-value">62% / 38%</div>
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
                <tbody>
                    <tr data-date="2026-10-04" data-type="Product">
                        <td>Oct 4</td>
                        <td>7:41PM</td>
                        <td class="fw-semibold">Ballpen</td>
                        <td>Product</td>
                        <td class="text-end">10</td>
                        <td class="text-end">₱8</td>
                        <td class="text-end fw-semibold">₱80</td>
                    </tr>
                    <tr data-date="2026-10-04" data-type="Service">
                        <td>Oct 4</td>
                        <td>5:12PM</td>
                        <td class="fw-semibold">Colored Printing</td>
                        <td>Service</td>
                        <td class="text-end">12</td>
                        <td class="text-end">₱15</td>
                        <td class="text-end fw-semibold">₱180</td>
                    </tr>
                    <tr data-date="2026-10-03" data-type="Service">
                        <td>Oct 3</td>
                        <td>2:30PM</td>
                        <td class="fw-semibold">Photocopy</td>
                        <td>Service</td>
                        <td class="text-end">50</td>
                        <td class="text-end">₱2</td>
                        <td class="text-end fw-semibold">₱100</td>
                    </tr>
                    <tr data-date="2026-10-02" data-type="Product">
                        <td>Oct 2</td>
                        <td>10:05AM</td>
                        <td class="fw-semibold">A4</td>
                        <td>Product</td>
                        <td class="text-end">2</td>
                        <td class="text-end">₱285</td>
                        <td class="text-end fw-semibold">₱570</td>
                    </tr>
                    <tr data-date="2026-09-30" data-type="Product">
                        <td>Sep 30</td>
                        <td>1:18PM</td>
                        <td class="fw-semibold">Crayons</td>
                        <td>Product</td>
                        <td class="text-end">3</td>
                        <td class="text-end">₱35</td>
                        <td class="text-end fw-semibold">₱105</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="emptyState" class="text-center text-muted py-4 d-none">No sales found for these filters.</div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>