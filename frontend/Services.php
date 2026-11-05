<?php

$pageTitle    = 'Services';
$pageSubtitle = 'Manage printing and other services, their prices and availability';
$activePage   = 'products';

include 'includes/header.php';
?>

<!-- action button -->
<div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-pill active" id="addServiceBtn">
        <i class="bi bi-plus"></i> Add service
    </button>
</div>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Services revenue - October</div>
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
            <div class="stat-label">Top service</div>
            <div class="stat-value">Photocopy</div>
            <div class="stat-note">₱31,400 in October</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- services list -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">

                <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                    <!-- page tabs -->
                    <div class="d-flex gap-2" id="tabs">
                        <a href="Products.php" class="btn btn-pill">Products</a>
                        <button type="button" class="btn btn-pill active">Services</button>
                    </div>
                    <div class="input-group ms-md-auto" style="max-width:280px;">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="search" class="form-control ui-input border-start-0"
                            placeholder="Search services">
                    </div>
                </div>

                <!-- status pills -->
                <div class="d-flex flex-wrap gap-2 mb-3" id="pills">
                    <button type="button" class="btn btn-pill active" data-filter="all">All - 6</button>
                    <button type="button" class="btn btn-pill" data-filter="available">Available - 5</button>
                    <button type="button" class="btn btn-pill" data-filter="unavailable">Unavailable - 1</button>
                </div>

                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0" id="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Charged per</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr data-status="available">
                                <td class="fw-semibold">Colored Printing</td>
                                <td>Page</td>
                                <td>₱15</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">Photocopy</td>
                                <td>Page</td>
                                <td>₱2</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">Black &amp; White Printing</td>
                                <td>Page</td>
                                <td>₱5</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">Lamination</td>
                                <td>Sheet</td>
                                <td>₱20</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="available">
                                <td class="fw-semibold">Spiral Binding</td>
                                <td>Copy</td>
                                <td>₱35</td>
                                <td><span class="status-badge status-available">Available</span></td>
                                <td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>
                            </tr>
                            <tr data-status="unavailable">
                                <td class="fw-semibold">ID Picture Print</td>
                                <td>Sheet</td>
                                <td>₱30</td>
                                <td><span class="status-badge status-low">Unavailable</span></td>
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
                <form id="serviceForm" onsubmit="return false">
                    <div class="mb-3">
                        <label for="serviceName" class="field-label">Service name</label>
                        <input type="text" id="serviceName" class="form-control ui-input" value="Colored Printing" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label for="price" class="field-label">Price (₱)</label>
                            <input type="number" id="price" class="form-control ui-input" value="15.00" step="0.01" min="0" required>
                        </div>
                        <div class="col-7">
                            <label for="chargedPer" class="field-label">Charged per</label>
                            <select id="chargedPer" class="form-select ui-input">
                                <option selected>Page</option>
                                <option>Copy</option>
                                <option>Sheet</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="field-label">Status</label>
                        <select id="status" class="form-select ui-input">
                            <option value="available" selected>Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="field-label">Description</label>
                        <textarea id="description" class="form-control ui-input" rows="3"
                            placeholder="Full-color print on short or A4 paper."></textarea>
                    </div>

                    <div class="info-box mb-3">
                        <i class="bi bi-info-square-fill"></i>
                        <span>Services don't use stock. Quantity and unit price are saved with every sale, so past sales keep their original price when you change it here.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active" id="saveServiceBtn">Save changes</button>
                        <button type="button" class="btn btn-pill" id="cancelServiceBtn">Cancel</button>
                    </div>

                    <div id="serviceMsg" class="text-success text-center mt-2 d-none" style="font-size:0.8rem;">
                        Service updated!
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
                document.getElementById('serviceName').value = cells[0].textContent.trim();
                document.getElementById('price').value = cells[2].textContent.replace('₱', '').trim();
                var per = cells[1].textContent.trim();
                var sel = document.getElementById('chargedPer');
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].text === per) { sel.selectedIndex = i; break; }
                }
                var badgeText = cells[3].textContent.toLowerCase();
                document.getElementById('status').value = badgeText.indexOf('un') !== -1 ? 'unavailable' : 'available';
            };
        });
    }
    bindEditButtons();

    // add service
    document.getElementById('addServiceBtn').addEventListener('click', function () {
        activeRow = null;
        document.getElementById('serviceName').value = '';
        document.getElementById('price').value = '0.00';
        document.getElementById('description').value = '';
        document.getElementById('serviceName').focus();
    });

    // cancel edit
    document.getElementById('cancelServiceBtn').addEventListener('click', function () {
        if (activeRow) {
            var firstBtn = activeRow.querySelector('.edit-btn');
            if (firstBtn) firstBtn.click();
        } else {
            document.getElementById('serviceName').value = 'Colored Printing';
            document.getElementById('price').value = '15.00';
        }
    });

    // save changes
    document.getElementById('saveServiceBtn').addEventListener('click', function () {
        var name = document.getElementById('serviceName').value.trim();
        var per = document.getElementById('chargedPer').value;
        var price = parseFloat(document.getElementById('price').value) || 0;
        var status = document.getElementById('status').value;

        if (!name) return;

        var isAvail = status === 'available';
        var badgeClass = isAvail ? 'status-available' : 'status-low';
        var badgeLabel = isAvail ? 'Available' : 'Unavailable';
        var rowStatus = isAvail ? 'available' : 'unavailable';

        if (activeRow) {
            var cells = activeRow.querySelectorAll('td');
            cells[0].textContent = name;
            cells[1].textContent = per;
            cells[2].textContent = '₱' + price;
            cells[3].innerHTML = '<span class="status-badge ' + badgeClass + '">' + badgeLabel + '</span>';
            activeRow.dataset.status = rowStatus;
        } else {
            var tbody = document.querySelector('#table tbody');
            var tr = document.createElement('tr');
            tr.dataset.status = rowStatus;
            tr.innerHTML =
                '<td class="fw-semibold">' + name + '</td>' +
                '<td>' + per + '</td>' +
                '<td>₱' + price + '</td>' +
                '<td><span class="status-badge ' + badgeClass + '">' + badgeLabel + '</span></td>' +
                '<td class="text-end"><button type="button" class="edit-btn" aria-label="Edit"><i class="bi bi-pencil-square"></i></button></td>';
            tbody.appendChild(tr);
            activeRow = tr;
            bindEditButtons();
        }

        var msg = document.getElementById('serviceMsg');
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

    // search filter
    document.getElementById('search').addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#table tbody tr').forEach(function (r) {
            r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
