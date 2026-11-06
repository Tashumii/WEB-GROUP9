<?php

$pageTitle    = 'Products & Services';
<<<<<<< HEAD
$pageSubtitle = 'Manage products, stock quantity, prices and availability';
=======
$pageSubtitle = 'Manage your products, services, prices, and availability';
>>>>>>> 5285334 (Update frontend UI)
$activePage   = 'products';

include 'includes/header.php';
?>

<<<<<<< HEAD
<!-- action button -->
<div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-pill active" id="addProductBtn">
=======
<!-- This is for Actions -->
<div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-pill active">
>>>>>>> 5285334 (Update frontend UI)
        <i class="bi bi-plus"></i> Add product
    </button>
    <button type="button" class="btn btn-pill">
        <i class="bi bi-plus"></i> Add service
    </button>
</div>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
<<<<<<< HEAD
            <div class="stat-label">Products revenue - October</div>
            <div class="stat-value">₱102,992</div>
=======
            <div class="stat-label">Product revenue - September</div>
            <div class="stat-value">₱103,124</div>
>>>>>>> 5285334 (Update frontend UI)
            <div class="stat-note">62% of all revenue</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Active products</div>
<<<<<<< HEAD
            <div class="stat-value">6</div>
            <div class="stat-note">2 low stock</div>
=======
            <div class="stat-value">4</div>
            <div class="stat-note">2 marked low stock</div>
>>>>>>> 5285334 (Update frontend UI)
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Top product</div>
            <div class="stat-value">A4 paper</div>
<<<<<<< HEAD
            <div class="stat-note">₱102,600 in October</div>
=======
            <div class="stat-note">₱31,400 in September</div>
>>>>>>> 5285334 (Update frontend UI)
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- products list -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">

                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    <!-- page tabs -->
                    <div class="d-flex gap-2" id="tabs">
                        <button type="button" class="btn btn-pill active">Products</button>
<<<<<<< HEAD
                        <a href="Services.php" class="btn btn-pill">Services</a>
=======
                        <button type="button" class="btn btn-pill">Services</button>
>>>>>>> 5285334 (Update frontend UI)
                    </div>
                    <div class="input-group ms-md-auto" style="max-width:280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="search" class="form-control ui-input border-start-0"
                            placeholder="Search products">
                    </div>
                </div>

                <!-- status pills -->
                <div class="d-flex flex-wrap gap-2 mb-3" id="pills">
<<<<<<< HEAD
                    <button type="button" class="btn btn-pill active" data-filter="all">All</button>
                    <button type="button" class="btn btn-pill" data-filter="available">Available</button>
                    <button type="button" class="btn btn-pill" data-filter="low">Low stock</button>
=======
                    <button type="button" class="btn btn-pill active">All - 4</button>
                    <button type="button" class="btn btn-pill">Available - 2</button>
                    <button type="button" class="btn btn-pill">Low stock - 2</button>
>>>>>>> 5285334 (Update frontend UI)
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
                            <tr data-status="low">
                                <td class="fw-semibold">Ballpen black</td>
                                <td>Writing</td>
                                <td>₱8</td>
                                <td>14</td>
                                <td><span class="status-badge status-low">Low stock</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">A4 paper</td>
                                <td>Paper</td>
                                <td>₱285</td>
                                <td>30</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">Crayons</td>
                                <td>Art supplies</td>
                                <td>₱35</td>
                                <td>60</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="low">
                                <td class="fw-semibold">Pencil</td>
                                <td>Writing</td>
                                <td>₱16</td>
                                <td>6</td>
                                <td><span class="status-badge status-low">Low stock</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>
    </div>

    <!-- edit form -->
    <div class="col-xl-4">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <form id="productForm" onsubmit="return false">
                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="productName" class="field-label">Product name</label>
                        <input type="text" id="productName" class="form-control ui-input" value="Ballpen black" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="field-label">Category</label>
                        <select id="category" class="form-select ui-input">
                            <option selected>Writing</option>
                            <option>Paper</option>
                            <option>Art supplies</option>
                            <option>Office</option>
                        </select>
=======
                        <label for="serviceName" class="field-label">Product name</label>
                        <input type="text" id="serviceName" class="form-control ui-input" value="Ballpen black">
>>>>>>> 5285334 (Update frontend UI)
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="price" class="field-label">Price (₱)</label>
<<<<<<< HEAD
                            <input type="number" id="price" class="form-control ui-input" value="8.00" step="0.01" min="0" required>
                        </div>
                        <div class="col-6">
                            <label for="stock" class="field-label">Stock qty</label>
                            <input type="number" id="stock" class="form-control ui-input" value="14" min="0" required>
=======
                            <input type="number" id="price" class="form-control ui-input" value="8.00" step="0.01">
                        </div>
                        <div class="col-7">
                            <label for="chargedPer" class="field-label">Category</label>
                            <select id="chargedPer" class="form-select ui-input">
                                <option selected>Writing</option>
                                <option>Paper</option>
                                <option>Art supplies</option>
                            </select>
>>>>>>> 5285334 (Update frontend UI)
                        </div>
                    </div>

                    <div class="mb-3">
<<<<<<< HEAD
                        <label for="status" class="field-label">Status</label>
                        <select id="status" class="form-select ui-input">
                            <option value="available" selected>Available</option>
                            <option value="low">Low stock</option>
                            <option value="inactive">Inactive</option>
                        </select>
=======
                        <label for="stockQuantity" class="field-label">Stock quantity</label>
                        <input type="number" id="stockQuantity" class="form-control ui-input" value="14" min="0">
>>>>>>> 5285334 (Update frontend UI)
                    </div>

                    <div class="info-box mb-3">
                        <i class="bi bi-info-square-fill"></i>
<<<<<<< HEAD
                        <span>Physical products track live inventory. Stock is deducted automatically upon every sale recorded.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active" id="saveProductBtn">Save changes</button>
                        <button type="button" class="btn btn-pill" id="cancelProductBtn">Cancel</button>
                    </div>

                    <div id="productMsg" class="text-success text-center mt-2 d-none" style="font-size:0.8rem;">
                        Product updated!
=======
                        <span>Keep product names, prices, and stock quantities up to date for your inventory.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active">Save product</button>
                        <button type="button" class="btn btn-pill">Cancel</button>
>>>>>>> 5285334 (Update frontend UI)
                    </div>
                </form>
            </div>
        </section>
    </div>

</div>

<script>
    // active row
    var activeRow = document.querySelector('#table tbody tr');

    // edit populate
    function bindEditButtons() {
        document.querySelectorAll('#table .edit-btn').forEach(function (btn) {
            btn.onclick = function () {
                activeRow = btn.closest('tr');
                var cells = activeRow.querySelectorAll('td');
                document.getElementById('productName').value = cells[0].textContent.trim();
                var cat = cells[1].textContent.trim();
                var selCat = document.getElementById('category');
                for (var i = 0; i < selCat.options.length; i++) {
                    if (selCat.options[i].text === cat) { selCat.selectedIndex = i; break; }
                }
                document.getElementById('price').value = cells[2].textContent.replace('₱', '').trim();
                document.getElementById('stock').value = cells[3].textContent.trim();
                var badgeText = cells[4].textContent.toLowerCase();
                var selStatus = document.getElementById('status');
                selStatus.value = badgeText.indexOf('low') !== -1 ? 'low' : 'available';
            };
        });
    }
    bindEditButtons();

    // add product
    document.getElementById('addProductBtn').addEventListener('click', function () {
        activeRow = null;
        document.getElementById('productName').value = '';
        document.getElementById('price').value = '0.00';
        document.getElementById('stock').value = '0';
        document.getElementById('productName').focus();
    });

    // cancel edit
    document.getElementById('cancelProductBtn').addEventListener('click', function () {
        if (activeRow) {
            var firstBtn = activeRow.querySelector('.edit-btn');
            if (firstBtn) firstBtn.click();
        } else {
            document.getElementById('productName').value = 'Ballpen black';
            document.getElementById('price').value = '8.00';
            document.getElementById('stock').value = '14';
        }
    });

    // save changes
    document.getElementById('saveProductBtn').addEventListener('click', function () {
        var name = document.getElementById('productName').value.trim();
        var cat = document.getElementById('category').value;
        var price = parseFloat(document.getElementById('price').value) || 0;
        var stock = parseInt(document.getElementById('stock').value) || 0;
        var status = document.getElementById('status').value;

        if (!name) return;

        var badgeClass = (status === 'low' || stock <= 10) ? 'status-low' : 'status-available';
        var badgeLabel = (status === 'low' || stock <= 10) ? 'Low stock' : 'Available';
        var rowStatus = (status === 'low' || stock <= 10) ? 'low' : 'available';

        if (activeRow) {
            // update existing
            var cells = activeRow.querySelectorAll('td');
            cells[0].textContent = name;
            cells[1].textContent = cat;
            cells[2].textContent = '₱' + price;
            cells[3].textContent = stock;
            cells[4].innerHTML = '<span class="status-badge ' + badgeClass + '">' + badgeLabel + '</span>';
            activeRow.dataset.status = rowStatus;
        } else {
            // append new
            var tbody = document.querySelector('#table tbody');
            var tr = document.createElement('tr');
            tr.dataset.status = rowStatus;
            tr.innerHTML =
                '<td class="fw-semibold">' + name + '</td>' +
                '<td>' + cat + '</td>' +
                '<td>₱' + price + '</td>' +
                '<td>' + stock + '</td>' +
                '<td><span class="status-badge ' + badgeClass + '">' + badgeLabel + '</span></td>' +
                '<td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>';
            tbody.appendChild(tr);
            activeRow = tr;
            bindEditButtons();
        }

        // feedback msg
        var msg = document.getElementById('productMsg');
        msg.classList.remove('d-none');
        setTimeout(function () { msg.classList.add('d-none'); }, 2000);
    });

    // filter pills
    document.querySelectorAll('#pills .btn-pill').forEach(function (p) {
        p.addEventListener('click', function () {
            document.querySelectorAll('#pills .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            p.classList.add('active');
            var f = p.dataset.filter;
            document.querySelectorAll('#table tbody tr').forEach(function (r) {
                if (f === 'all') {
                    r.style.display = '';
                } else {
                    r.style.display = (r.dataset.status === f) ? '' : 'none';
                }
            });
        });
    });

    // simple search
    document.getElementById('search').addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#table tbody tr').forEach(function (r) {
            r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
