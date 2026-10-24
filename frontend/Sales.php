<?php

$pageTitle    = 'Sales history';
$pageSubtitle = 'Every recorded sale, with quantity, unit price and total';
$activePage   = 'history';

include 'includes/header.php';
?>

      <!-- ============ Filters ============ -->
      <section class="ui-card">
        <div class="ui-card-body">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-pill">Today</button>
            <button type="button" class="btn btn-pill active">This week</button>
            <button type="button" class="btn btn-pill">This month</button>
            <button type="button" class="btn btn-pill">Custom range</button>
          </div>

          <form class="row g-3" onsubmit="return false">
            <div class="col-md-6 col-lg-4">
              <label for="from" class="field-label">From</label>
              <input type="text" id="from" class="form-control ui-input" value="September 28, 2026">
            </div>
            <div class="col-md-6 col-lg-4">
              <label for="to" class="field-label">To</label>
              <input type="text" id="to" class="form-control ui-input" value="October 05, 2026">
            </div>
            <div class="col-lg-4">
              <label for="type" class="field-label">Type</label>
              <select id="type" class="form-select ui-input">
                <option selected>Products &amp; Services</option>
                <option>Products</option>
                <option>Services</option>
              </select>
            </div>
            <div class="col-12">
              <label for="search" class="field-label">Product or service</label>
              <input type="search" id="search" class="form-control ui-input" placeholder="Search by name">
            </div>
          </form>
        </div>
      </section>

      <!-- ============ Stat cards ============ -->
      <section class="row g-3 mb-3">
        <div class="col-12 col-md-4">
          <div class="ui-card stat">
            <div class="stat-label">Total sales Sep 28 - Oct 04</div>
            <div class="stat-value">₱53,000</div>
          </div>
        </div>
        <div class="col-6 col-md-4">
          <div class="ui-card stat">
            <div class="stat-label">Record in range</div>
            <div class="stat-value">214</div>
          </div>
        </div>
        <div class="col-6 col-md-4">
          <div class="ui-card stat">
            <div class="stat-label">Products vs services</div>
            <div class="stat-value">62% / 28%</div>
          </div>
        </div>
      </section>

      <!-- ============ Sales table ============ -->
      <section class="ui-card">
        <div class="table-responsive">
          <table class="table sales-table align-middle mb-0">
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
              <tr>
                <td>Oct 4</td>
                <td>7:41PM</td>
                <td>Ballpen</td>
                <td>Product</td>
                <td class="text-end">10</td>
                <td class="text-end">₱2</td>
                <td class="text-end fw-semibold">₱20</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <?php include 'includes/footer.php'; ?>