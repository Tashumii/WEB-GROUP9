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
            <div class="stat-value">₱2,340</div>
            <div class="stat-note">Nov 4, 2026</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Revenue this month</div>
            <div class="stat-value">₱63,124</div>
            <div class="stat-note">+12% vs last month</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Low stock items</div>
            <div class="stat-value">3</div>
            <div class="stat-note">Needs restocking</div>
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
                        <tbody>
                            <tr>
                                <td>Nov 4</td>
                                <td>11:02AM</td>
                                <td class="fw-semibold">Ballpen black</td>
                                <td>Product</td>
                                <td class="text-end">5</td>
                                <td class="text-end fw-semibold">₱40</td>
                            </tr>
                            <tr>
                                <td>Nov 4</td>
                                <td>10:45AM</td>
                                <td class="fw-semibold">Colored Printing</td>
                                <td>Service</td>
                                <td class="text-end">8</td>
                                <td class="text-end fw-semibold">₱120</td>
                            </tr>
                            <tr>
                                <td>Nov 4</td>
                                <td>9:30AM</td>
                                <td class="fw-semibold">Photocopy</td>
                                <td>Service</td>
                                <td class="text-end">30</td>
                                <td class="text-end fw-semibold">₱60</td>
                            </tr>
                            <tr>
                                <td>Nov 3</td>
                                <td>4:12PM</td>
                                <td class="fw-semibold">A4 paper</td>
                                <td>Product</td>
                                <td class="text-end">2</td>
                                <td class="text-end fw-semibold">₱570</td>
                            </tr>
                            <tr>
                                <td>Nov 3</td>
                                <td>1:50PM</td>
                                <td class="fw-semibold">Crayons</td>
                                <td>Product</td>
                                <td class="text-end">3</td>
                                <td class="text-end fw-semibold">₱105</td>
                            </tr>
                        </tbody>
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
                        <i class="bi bi-plus-circle me-1"></i> Record a sale
                    </a>
                    <a href="Products.php" class="btn btn-pill">
                        <i class="bi bi-box-seam me-1"></i> Manage products
                    </a>
                    <a href="Services.php" class="btn btn-pill">
                        <i class="bi bi-printer me-1"></i> Manage services
                    </a>
                    <a href="Analytics.php" class="btn btn-pill">
                        <i class="bi bi-graph-up-arrow me-1"></i> View analytics
                    </a>
                </div>

                <hr class="my-3">

                <!-- stock alerts -->
                <strong class="d-block mb-2">Stock alerts</strong>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-semibold" style="font-size:.82rem;">Ballpen black</span>
                    <span class="status-badge status-low">Low stock</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="fw-semibold" style="font-size:.82rem;">Pencil</span>
                    <span class="status-badge status-low">Low stock</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold" style="font-size:.82rem;">Eraser</span>
                    <span class="status-badge status-low">Low stock</span>
                </div>
            </div>
        </section>
    </div>

</div>

<?php include 'includes/footer.php'; ?>

