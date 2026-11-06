<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar sticky-top border-bottom" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand brand d-flex align-items-center gap-2" href="#home"><span class="brand-icon"><i
                        class="bi bi-bag-fill"></i></span>Sales Tracker</a>
            <div class="navbar-collapse" id="nav">
                <ul class="navbar-nav gap-lg-3">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item">
                        <details class="help-menu feature-menu">
                            <summary class="nav-link">Features</summary>
                            <div class="help-dropdown">
                                <span>POS</span>
                                <span>Report</span>
                                <span>Expenses</span>
                                <span>Receipts</span>
                                <span>Inventory Management</span>
                            </div>
                        </details>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="#categories">Shop by Category</a></li>
                    <li class="nav-item">
                        <details class="help-menu">
                            <summary class="nav-link">Help Center</summary>
                            <div class="help-dropdown">
                                <a href="user-guides.html">User Guides</a>
                                <a href="faq.html">FAQ</a>
                            </div>
                        </details>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <section class="hero" id="home">
            <div class="container">
                <div class="row">
                    <div class="col-lg-7">
                        <div class="eyebrow">Your one-stop school supply store</div>
                        <h1>SCHOOL<br><span>SUPPLIES</span></h1>
                        <p>From your everyday essentials to your next big idea, find everything you need for school
                            right here.</p>
                        <div class="d-flex gap-2 mt-3"><a href="login.php" class="btn btn-dark btn-sm-pill">Login</a><a
                                href="#supplies" class="btn btn-outline-secondary btn-sm-pill">Explore Features</a>
                        </div>
                        <div class="small-note mt-3">Everything you need for a successful school year.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="stats">
            <div class="container">
                <div class="row row-cols-2 row-cols-md-4">
                    <div class="col stat"><strong>1,000<span class="d-inline">+</span></strong><span>Products
                            available</span></div>
                    <div class="col stat"><strong>500<span class="d-inline">+</span></strong><span>Happy
                            customers</span></div>
                    <div class="col stat"><strong>2<span class="d-inline">+</span></strong><span>Years serving
                            you</span></div>
                    <div class="col stat"><strong>24/7</strong><span>Here for your needs</span></div>
                </div>
            </div>
        </section>

        <section class="section" id="supplies">
            <div class="container">
                <h2>Features of Our Website</h2>
                <p class="intro">Manage your products, sales, and business performance in one place.</p>
                <div class="row g-3 feature-grid">
                    <div class="col-12 col-lg-6">
                        <article class="service-card feature-primary">
                            <div class="feature-label"><span></span> POS</div>
                            <h3 class="feature-title">Fast checkout in just a few taps.</h3>
                            <p>Record every sale without the hassle. Pick the product, enter the quantity, and the
                                transaction is done.</p>
                            <hr>
                            <ul>
                                <li>Easy to use, even on day one</li>
                                <li>Every sale is recorded and easy to look up</li>
                                <li>Connected to inventory, reports, and receipts</li>
                            </ul>
                        </article>
                    </div>
                    <div class="col-12 col-lg-6">
                        <article class="service-card">
                            <div class="feature-label"><span></span> INVENTORY</div>
                            <h3 class="feature-title">Always know what’s left on the shelf.</h3>
                            <p>Keep an eye on the stock of every item so you never run out and never overbuy.</p>
                            <hr>
                            <ul>
                                <li>A list of all your products and prices</li>
                                <li>See current stock at a glance</li>
                                <li>Spot items that are running low</li>
                            </ul>
                        </article>
                    </div>
                    <div class="col-12 col-lg-4">
                        <article class="service-card">
                            <div class="feature-label"><span></span> REPORTS</div>
                            <h3 class="feature-title">See how your business is doing.</h3>
                            <p>View your sales and profit by day, week, or month so decisions get easier.</p>
                            <hr>
                            <ul>
                                <li>Daily, weekly, and monthly summaries</li>
                                <li>Sales compared with expenses</li>
                            </ul>
                        </article>
                    </div>
                    <div class="col-12 col-lg-4">
                        <article class="service-card">
                            <div class="feature-label"><span></span> EXPENSES</div>
                            <h3 class="feature-title">Keep tabs on every expense.</h3>
                            <p>Log rent, utilities, supplies, and other costs so you know your real profit.</p>
                            <hr>
                            <ul>
                                <li>Every expense has a date and a note</li>
                                <li>See where your money goes</li>
                            </ul>
                        </article>
                    </div>
                    <div class="col-12 col-lg-4">
                        <article class="service-card">
                            <div class="feature-label"><span></span> RECEIPTS</div>
                            <h3 class="feature-title">A receipt for every sale.</h3>
                            <p>Give customers a receipt and keep your own copy for your records.</p>
                            <hr>
                            <ul>
                                <li>A receipt right after each sale</li>
                                <li>Easy to find past receipts</li>
                            </ul>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="categories">
            <div class="container">
                <h2>Shop by category <span class="tag">Explore</span></h2>
                <p class="intro">Find exactly what you're looking for in our carefully selected categories.</p>
                <div class="row g-3" id="categoryGrid">
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="School Supplies"><i
                                class="bi bi-journal"></i><span><strong>School
                                    Supplies</strong><small>Everything for
                                    class</small></span></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="Printing Services"><i
                                class="bi bi-printer"></i><span><strong>Printing
                                    Services</strong><small>Print and
                                    photocopy</small></span></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="Arts &amp; Crafts"><i
                                class="bi bi-palette"></i><span><strong>Arts &amp;
                                    Crafts</strong><small>Make something
                                    great</small></span></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="Bags"><i
                                class="bi bi-backpack"></i><span><strong>Bags</strong><small>Ready for every school
                                    day</small></span></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="Writing Materials"><i
                                class="bi bi-pen"></i><span><strong>Writing
                                    Materials</strong><small>Write your next big
                                    idea</small></span></div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="category" data-category="Office Supplies"><i
                                class="bi bi-clipboard"></i><span><strong>Office
                                    Supplies</strong><small>Workday
                                    essentials</small></span></div>
                    </div>
                </div>
                <div class="how-card d-none mt-3" id="categoryDetails" aria-live="polite">
                    <h3 id="categoryDetailTitle"></h3>
                    <p id="categoryDetailText"></p>
                    <button class="btn btn-dark btn-sm-pill mt-3" type="button" id="backToCategories"><i
                            class="bi bi-arrow-left me-1"></i>Back to categories</button>
                </div>
            </div>
        </section>

    </main>

    <footer class="footer tracker-footer" id="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-12 col-md-5 col-lg-5">
                    <div class="brand d-flex align-items-center gap-2"><span
                            class="footer-mark">▣</span><span><b>Sales</b> Tracker</span></div>
                    <p class="footer-tagline">Built for school supplies businesses</p>
                    <p class="footer-copyright">
                        <?= date('Y') ?> Sales Tracker
                    </p>
                </div>
                <div class="col-4 col-md-2 col-lg-2">
                    <div class="footer-title">Explore</div>
                    <a class="d-block" href="#supplies">Products</a>
                    <a class="d-block" href="#categories">Services</a>
                    <a class="d-block" href="#how">Pricing</a>
                </div>
                <div class="col-4 col-md-2 col-lg-3">
                    <div class="footer-title">Contact</div>
                    <a class="d-block" href="mailto:@salestracker.ph">@salestracker.ph</a>
                    <a class="d-block" href="tel:+63900000000">+63 900 000 000</a>
                </div>
                <div class="col-4 col-md-3 col-lg-2">
                    <div class="footer-title">Shop hours</div>
                    <p class="footer-hours">Mon-Sat, 8am-7pm</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>