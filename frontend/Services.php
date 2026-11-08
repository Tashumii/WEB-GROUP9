<?php

$pageTitle    = 'Services';
$pageSubtitle = 'Manage printing and other services, their prices and availability';
$activePage   = 'products';

include 'includes/header.php';
?>

<!-- action button -->
<div class="d-flex justify-content-end gap-2 mb-3">
    <button type="button" class="btn btn-pill active" id="addServiceBtn">
        <i class="fa-solid fa-plus"></i> Add service
    </button>
</div>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Services revenue</div>
            <div class="stat-value" id="svcRevenue">₱0</div>
            <div class="stat-note" id="svcRevenueNote">All recorded sales</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Active services</div>
            <div class="stat-value" id="svcActive">0</div>
            <div class="stat-note" id="svcActiveNote">0 unavailable</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Top service</div>
            <div class="stat-value" id="svcTop">—</div>
            <div class="stat-note" id="svcTopNote">No sales yet</div>
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
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
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
                        <tbody id="serviceBody"></tbody>
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
                        <input type="text" id="serviceName" class="form-control ui-input" placeholder="e.g. Colored Printing" maxlength="255" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label for="price" class="field-label">Price (₱)</label>
                            <input type="number" id="price" class="form-control ui-input" value="0.00" step="0.01" min="0" required>
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
                            <option value="active" selected>Available</option>
                            <option value="inactive">Unavailable</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="field-label">Description</label>
                        <textarea id="description" class="form-control ui-input" rows="3"
                            placeholder="Full-color print on short or A4 paper."></textarea>
                    </div>

                    <div class="info-box mb-3">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Services don't use stock. Quantity and unit price are saved with every sale, so past sales keep their original price when you change it here.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active" id="saveServiceBtn">Add service</button>
                        <button type="button" class="btn btn-pill" id="cancelServiceBtn">Cancel</button>
                        <button type="button" class="btn btn-pill d-none" id="deleteServiceBtn">Delete</button>
                    </div>

                    <div id="serviceMsg" class="text-center mt-2 d-none" style="font-size:0.8rem;"></div>
                </form>
            </div>
        </section>
    </div>

</div>

<script>
(function () {
    var services = [];
    var activeId = null;
    var role = window.APP_USER ? window.APP_USER.role : '';
    var isAdmin = role === 'owner' || role === 'admin';
    var canCreate = role === 'owner' || role === 'admin' || role === 'staff';
    var msgTimer = null;

    var els = {
        body: document.getElementById('serviceBody'),
        add: document.getElementById('addServiceBtn'),
        save: document.getElementById('saveServiceBtn'),
        cancel: document.getElementById('cancelServiceBtn'),
        remove: document.getElementById('deleteServiceBtn'),
        name: document.getElementById('serviceName'),
        price: document.getElementById('price'),
        chargedPer: document.getElementById('chargedPer'),
        description: document.getElementById('description'),
        status: document.getElementById('status'),
        search: document.getElementById('search'),
        msg: document.getElementById('serviceMsg'),
        pills: document.getElementById('pills')
    };

    function showMessage(text, isError) {
        els.msg.textContent = text;
        els.msg.classList.toggle('text-danger', !!isError);
        els.msg.classList.toggle('text-success', !isError);
        els.msg.classList.remove('d-none');
        clearTimeout(msgTimer);
        msgTimer = setTimeout(function () { els.msg.classList.add('d-none'); }, 3000);
    }

    function renderRows() {
        els.body.innerHTML = '';
        if (!services.length) {
            els.body.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No services yet. Add your first service.</td></tr>';
            return;
        }
        services.forEach(function (s) {
            var active = s.status === 'active';
            var tr = document.createElement('tr');
            tr.dataset.id = s.id;
            tr.dataset.status = active ? 'available' : 'unavailable';
            tr.innerHTML =
                '<td class="fw-semibold">' + escapeHtml(s.name) + '</td>' +
                '<td>' + escapeHtml(s.charged_per) + '</td>' +
                '<td>' + peso(s.price) + '</td>' +
                '<td><span class="status-badge ' + (active ? 'status-available' : 'status-low') + '">' + (active ? 'Available' : 'Unavailable') + '</span></td>' +
                '<td class="text-end">' + (isAdmin ? '<button type="button" class="edit-btn" aria-label="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' : '') + '</td>';
            els.body.appendChild(tr);
        });
    }

    function updateStats(data) {
        document.getElementById('svcRevenue').textContent = peso(data.revenue || 0);
        document.getElementById('svcRevenueNote').textContent = 'All recorded sales';
        document.getElementById('svcActive').textContent = data.counts.available;
        document.getElementById('svcActiveNote').textContent = data.counts.unavailable + ' unavailable';
        document.getElementById('svcTop').textContent = data.top_service ? data.top_service.name : '—';
        document.getElementById('svcTopNote').textContent = data.top_service ? peso(data.top_service.revenue) + ' revenue' : 'No sales yet';
    }

    function updatePillCounts(counts) {
        var pills = els.pills.querySelectorAll('.btn-pill');
        if (pills[0]) pills[0].textContent = 'All - ' + counts.total;
        if (pills[1]) pills[1].textContent = 'Available - ' + counts.available;
        if (pills[2]) pills[2].textContent = 'Unavailable - ' + counts.unavailable;
    }

    function applyFilters() {
        var activePill = els.pills.querySelector('.btn-pill.active');
        var filter = activePill ? activePill.dataset.filter : 'all';
        var query = els.search.value.toLowerCase().trim();

        els.body.querySelectorAll('tr[data-status]').forEach(function (row) {
            var visible = (filter === 'all' || row.dataset.status === filter);
            if (visible && query) {
                visible = row.textContent.toLowerCase().indexOf(query) !== -1;
            }
            row.style.display = visible ? '' : 'none';
        });
    }

    function loadServices() {
        Api.get('services.php').then(function (data) {
            services = data.services || [];
            renderRows();
            updateStats(data);
            updatePillCounts(data.counts);
            applyFilters();
        }).catch(function (err) {
            showMessage(err.message, true);
        });
    }

    function resetForm() {
        activeId = null;
        els.name.value = '';
        els.price.value = '0.00';
        els.chargedPer.value = 'Page';
        els.description.value = '';
        els.status.value = 'active';
        els.remove.classList.add('d-none');
        els.save.textContent = 'Add service';
        els.body.querySelectorAll('tr.table-active').forEach(function (row) { row.classList.remove('table-active'); });
    }

    function fillForm(service, row) {
        activeId = Number(service.id);
        els.name.value = service.name;
        els.price.value = service.price;
        if (service.charged_per && els.chargedPer.value !== service.charged_per) {
            var existing = Array.prototype.some.call(els.chargedPer.options, function (option) {
                return option.value === service.charged_per;
            });
            if (!existing) {
                var option = document.createElement('option');
                option.value = service.charged_per;
                option.textContent = service.charged_per;
                els.chargedPer.appendChild(option);
            }
            els.chargedPer.value = service.charged_per;
        }
        els.description.value = service.description || '';
        els.status.value = service.status === 'inactive' ? 'inactive' : 'active';
        els.remove.classList.toggle('d-none', !isAdmin);
        els.save.textContent = 'Save changes';
        els.body.querySelectorAll('tr.table-active').forEach(function (tr) { tr.classList.remove('table-active'); });
        if (row) row.classList.add('table-active');
        els.name.focus();
    }

    if (!canCreate) {
        els.add.classList.add('d-none');
        els.save.classList.add('d-none');
        els.cancel.classList.add('d-none');
        [els.name, els.price, els.chargedPer, els.description, els.status].forEach(function (el) { el.disabled = true; });
    }

    els.body.addEventListener('click', function (event) {
        var button = event.target.closest('.edit-btn');
        if (!button || !isAdmin) return;
        var row = button.closest('tr');
        var service = services.filter(function (s) { return Number(s.id) === Number(row.dataset.id); })[0];
        if (service) fillForm(service, row);
    });

    els.add.addEventListener('click', function () {
        resetForm();
        els.name.focus();
    });

    els.cancel.addEventListener('click', function () {
        resetForm();
    });

    els.save.addEventListener('click', function () {
        if (!canCreate) return;

        var payload = {
            name: els.name.value.trim(),
            charged_per: els.chargedPer.value,
            price: parseFloat(els.price.value),
            description: els.description.value.trim(),
            status: els.status.value === 'inactive' ? 'inactive' : 'active'
        };

        if (!payload.name) {
            showMessage('Service name is required.', true);
            return;
        }
        if (!isFinite(payload.price) || payload.price < 0) {
            showMessage('Enter a valid price.', true);
            return;
        }

        var editing = activeId !== null;
        if (editing) {
            if (!isAdmin) {
                showMessage('Only owners and admins can edit existing services.', true);
                return;
            }
            payload.action = 'update';
            payload.id = activeId;
        }

        els.save.disabled = true;
        Api.post('services.php', payload).then(function () {
            showMessage(editing ? 'Service updated!' : 'Service added!');
            resetForm();
            loadServices();
        }).catch(function (err) {
            showMessage(err.message, true);
        }).then(function () {
            els.save.disabled = false;
        });
    });

    els.remove.addEventListener('click', function () {
        if (!isAdmin || !activeId) return;
        if (!window.confirm('Delete this service? This cannot be undone.')) return;

        Api.post('services.php', { action: 'delete', id: activeId }).then(function () {
            showMessage('Service deleted.');
            resetForm();
            loadServices();
        }).catch(function (err) {
            showMessage(err.message, true);
        });
    });

    els.pills.addEventListener('click', function (event) {
        var pill = event.target.closest('.btn-pill');
        if (!pill) return;
        els.pills.querySelectorAll('.btn-pill').forEach(function (p) { p.classList.remove('active'); });
        pill.classList.add('active');
        applyFilters();
    });

    els.search.addEventListener('input', applyFilters);

    loadServices();
})();
</script>

<?php include 'includes/footer.php'; ?>
