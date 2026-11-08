<?php

$pageTitle    = 'Products & Services';
$pageSubtitle = 'Manage products, stock quantity, prices and availability';
$activePage   = 'products';

include 'includes/header.php';
?>

<!-- stat cards -->
<section class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Products revenue</div>
            <div class="stat-value" id="prodRevenue">₱0</div>
            <div class="stat-note" id="prodRevenueNote">All recorded sales</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Active products</div>
            <div class="stat-value" id="prodActive">0</div>
            <div class="stat-note" id="prodActiveNote">0 low stock</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="ui-card stat">
            <div class="stat-label">Top product</div>
            <div class="stat-value" id="prodTop">—</div>
            <div class="stat-note" id="prodTopNote">No sales yet</div>
        </div>
    </div>
</section>

<div class="row g-3">

    <!-- products list -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">

                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <!-- page tabs -->
                    <div class="d-flex gap-2" id="tabs">
                        <button type="button" class="btn btn-pill active">Products</button>
                        <a href="Services.php" class="btn btn-pill">Services</a>
                    </div>
                    <div class="input-group ms-md-auto" style="max-width:260px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                        <input type="search" id="search" name="search" class="form-control ui-input border-start-0"
                            placeholder="Search by name or category…" aria-label="Search by name or category" autocomplete="off">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <!-- status pills -->
                    <div class="d-flex flex-wrap gap-2" id="pills">
                        <button type="button" class="btn btn-pill active" data-filter="all">All</button>
                        <button type="button" class="btn btn-pill" data-filter="available">Available</button>
                        <button type="button" class="btn btn-pill" data-filter="low">Low stock</button>
                    </div>
                    <div class="ms-md-auto">
                        <label for="categoryFilter" class="visually-hidden">Filter by category</label>
                        <select id="categoryFilter" name="categoryFilter" class="form-select ui-input" style="width:200px; max-width:100%;">
                            <option value="all">All categories</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0" id="table">
                        <caption class="visually-hidden">List of products with price, stock, and status</caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Price</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody id="productBody"></tbody>
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
                        <label for="productName" class="field-label">Product name</label>
                        <input type="text" id="productName" name="productName" autocomplete="off" class="form-control ui-input" placeholder="e.g. Ballpen black" maxlength="255" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="field-label">Category</label>
                        <input type="text" id="category" name="category" autocomplete="off" class="form-control ui-input" list="categoryOptions"
                            value="General" maxlength="100" placeholder="e.g. Writing">
                        <datalist id="categoryOptions"></datalist>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="price" class="field-label">Price (₱)</label>
                            <input type="number" id="price" name="price" inputmode="decimal" class="form-control ui-input" value="0.00" step="0.01" min="0" required>
                        </div>
                        <div class="col-6">
                            <label for="stock" class="field-label">Stock qty</label>
                            <input type="number" id="stock" name="stock" inputmode="numeric" class="form-control ui-input" value="0" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="field-label">Status</label>
                        <select id="status" name="status" class="form-select ui-input">
                            <option value="active" selected>Available</option>
                            <option value="inactive">Unavailable</option>
                        </select>
                    </div>

                    <div class="info-box mb-3">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <span>Physical products track live inventory. Stock is deducted automatically upon every sale recorded.</span>
                    </div>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-pill active" id="saveProductBtn">Add product</button>
                        <button type="button" class="btn btn-pill" id="cancelProductBtn">Cancel</button>
                        <button type="button" class="btn btn-pill d-none" id="deleteProductBtn">Delete</button>
                    </div>

                    <div id="productMsg" role="status" aria-live="polite" class="text-center mt-2 d-none" style="font-size:0.8rem;"></div>
                </form>
            </div>
        </section>
    </div>

</div>

<script>
(function () {
    var products = [];
    var activeId = null;
    var role = window.APP_USER ? window.APP_USER.role : '';
    var isAdmin = role === 'owner' || role === 'admin';
    var canCreate = role === 'owner' || role === 'admin' || role === 'staff';
    var msgTimer = null;

    var els = {
        body: document.getElementById('productBody'),
        save: document.getElementById('saveProductBtn'),
        cancel: document.getElementById('cancelProductBtn'),
        remove: document.getElementById('deleteProductBtn'),
        name: document.getElementById('productName'),
        category: document.getElementById('category'),
        price: document.getElementById('price'),
        stock: document.getElementById('stock'),
        status: document.getElementById('status'),
        search: document.getElementById('search'),
        catFilter: document.getElementById('categoryFilter'),
        msg: document.getElementById('productMsg'),
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

    function statusMeta(product) {
        if (product.status === 'inactive') {
            return { key: 'inactive', label: 'Unavailable', badge: 'status-low' };
        }
        if (Number(product.stock) <= 10) {
            return { key: 'low', label: 'Low stock', badge: 'status-low' };
        }
        return { key: 'available', label: 'Available', badge: 'status-available' };
    }

    function getCategories() {
        var seen = {};
        var out = [];
        products.map(function (p) { return (p.category || 'General').trim(); }).forEach(function (cat) {
            var key = cat.toLowerCase();
            if (!cat || seen[key]) return;
            seen[key] = true;
            out.push(cat);
        });
        return out.sort(function (a, b) { return a.localeCompare(b); });
    }

    function fillCategories() {
        var list = document.getElementById('categoryOptions');
        var seen = {};
        var defaults = ['Writing', 'Paper', 'Art supplies', 'Office', 'General'];
        list.innerHTML = '';
        defaults.concat(getCategories()).forEach(function (cat) {
            if (!cat || seen[cat]) return;
            seen[cat] = true;
            var option = document.createElement('option');
            option.value = cat;
            list.appendChild(option);
        });
    }

    function fillCategoryFilter() {
        var current = els.catFilter.value || 'all';
        var cats = getCategories();
        els.catFilter.innerHTML = '<option value="all">All categories</option>';
        cats.forEach(function (cat) {
            var option = document.createElement('option');
            option.value = cat;
            option.textContent = cat;
            els.catFilter.appendChild(option);
        });
        els.catFilter.value = cats.indexOf(current) !== -1 ? current : 'all';
    }

    function renderRows() {
        els.body.innerHTML = '';
        if (!products.length) {
            els.body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No products yet. Add your first product.</td></tr>';
            return;
        }
        products.forEach(function (p) {
            var meta = statusMeta(p);
            var tr = document.createElement('tr');
            tr.dataset.id = p.id;
            tr.dataset.status = meta.key;
            tr.dataset.category = (p.category || 'General').trim();
            tr.innerHTML =
                '<td class="fw-semibold">' + escapeHtml(p.name) + '</td>' +
                '<td>' + escapeHtml(p.category) + '</td>' +
                '<td>' + peso(p.price) + '</td>' +
                '<td>' + Number(p.stock) + '</td>' +
                '<td><span class="status-badge ' + meta.badge + '">' + meta.label + '</span></td>' +
                '<td class="text-end">' + (isAdmin ? '<button type="button" class="edit-btn" aria-label="Edit"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>' : '') + '</td>';
            els.body.appendChild(tr);
        });
    }

    function updateStats(data) {
        document.getElementById('prodRevenue').textContent = peso(data.revenue || 0);
        document.getElementById('prodRevenueNote').textContent = 'All recorded sales';
        document.getElementById('prodActive').textContent = data.counts.available;
        document.getElementById('prodActiveNote').textContent = data.counts.low_stock + ' low stock';
        document.getElementById('prodTop').textContent = data.top_product ? data.top_product.name : '—';
        document.getElementById('prodTopNote').textContent = data.top_product ? peso(data.top_product.revenue) + ' revenue' : 'No sales yet';
    }

    function applyFilters() {
        var activePill = els.pills.querySelector('.btn-pill.active');
        var filter = activePill ? activePill.dataset.filter : 'all';
        var query = els.search.value.toLowerCase().trim();
        var catFilter = (els.catFilter.value || 'all').toLowerCase();
        var hasCat = catFilter !== 'all';

        els.body.querySelectorAll('tr[data-status]').forEach(function (row) {
            var visible = (filter === 'all' || row.dataset.status === filter);
            if (visible && hasCat) {
                visible = (row.dataset.category || '').toLowerCase() === catFilter;
            }
            if (visible && query) {
                visible = (row.dataset.category + ' ' + row.textContent).toLowerCase().indexOf(query) !== -1;
            }
            row.style.display = visible ? '' : 'none';
        });
    }

    function loadProducts() {
        Api.get('products.php').then(function (data) {
            products = data.products || [];
            fillCategories();
            fillCategoryFilter();
            renderRows();
            updateStats(data);
            applyFilters();
        }).catch(function (err) {
            showMessage(err.message, true);
        });
    }

    function resetForm() {
        activeId = null;
        els.name.value = '';
        els.category.value = 'General';
        els.price.value = '0.00';
        els.stock.value = '0';
        els.status.value = 'active';
        els.remove.classList.add('d-none');
        els.save.textContent = 'Add product';
        els.body.querySelectorAll('tr.table-active').forEach(function (row) { row.classList.remove('table-active'); });
    }

    function fillForm(product, row) {
        activeId = Number(product.id);
        els.name.value = product.name;
        els.category.value = product.category;
        els.price.value = product.price;
        els.stock.value = product.stock;
        els.status.value = product.status === 'inactive' ? 'inactive' : 'active';
        els.remove.classList.toggle('d-none', !isAdmin);
        els.save.textContent = 'Save changes';
        els.body.querySelectorAll('tr.table-active').forEach(function (tr) { tr.classList.remove('table-active'); });
        if (row) row.classList.add('table-active');
        els.name.focus();
    }

    if (!canCreate) {
        els.save.classList.add('d-none');
        els.cancel.classList.add('d-none');
        [els.name, els.category, els.price, els.stock, els.status].forEach(function (el) { el.disabled = true; });
    }

    els.body.addEventListener('click', function (event) {
        var button = event.target.closest('.edit-btn');
        if (!button || !isAdmin) return;
        var row = button.closest('tr');
        var product = products.filter(function (p) { return Number(p.id) === Number(row.dataset.id); })[0];
        if (product) fillForm(product, row);
    });

    els.cancel.addEventListener('click', function () {
        resetForm();
    });

    els.save.addEventListener('click', function () {
        if (!canCreate) return;

        var payload = {
            name: els.name.value.trim(),
            category: els.category.value.trim() || 'General',
            price: parseFloat(els.price.value),
            stock: parseInt(els.stock.value, 10),
            status: els.status.value === 'inactive' ? 'inactive' : 'active'
        };

        if (!payload.name) {
            showMessage('Product name is required.', true);
            return;
        }
        if (!isFinite(payload.price) || payload.price < 0) {
            showMessage('Enter a valid price.', true);
            return;
        }
        if (!isFinite(payload.stock) || payload.stock < 0) {
            showMessage('Enter a valid stock quantity.', true);
            return;
        }

        var editing = activeId !== null;
        if (editing) {
            if (!isAdmin) {
                showMessage('Only owners and admins can edit existing products.', true);
                return;
            }
            payload.action = 'update';
            payload.id = activeId;
        }

        els.save.disabled = true;
        Api.post('products.php', payload).then(function () {
            showMessage(editing ? 'Product updated!' : 'Product added!');
            resetForm();
            loadProducts();
        }).catch(function (err) {
            showMessage(err.message, true);
        }).then(function () {
            els.save.disabled = false;
        });
    });

    els.remove.addEventListener('click', function () {
        if (!isAdmin || !activeId) return;
        if (!window.confirm('Delete this product? This cannot be undone.')) return;

        Api.post('products.php', { action: 'delete', id: activeId }).then(function () {
            showMessage('Product deleted.');
            resetForm();
            loadProducts();
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
    els.catFilter.addEventListener('change', applyFilters);

    loadProducts();
})();
</script>

<?php include 'includes/footer.php'; ?>
