<?php

$pageTitle    = 'Record Sale';
$pageSubtitle = 'Add items and save a new transaction';
$activePage   = 'record';

include 'includes/header.php';
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
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="itemSearch" class="form-control ui-input border-start-0"
                            placeholder="Search item">
                    </div>
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
                        <tbody>
                            <tr data-type="Product" data-name="Ballpen black" data-price="8">
                                <td class="fw-semibold">Ballpen black</td>
                                <td>Product</td>
                                <td class="text-end">₱8</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Product" data-name="A4 paper" data-price="285">
                                <td class="fw-semibold">A4 paper</td>
                                <td>Product</td>
                                <td class="text-end">₱285</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Product" data-name="Crayons" data-price="35">
                                <td class="fw-semibold">Crayons</td>
                                <td>Product</td>
                                <td class="text-end">₱35</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Product" data-name="Pencil" data-price="16">
                                <td class="fw-semibold">Pencil</td>
                                <td>Product</td>
                                <td class="text-end">₱16</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Service" data-name="Colored Printing" data-price="15">
                                <td class="fw-semibold">Colored Printing</td>
                                <td>Service</td>
                                <td class="text-end">₱15 / page</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Service" data-name="Photocopy" data-price="2">
                                <td class="fw-semibold">Photocopy</td>
                                <td>Service</td>
                                <td class="text-end">₱2 / page</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Service" data-name="Black &amp; White Printing" data-price="5">
                                <td class="fw-semibold">Black &amp; White Printing</td>
                                <td>Service</td>
                                <td class="text-end">₱5 / page</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                            <tr data-type="Service" data-name="Lamination" data-price="20">
                                <td class="fw-semibold">Lamination</td>
                                <td>Service</td>
                                <td class="text-end">₱20 / sheet</td>
                                <td class="text-end"><button type="button" class="btn btn-pill btn-sm add-item-btn">+ Add</button></td>
                            </tr>
                        </tbody>
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
                                <th class="text-end">Price</th>
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

                <div id="savedMsg" class="text-center text-success mt-2 d-none" style="font-size:.8rem;">
                    Sale saved!
                </div>
            </div>
        </section>
    </div>

</div>

<script>
    // cart state
    var cart = {};

    function peso(n) { return '₱' + n.toLocaleString(); }

    // render cart
    function renderCart() {
        var keys = Object.keys(cart);
        var body = document.getElementById('cartBody');
        var table = document.getElementById('cartTable');
        var empty = document.getElementById('cartEmpty');
        var saveBtn = document.getElementById('saveSaleBtn');
        body.innerHTML = '';
        var total = 0;
        keys.forEach(function (name) {
            var item = cart[name];
            var sub = item.price * item.qty;
            total += sub;
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="fw-semibold" style="font-size:.78rem;">' + name + '</td>' +
                '<td class="text-end"><input type="number" class="form-control ui-input text-end p-1" style="width:55px;font-size:.78rem;" min="1" value="' + item.qty + '" data-name="' + name + '"></td>' +
                '<td class="text-end" style="font-size:.78rem;">' + peso(sub) + '</td>' +
                '<td class="text-end"><button type="button" class="edit-btn remove-btn" data-name="' + name + '"><i class="bi bi-x"></i></button></td>';
            body.appendChild(tr);
        });
        document.getElementById('grandTotal').textContent = peso(total);
        var hasItems = keys.length > 0;
        table.style.display = hasItems ? '' : 'none';
        empty.style.display = hasItems ? 'none' : '';
        saveBtn.disabled = !hasItems;
        // qty change
        body.querySelectorAll('input[type=number]').forEach(function (inp) {
            inp.addEventListener('change', function () {
                cart[inp.dataset.name].qty = Math.max(1, parseInt(inp.value) || 1);
                renderCart();
            });
        });
        // remove item
        body.querySelectorAll('.remove-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                delete cart[btn.dataset.name];
                renderCart();
            });
        });
    }

    // add item
    document.querySelectorAll('.add-item-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            var name = row.dataset.name;
            var price = parseFloat(row.dataset.price);
            if (cart[name]) { cart[name].qty += 1; } else { cart[name] = { price: price, qty: 1 }; }
            renderCart();
        });
    });

    // save sale
    document.getElementById('saveSaleBtn').addEventListener('click', function () {
        cart = {};
        renderCart();
        var msg = document.getElementById('savedMsg');
        msg.classList.remove('d-none');
        setTimeout(function () { msg.classList.add('d-none'); }, 2500);
    });

    // clear cart
    document.getElementById('clearCartBtn').addEventListener('click', function () {
        cart = {};
        renderCart();
    });

    // type filter
    document.querySelectorAll('#typeTabs .btn-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('#typeTabs .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
            var type = b.dataset.type;
            document.querySelectorAll('#itemTable tbody tr').forEach(function (r) {
                r.style.display = (type === 'all' || r.dataset.type === type) ? '' : 'none';
            });
        });
    });

    // search filter
    document.getElementById('itemSearch').addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#itemTable tbody tr').forEach(function (r) {
            r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

