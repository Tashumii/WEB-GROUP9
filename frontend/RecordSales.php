<?php

$pageTitle    = 'Record Sale';
$pageSubtitle = 'Add items and save a new transaction';
$activePage   = 'record';

include 'includes/header.php';

$canAddCatalogItem = in_array($currentRoleKey ?? '', ['owner', 'admin', 'staff'], true);
?>

<div class="row g-3">

    <!-- item picker -->
    <div class="col-xl-8">
        <section class="ui-card h-100">
            <div class="ui-card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <!-- type tabs -->
                    <div class="d-flex gap-2" id="typeTabs">
                        <button type="button" class="btn btn-pill active" data-type="all">All</button>
                        <button type="button" class="btn btn-pill" data-type="Product">Products</button>
                        <button type="button" class="btn btn-pill" data-type="Service">Services</button>
                    </div>
                    <div class="input-group ms-md-auto" style="max-width:260px;">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" id="itemSearch" class="form-control ui-input border-start-0"
                            placeholder="Search item">
                    </div>
                    <?php if ($canAddCatalogItem): ?>
                    <button type="button" class="btn btn-pill" data-bs-toggle="modal" data-bs-target="#quickAddModal">
                        <i class="fa-solid fa-plus"></i> Add item
                    </button>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table sales-table align-middle mb-0" id="itemTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th class="text-end">Unit price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemBody"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- order summary -->
    <div class="col-xl-4">
        <section class="ui-card h-100">
            <div class="ui-card-body d-flex flex-column" style="min-height:360px;">
                <strong class="d-block mb-3">Order summary</strong>

                <div id="cartEmpty" class="text-muted text-center py-4" style="font-size:.82rem;">
                    No items added yet.
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table sales-table align-middle mb-0" id="cartTable" style="display:none;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartBody"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                    <strong>Total</strong>
                    <strong id="grandTotal" style="font-size:1.1rem;">₱0</strong>
                </div>

                <div class="mt-3 d-grid gap-2">
                    <button type="button" class="btn btn-pill active" id="saveSaleBtn" disabled>Save sale</button>
                    <button type="button" class="btn btn-pill" id="clearCartBtn">Clear</button>
                </div>

                <div id="savedMsg" class="text-center mt-2 d-none" style="font-size:.8rem;"></div>
            </div>
        </section>
    </div>

</div>

<?php if ($canAddCatalogItem): ?>
<div class="modal fade" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAddForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAddModalLabel">Add catalog item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="quickAddMsg" class="d-none mb-3" style="font-size:.8rem;"></div>

                    <div class="mb-3">
                        <label for="quickAddType" class="field-label">Type</label>
                        <select id="quickAddType" class="form-select ui-input">
                            <option value="product">Product</option>
                            <option value="service">Service</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="quickAddName" class="field-label">Name</label>
                        <input type="text" id="quickAddName" class="form-control ui-input" maxlength="255" placeholder="e.g. Ballpen black" required>
                    </div>

                    <div id="quickAddProductFields">
                        <div class="mb-3">
                            <label for="quickAddCategory" class="field-label">Category</label>
                            <input type="text" id="quickAddCategory" class="form-control ui-input" value="General" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="quickAddStock" class="field-label">Stock qty</label>
                            <input type="number" id="quickAddStock" class="form-control ui-input" value="0" min="0" step="1">
                        </div>
                    </div>

                    <div id="quickAddServiceFields" class="d-none">
                        <div class="mb-3">
                            <label for="quickAddChargedPer" class="field-label">Charged per</label>
                            <input type="text" id="quickAddChargedPer" class="form-control ui-input" value="Page" maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label for="quickAddDescription" class="field-label">Description</label>
                            <textarea id="quickAddDescription" class="form-control ui-input" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="quickAddPrice" class="field-label">Price (₱)</label>
                        <input type="number" id="quickAddPrice" class="form-control ui-input" value="0.00" min="0" step="0.01" required>
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" id="quickAddStatus" class="form-check-input" checked>
                        <label for="quickAddStatus" class="form-check-label" style="font-size:.82rem;">Available for sale</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pill active" id="quickAddSubmit">Add item</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var cart = {};
    var msgTimer = null;

    var els = {
        body: document.getElementById('itemBody'),
        search: document.getElementById('itemSearch'),
        cartBody: document.getElementById('cartBody'),
        cartTable: document.getElementById('cartTable'),
        cartEmpty: document.getElementById('cartEmpty'),
        grandTotal: document.getElementById('grandTotal'),
        saveBtn: document.getElementById('saveSaleBtn'),
        clearBtn: document.getElementById('clearCartBtn'),
        msg: document.getElementById('savedMsg'),
        typeTabs: document.getElementById('typeTabs')
    };

    function showMessage(text, isError) {
        els.msg.textContent = text;
        els.msg.classList.toggle('text-danger', !!isError);
        els.msg.classList.toggle('text-success', !isError);
        els.msg.classList.remove('d-none');
        clearTimeout(msgTimer);
        msgTimer = setTimeout(function () { els.msg.classList.add('d-none'); }, 3500);
    }

    function renderCatalog(products, services) {
        var items = [];
        products.forEach(function (p) {
            items.push({ id: p.id, name: p.name, type: 'Product', price: p.price, stock: p.stock });
        });
        services.forEach(function (s) {
            items.push({ id: s.id, name: s.name, type: 'Service', price: s.price, charged_per: s.charged_per });
        });

        els.body.innerHTML = '';
        if (!items.length) {
            els.body.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No active products or services yet.</td></tr>';
            return;
        }

        items.forEach(function (item) {
            var isProduct = item.type === 'Product';
            var outOfStock = isProduct && Number(item.stock) <= 0;
            var detail;
            if (isProduct) {
                detail = item.stock + ' in stock';
            } else {
                detail = 'per ' + (item.charged_per || 'page');
            }

            var tr = document.createElement('tr');
            tr.dataset.type = item.type;
            tr.dataset.id = item.id;
            tr.dataset.name = item.name;
            tr.dataset.price = item.price;
            tr.dataset.stock = isProduct ? item.stock : '';
            tr.innerHTML =
                '<td class="fw-semibold">' + escapeHtml(item.name) + '</td>' +
                '<td>' + item.type + ' <span class="text-muted" style="font-size:.75rem;">(' + escapeHtml(detail) + ')</span></td>' +
                '<td class="text-end">' + peso(item.price) + '</td>' +
                '<td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn"' + (outOfStock ? ' disabled' : '') + '>' + (outOfStock ? 'Out of stock' : '+ Add') + '</button></td>';
            els.body.appendChild(tr);
        });

        applyCatalogFilters();
    }

    function loadCatalog() {
        Api.get('recordsales.php').then(function (data) {
            renderCatalog(data.products || [], data.services || []);
        }).catch(function (err) {
            showMessage(err.message, true);
        });
    }

    function cartTotal() {
        return Object.keys(cart).reduce(function (sum, key) {
            return sum + (cart[key].price * cart[key].qty);
        }, 0);
    }

    function renderCart() {
        var keys = Object.keys(cart);
        els.cartBody.innerHTML = '';
        var total = cartTotal();

        keys.forEach(function (key) {
            var item = cart[key];
            var tr = document.createElement('tr');

            var nameCell = document.createElement('td');
            nameCell.className = 'fw-semibold';
            nameCell.style.fontSize = '.78rem';
            nameCell.textContent = item.name;
            tr.appendChild(nameCell);

            var qtyCell = document.createElement('td');
            qtyCell.className = 'text-end';
            var qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.className = 'form-control ui-input text-end p-1';
            qtyInput.style.width = '65px';
            qtyInput.style.fontSize = '.78rem';
            qtyInput.min = '1';
            if (item.stock !== null && item.stock !== undefined) {
                qtyInput.max = String(item.stock);
            }
            qtyInput.value = item.qty;
            qtyInput.dataset.key = key;
            qtyCell.appendChild(qtyInput);
            tr.appendChild(qtyCell);

            var subCell = document.createElement('td');
            subCell.className = 'text-end';
            subCell.style.fontSize = '.78rem';
            subCell.textContent = peso(item.price * item.qty);
            tr.appendChild(subCell);

            var actionCell = document.createElement('td');
            actionCell.className = 'text-end';
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'edit-btn remove-btn';
            removeBtn.dataset.key = key;
            removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            actionCell.appendChild(removeBtn);
            tr.appendChild(actionCell);

            els.cartBody.appendChild(tr);
        });

        els.grandTotal.textContent = peso(total);
        var hasItems = keys.length > 0;
        els.cartTable.style.display = hasItems ? '' : 'none';
        els.cartEmpty.style.display = hasItems ? 'none' : '';
        els.saveBtn.disabled = !hasItems;

        els.cartBody.querySelectorAll('input[type=number]').forEach(function (input) {
            input.addEventListener('change', function () {
                var key = input.dataset.key;
                var qty = Math.max(1, parseInt(input.value, 10) || 1);
                if (cart[key].stock !== null && cart[key].stock !== undefined) {
                    qty = Math.min(qty, cart[key].stock);
                }
                cart[key].qty = qty;
                renderCart();
            });
        });

        els.cartBody.querySelectorAll('.remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                delete cart[btn.dataset.key];
                renderCart();
            });
        });
    }

    function applyCatalogFilters() {
        var activeTab = els.typeTabs.querySelector('.btn-pill.active');
        var type = activeTab ? activeTab.dataset.type : 'all';
        var query = els.search.value.toLowerCase().trim();

        els.body.querySelectorAll('tr[data-type]').forEach(function (row) {
            var visible = (type === 'all' || row.dataset.type === type);
            if (visible && query) {
                visible = row.textContent.toLowerCase().indexOf(query) !== -1;
            }
            row.style.display = visible ? '' : 'none';
        });
    }

    els.body.addEventListener('click', function (event) {
        var button = event.target.closest('.add-item-btn');
        if (!button || button.disabled) return;

        var row = button.closest('tr');
        var type = row.dataset.type;
        var id = Number(row.dataset.id);
        var key = type + '::' + id;
        var stock = row.dataset.stock === '' ? null : Number(row.dataset.stock);

        if (cart[key]) {
            if (stock !== null && cart[key].qty >= stock) {
                showMessage('Only ' + stock + ' in stock for ' + row.dataset.name + '.', true);
                return;
            }
            cart[key].qty += 1;
        } else {
            cart[key] = {
                id: id,
                name: row.dataset.name,
                type: type,
                price: Number(row.dataset.price),
                qty: 1,
                stock: stock
            };
        }
        renderCart();
    });

    els.saveBtn.addEventListener('click', function () {
        var keys = Object.keys(cart);
        if (!keys.length) return;

        var items = keys.map(function (key) {
            return {
                item_type: cart[key].type.toLowerCase(),
                item_id: cart[key].id,
                quantity: cart[key].qty
            };
        });

        els.saveBtn.disabled = true;
        Api.post('recordsales.php', { items: items }).then(function (data) {
            cart = {};
            renderCart();
            showMessage('Sale saved! Total: ' + peso(data.total_amount));
            loadCatalog();
        }).catch(function (err) {
            showMessage(err.message, true);
            renderCart();
        });
    });

    els.clearBtn.addEventListener('click', function () {
        cart = {};
        renderCart();
    });

    els.typeTabs.addEventListener('click', function (event) {
        var tab = event.target.closest('.btn-pill');
        if (!tab) return;
        els.typeTabs.querySelectorAll('.btn-pill').forEach(function (b) { b.classList.remove('active'); });
        tab.classList.add('active');
        applyCatalogFilters();
    });

    els.search.addEventListener('input', applyCatalogFilters);

    var quickAddForm = document.getElementById('quickAddForm');
    if (quickAddForm) {
        var quickType = document.getElementById('quickAddType');
        var quickMsg = document.getElementById('quickAddMsg');
        var quickProductFields = document.getElementById('quickAddProductFields');
        var quickServiceFields = document.getElementById('quickAddServiceFields');
        var quickName = document.getElementById('quickAddName');
        var quickCategory = document.getElementById('quickAddCategory');
        var quickStock = document.getElementById('quickAddStock');
        var quickChargedPer = document.getElementById('quickAddChargedPer');
        var quickDescription = document.getElementById('quickAddDescription');
        var quickPrice = document.getElementById('quickAddPrice');
        var quickStatus = document.getElementById('quickAddStatus');
        var quickSubmit = document.getElementById('quickAddSubmit');

        function showQuickAddMessage(text, isError) {
            quickMsg.textContent = text;
            quickMsg.classList.remove('d-none');
            quickMsg.classList.toggle('text-danger', !!isError);
            quickMsg.classList.toggle('text-success', !isError);
        }

        function syncQuickAddFields() {
            var isProduct = quickType.value === 'product';
            quickProductFields.classList.toggle('d-none', !isProduct);
            quickServiceFields.classList.toggle('d-none', isProduct);
            quickCategory.disabled = !isProduct;
            quickStock.disabled = !isProduct;
            quickChargedPer.disabled = isProduct;
            quickDescription.disabled = isProduct;
        }

        function resetQuickAddForm() {
            quickAddForm.reset();
            quickCategory.value = 'General';
            quickStock.value = '0';
            quickChargedPer.value = 'Page';
            quickPrice.value = '0.00';
            quickStatus.checked = true;
            quickMsg.classList.add('d-none');
            syncQuickAddFields();
        }

        quickType.addEventListener('change', syncQuickAddFields);
        syncQuickAddFields();

        quickAddForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var type = quickType.value === 'service' ? 'service' : 'product';
            var name = quickName.value.trim();
            var price = parseFloat(quickPrice.value);
            var status = quickStatus.checked ? 'active' : 'inactive';

            quickMsg.classList.add('d-none');

            if (!name) {
                showQuickAddMessage('Item name is required.', true);
                return;
            }
            if (!isFinite(price) || price < 0) {
                showQuickAddMessage('Enter a valid price.', true);
                return;
            }

            var payload;
            if (type === 'product') {
                var stock = parseInt(quickStock.value, 10);
                if (!isFinite(stock) || stock < 0) {
                    showQuickAddMessage('Enter a valid stock quantity.', true);
                    return;
                }
                payload = {
                    name: name,
                    category: quickCategory.value.trim() || 'General',
                    price: price,
                    stock: stock,
                    status: status
                };
            } else {
                payload = {
                    name: name,
                    charged_per: quickChargedPer.value.trim() || 'Page',
                    price: price,
                    description: quickDescription.value.trim(),
                    status: status
                };
            }

            quickSubmit.disabled = true;
            Api.post(type === 'product' ? 'products.php' : 'services.php', payload).then(function (data) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('quickAddModal')).hide();
                showMessage(data.message || 'Item added.', false);
                resetQuickAddForm();
                loadCatalog();
            }).catch(function (err) {
                showQuickAddMessage(err.message, true);
            }).then(function () {
                quickSubmit.disabled = false;
            });
        });
    }

    loadCatalog();
    renderCart();
})();
</script>

<?php include 'includes/footer.php'; ?>

