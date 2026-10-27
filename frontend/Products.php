<?php

$pageTitle    = 'Products & Services';
$pageSubtitle = 'Manage printing and other services, their prices and availability';
$activePage   = 'products';

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="css/style.css">

<!-- This is for Actions -->
<div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-pill">
        <i class="bi bi-plus"></i> Add product
    </button>
    <button type="button" class="btn btn-pill active">
        <i class="bi bi-plus"></i> Add product
    </button>
</div>

<!-- Stat cards to check yung benta it could be per day or weeks -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Services revenue - September</div>
            <div class="stat-value">₱63,124</div>
            <div class="stat-note">38% of all revenue</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Active services</div>
            <div class="stat-value">5</div>
            <div class="stat-note">1 marked unavailable</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Top services</div>
            <div class="stat-value">Photocopy</div>
            <div class="stat-note">₱31,400 in September</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- List if pipindutin mo yung button na products and services dapat yung lalabas na button for products and services lang same desgin lang din -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">

                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    <div class="d-flex gap-2" id="tabs">
                        <button type="button" class="btn btn-pill">Products</button>
                        <button type="button" class="btn btn-pill active">Services</button>
                    </div>
                    <div class="input-group ms-md-auto" style="max-width:280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="search" class="form-control ui-input border-start-0"
                            placeholder="Search services">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3" id="pills">
                    <button type="button" class="btn btn-pill active">All - 6</button>
                    <button type="button" class="btn btn-pill">Available - 5</button>
                    <button type="button" class="btn btn-pill">Unavailable - 1</button>
                </div>

                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0" id="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Ballpen black</td>
                                <td>Writing</td>
                                <td>₱8</td>
                                <td>14</td>
                                <td><span class="status-badge status-low">Low stock</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i
                                            class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">A4</td>
                                <td>Paper</td>
                                <td>₱285</td>
                                <td>30</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i
                                            class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Crayons</td>
                                <td>Art supplies</td>
                                <td>₱35</td>
                                <td>60</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i
                                            class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Pencil</td>
                                <td>Writing</td>
                                <td>₱16</td>
                                <td>6</td>
                                <td><span class="status-badge status-low">Low stock</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i
                                            class="bi bi-pencil-square"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>
    </div>

    <!-- Edit form  just in case na mali yung na type mo sa products can edit naman sa changes -->
    <div class="col-xl-4">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <form onsubmit="return false">
                    <div class="mb-3">
                        <label for="serviceName" class="field-label">Service name</label>
                        <input type="text" id="serviceName" class="form-control ui-input" value="Colored Printing">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label for="price" class="field-label">Price (₱)</label>
                            <input type="number" id="price" class="form-control ui-input" value="15.00" step="0.01">
                        </div>
                        <div class="col-7">
                            <label for="chargedPer" class="field-label">Charged per</label>
                            <select id="chargedPer" class="form-select ui-input">
                                <option selected>Page</option>
                                <option>Copy</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="field-label">Description</label>
                        <textarea id="description" class="form-control ui-input" rows="4"
                            placeholder="Full-color print on short or A4 paper. Customer's file or flash drive."></textarea>
                    </div>

                    <div class="info-box mb-3">
                        <i class="bi bi-info-square-fill"></i>
                        <span>Services don't use stock. Quantity and unit price are saved with every sale, so past sales
                            keep their original price when you change it here.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active">Save changes</button>
                        <button type="button" class="btn btn-pill">Cancel</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

</div>

<script>
    // Toggle Products / Services
    document.querySelectorAll('#tabs .btn-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#tabs .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
        });
    });
    // Toggle filter pills
    document.querySelectorAll('#pills .btn-pill').forEach(function (p) {
        p.addEventListener('click', function () {
            document.querySelectorAll('#pills .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            p.classList.add('active');
        });
    });
    // Simple search
    document.getElementById('search').addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#table tbody tr').forEach(function (r) {
            r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>