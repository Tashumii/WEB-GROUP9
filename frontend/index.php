<!doctype html>
<?php require_once __DIR__ . '/../backend/api/session_boot.php'; app_send_security_headers(); ?>
<html lang="en" data-type="place" data-radius="soft" data-buttons="pill" data-motion="hover" style="color-scheme: light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sales Tracker — school supplies shop management for products, sales, and analytics in one place.">
    <meta name="theme-color" content="#090909">
    <meta name="page-type" content="place">
    <meta name="site-kind" content="other">
    <meta name="page-kind" content="place">
    <title>Sales Tracker — School Supplies</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <nav class="navbar navbar-expand-lg sticky-top border-bottom" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand brand d-flex align-items-center gap-2" href="#home"><span class="brand-icon"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span><span translate="no">Sales Tracker</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="#home" aria-current="page">Home</a></li>
                    <li class="nav-item">
                        <details class="help-menu feature-menu">
                            <summary class="nav-link" aria-haspopup="true">Features</summary>
                            <div class="help-dropdown">
                                <span>POS</span>
                                <span>Reports</span>
                                <span>Expenses</span>
                                <span>Receipts</span>
                                <span>Inventory Management</span>
                            </div>
                        </details>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#categories">Shop by Category</a></li>
                    <li class="nav-item">
                        <details class="help-menu">
                            <summary class="nav-link" aria-haspopup="true">Help Center</summary>
                            <div class="help-dropdown">
                                <a href="mailto:hello@salestracker.ph">Contact Us</a>
                                <a href="#footer">Shop Hours</a>
                            </div>
                        </details>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main id="main-content">
        <section class="hero" id="home" aria-labelledby="hero-heading">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6 col-xl-7">
                        <div class="eyebrow">Your one-stop school supply store</div>
                        <h1 id="hero-heading">SCHOOL<br><span>SUPPLIES</span></h1>
                        <p>From your everyday essentials to your next big idea, find everything you need for school right here.</p>
                        <div class="d-flex gap-2 mt-3 flex-wrap"><a href="login.php" class="btn btn-dark btn-sm-pill">Log In</a><a href="register.php" class="btn btn-outline-secondary btn-sm-pill">Create Account</a><a href="#supplies" class="btn btn-outline-secondary btn-sm-pill">Explore Products</a></div>
                        <div class="small-note mt-3">Everything you need for a successful school year — 1,000+ products, 24/7 support.</div>
                    </div>
                    <div class="col-lg-6 col-xl-5">
                        <figure class="app-frame" aria-label="Sales Tracker dashboard preview">
                            <div class="app-frame-head">
                                <span class="app-dot" aria-hidden="true"></span><span class="app-dot" aria-hidden="true"></span><span class="app-dot" aria-hidden="true"></span>
                                <span class="app-title" translate="no">Sales Tracker — Today</span>
                            </div>
                            <div class="app-frame-body">
                                <div class="app-kpi"><strong>₱4,280</strong><span>Today’s sales</span></div>
                                <div class="app-bars" aria-hidden="true"><i style="--h:34%"></i><i style="--h:52%"></i><i style="--h:44%"></i><i style="--h:68%"></i><i style="--h:82%"></i></div>
                                <ul class="app-rows">
                                    <li><i class="fa-solid fa-pencil" aria-hidden="true"></i><span><strong>Best-selling</strong><small>Writing materials</small></span><b>142 sold</b></li>
                                    <li><i class="fa-solid fa-print" aria-hidden="true"></i><span><strong>Printing</strong><small>Print &amp; photocopy</small></span><b>96 jobs</b></li>
                                </ul>
                                <a class="app-cta" href="Dashboard.php">View Dashboard <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                            </div>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats" aria-label="Store statistics">
            <div class="container">
                <div class="row row-cols-2 row-cols-md-4">
                    <div class="col stat"><strong>1,000<span aria-hidden="true">+</span></strong><span>Products available</span></div>
                    <div class="col stat"><strong>500<span aria-hidden="true">+</span></strong><span>Happy customers</span></div>
                    <div class="col stat"><strong>2<span aria-hidden="true">+</span></strong><span>Years serving you</span></div>
                    <div class="col stat"><strong>24/7</strong><span>Here for your needs</span></div>
                </div>
            </div>
        </section>

        <section class="section" id="supplies" aria-labelledby="features-heading">
            <div class="container">
                <h2 id="features-heading">Features of Our Website</h2>
                <p class="intro">Manage your products, sales, and business performance in one place.</p>
                <div class="row g-3">
                    <div class="col-6 col-lg-4">
                        <article class="service-card"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                            <h3>Dashboard</h3>
                            <p>See an overview of your shop and its activity at a glance.</p>
                            <a class="card-link" href="Dashboard.php">View Dashboard</a>
                        </article>
                    </div>
                    <div class="col-6 col-lg-4">
                        <article class="service-card"><i class="fa-solid fa-receipt" aria-hidden="true"></i>
                            <h3>Record Sales</h3>
                            <p>Log each transaction and keep your sales history organized.</p>
                            <a class="card-link" href="RecordSales.php">Record a Sale</a>
                        </article>
                    </div>
                    <div class="col-6 col-lg-4">
                        <article class="service-card"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
                            <h3>Products &amp; Services</h3>
                            <p>Manage the products and services your shop offers.</p>
                            <a class="card-link" href="Products.php">Manage Products</a>
                        </article>
                    </div>
                    <div class="col-6 col-lg-4">
                        <article class="service-card"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i>
                            <h3>Sales Analytics</h3>
                            <p>Review sales trends and understand how your shop is performing.</p>
                            <a class="card-link" href="Sales.php">View Sales</a>
                        </article>
                    </div>
                    <div class="col-6 col-lg-4">
                        <article class="service-card"><i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                            <h3>Analytics</h3>
                            <p>Use clear business insights to make informed shop decisions.</p>
                            <a class="card-link" href="Analytics.php">Open Analytics</a>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="categories" aria-labelledby="categories-heading">
            <div class="container">
                <h2 id="categories-heading">Shop by Category <span class="tag">Explore</span></h2>
                <p class="intro">Find exactly what you’re looking for in our carefully selected categories.</p>
                <div class="row g-3" id="categoryGrid" role="group" aria-label="Product categories">
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="School Supplies" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-book-open" aria-hidden="true"></i><span><strong>School Supplies</strong><small>Everything for class</small></span></button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="Printing Services" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-print" aria-hidden="true"></i><span><strong>Printing Services</strong><small>Print and photocopy</small></span></button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="Arts &amp; Crafts" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-palette" aria-hidden="true"></i><span><strong>Arts &amp; Crafts</strong><small>Make something great</small></span></button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="Bags" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><span><strong>Bags</strong><small>Ready for every school day</small></span></button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="Writing Materials" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-pen" aria-hidden="true"></i><span><strong>Writing Materials</strong><small>Write your next big idea</small></span></button>
                    </div>
                    <div class="col-6 col-lg-3">
                        <button type="button" class="category" data-category="Office Supplies" aria-expanded="false" aria-controls="categoryDetails"><i class="fa-solid fa-clipboard" aria-hidden="true"></i><span><strong>Office Supplies</strong><small>Workday essentials</small></span></button>
                    </div>
                </div>
                <div class="how-card d-none mt-3" id="categoryDetails" aria-live="polite" tabindex="-1">
                    <h3 id="categoryDetailTitle"></h3>
                    <p id="categoryDetailText"></p>
                    <button class="btn btn-dark btn-sm-pill mt-3" type="button" id="backToCategories"><i class="fa-solid fa-arrow-left me-1" aria-hidden="true"></i>Back to Categories</button>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer tracker-footer" id="footer" aria-labelledby="footer-heading">
        <div class="container">
            <h2 id="footer-heading" class="visually-hidden">Footer</h2>
            <div class="row g-4">
                <div class="col-12 col-md-5 col-lg-5">
                    <div class="brand d-flex align-items-center gap-2"><i class="fa-solid fa-grip footer-mark" aria-hidden="true"></i><span><b>Sales</b> <span translate="no">Tracker</span></span></div>
                    <p class="footer-tagline">Built for school supplies businesses</p>
                    <p class="footer-copyright">
                        <?= date('Y') ?> Sales Tracker
                    </p>
                </div>
                <div class="col-4 col-md-2 col-lg-2">
                    <h3 class="footer-title">Explore</h3>
                    <a class="d-block" href="#supplies">Products</a>
                    <a class="d-block" href="#categories">Services</a>
                    <a class="d-block" href="#supplies">Pricing</a>
                </div>
                <div class="col-4 col-md-2 col-lg-3">
                    <h3 class="footer-title">Contact</h3>
                    <a class="d-block" href="mailto:hello@salestracker.ph" translate="no">hello@salestracker.ph</a>
                    <a class="d-block" href="tel:+63900000000" translate="no">+63 900 000 000</a>
                </div>
                <div class="col-4 col-md-3 col-lg-2">
                    <h3 class="footer-title">Shop hours</h3>
                    <p class="footer-hours">Mon-Sat, 8am-7pm</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      (function () {
        var press = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('details.help-menu').forEach(function (d) {
          var s = d.querySelector('summary');
          s.setAttribute('aria-expanded', d.open ? 'true' : 'false');
          d.addEventListener('toggle', function () { s.setAttribute('aria-expanded', d.open ? 'true' : 'false'); });
          document.addEventListener('click', function (e) { if (d.open && !d.contains(e.target)) d.open = false; });
          document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && d.open) { d.open = false; press ? s.focus() : s.focus(); } });
        });
        var copy = {
          'School Supplies': 'Notebooks, bond paper, and classroom essentials restocked weekly.',
          'Printing Services': 'Print, photocopy, and binding — ready in minutes.',
          'Arts & Crafts': 'Paints, paper, and craft kits for class projects.',
          'Bags': 'Durable bags sized for books, lunch, and daily wear.',
          'Writing Materials': 'Pens, pencils, and markers for your next big idea.',
          'Office Supplies': 'Folders, clips, and workday essentials.'
        };
        var grid = document.getElementById('categoryGrid'), details = document.getElementById('categoryDetails'),
            title = document.getElementById('categoryDetailTitle'), text = document.getElementById('categoryDetailText'),
            back = document.getElementById('backToCategories'), lastBtn = null;
        function show(btn) {
          lastBtn = btn;
          grid.querySelectorAll('.category').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('is-active'); });
          btn.setAttribute('aria-expanded', 'true'); btn.classList.add('is-active');
          title.textContent = btn.dataset.category;
          text.textContent = copy[btn.dataset.category] || 'Browse this category in store.';
          details.classList.remove('d-none'); details.focus({ preventScroll: true });
        }
        function hide() {
          details.classList.add('d-none');
          grid.querySelectorAll('.category').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('is-active'); });
          if (lastBtn) lastBtn.focus({ preventScroll: true });
        }
        grid.addEventListener('click', function (e) { var b = e.target.closest('.category'); if (b) show(b); });
        back.addEventListener('click', hide);
        details.addEventListener('keydown', function (e) { if (e.key === 'Escape') hide(); });
      })();
    </script>
</body>

</html>
