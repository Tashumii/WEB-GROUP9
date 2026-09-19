# I hope read this so all of you have idea how overall will work when its already that
# always add "/" when every phase are done okay? before you to push in deployment branch example i made in phase 1 first check box i input [/].

# School Supplies Tracker — Project Plan 

**Type:**Web Application
**Purpose:** Track sales, services, and product inventory for a school supplies / printing business (e.g., binding services, school supplies), with admin-configurable pricing and analytics.

---

## 1. Overview

School Supplies Tracker is a multi-tenant SaaS web app that lets business owners:
- Track daily, weekly, and monthly sales
- Manage a catalog of **services** (e.g., soft binding, spiral binding) and **products** (e.g., pencil, paper, notebook) with configurable pricing
- View analytics on sales performance
- Manage everything through an **Admin Dashboard**

---

## 2. Core Features

### 2.1 Sales Tracking
- Record individual sale transactions (item/service + quantity + price + date)
- Daily sales summary
- Monthly sales summary
- Sales history log with search/filter (by date range, item, service)

### 2.2 Services Page
- List of offered services (e.g., Soft Binding, Spiral Binding, Lamination, Printing)
- Each service has: name, description, price, status (active/inactive)
- Configurable by admin (add/edit/delete/disable)

### 2.3 Products Page
- List of physical products (e.g., Pencil, Ballpen, Notebook, Bond Paper)
- Each product has: name, SKU (optional), price, stock quantity (optional), status
- Configurable by admin (add/edit/delete/disable)

### 2.4 Admin Dashboard
- Central panel to configure services and products (CRUD), per business
- Manage pricing changes
- Business switcher: admin can view "School Supplies near at BSU," "School Supplies near at Caltex," or a **combined comparison view** of both
- Staff accounts are scoped to one business each — a BSU staff account never sees Caltex data, and vice versa
- Overview widgets (today's sales, top-selling items, low stock alerts if stock is tracked)

### 2.5 Analytics
- Tabular analytics view (sortable/filterable table of sales data)
- Monthly sales trend (chart)
- Best-selling products/services (chart or ranked table)
- Revenue breakdown: products vs. services
- **Business comparison view (admin only):** side-by-side sales/revenue for BSU vs. Caltex
- Export analytics (CSV/PDF) — optional/future

---

## 3. Suggested Page/Route Structure

```
/                     → Landing/login page
/dashboard            → Admin overview (KPIs, quick stats)
/dashboard/sales      → Sales tracking (record + history)
/dashboard/services   → Manage services (soft binding, etc.)
/dashboard/products   → Manage products (pencil, etc.)
/dashboard/analytics  → Analytics (tabular + charts)
/dashboard/settings   → Admin/account settings, user management
```

---

## 4. Data Model

Multi-tenant schema: every table (except `businesses`) is scoped to a `business_id`, so multiple businesses can use the same SaaS instance with isolated data.

### businesses
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| name | VARCHAR(255) | Required |
| created_at | TIMESTAMP | Defaults to current time |

### users
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| business_id | INT, **nullable** | Required for staff (their one assigned business); **NULL for admin** — admin's businesses come from `admin_businesses` instead |
| username | VARCHAR(100) | Unique, required |
| password_hash | VARCHAR(255) | Store hashed passwords only, never plain text |
| role | ENUM('admin', 'staff') | Defaults to 'staff' |

### admin_businesses (join table — links one admin to multiple businesses)
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| user_id | INT | Foreign key → users.id (must be a user with role 'admin') |
| business_id | INT | Foreign key → businesses.id |

**Example setup:**
| Business | Staff |
|---|---|
| "School Supplies near at BSU" | Staff account(s) with `business_id` = BSU's id |
| "School Supplies near at Caltex" | Staff account(s) with `business_id` = Caltex's id |

The one admin account has `business_id` = NULL, and has two rows in `admin_businesses` — one linking to BSU, one linking to Caltex.

### products
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| business_id | INT | Foreign key → businesses.id |
| name | VARCHAR(255) | e.g., "Pencil" |
| price | DECIMAL(10,2) | Required |
| status | ENUM('active', 'inactive') | Defaults to 'active' |

### services
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| business_id | INT | Foreign key → businesses.id |
| name | VARCHAR(255) | e.g., "Soft Binding" |
| price | DECIMAL(10,2) | Required |
| description | TEXT | Optional |
| status | ENUM('active', 'inactive') | Defaults to 'active' |

### sales
| Column | Type | Notes |
|---|---|---|
| id | INT, auto-increment | Primary key |
| business_id | INT | Foreign key → businesses.id |
| recorded_by | INT | Foreign key → users.id |
| item_type | ENUM('product', 'service') | Which table item_id points to |
| item_id | INT | References products.id or services.id, depending on item_type |
| quantity | INT | Required |
| unit_price | DECIMAL(10,2) | Price at time of sale (not looked up live, so history stays accurate) |
| total | DECIMAL(10,2) | quantity × unit_price |
| sold_at | TIMESTAMP | Defaults to current time |

**Guide notes:**
- Every table except `businesses` needs a `business_id` foreign key back to `businesses.id` — this is what keeps each business's data separate (multi-tenancy).
- **Access model:** staff accounts are locked to one business via `users.business_id`. The admin account has `business_id` = NULL and instead is linked to multiple businesses through `admin_businesses`. When the admin logs in, look up all their linked `business_id`s from that table to decide what they can view.
- **Comparison view:** since every `sales` row carries a `business_id`, the admin dashboard can query and group sales by `business_id` to show Business A vs. Business B side by side (e.g., "BSU sales this month" vs. "Caltex sales this month").
- `sales.item_id` is a polymorphic reference: depending on `item_type`, it points to either `products.id` or `services.id`. MySQL can't enforce this with a single foreign key, so validate `item_id` exists in the correct table at the application layer (in the PHP endpoint) before inserting a sale.
- Always store `unit_price` and `total` on the sale record itself, not just a reference to the product/service — this way, if prices change later, past sales still reflect what was actually charged.
- Add indexes on every `business_id` column and on `sales.sold_at` — these will be queried constantly for filtering and analytics.
- Hash passwords with a strong algorithm (e.g., PHP's `password_hash()`) — never store plain text.

---

## 5. Tech Stack

- **Frontend:** HTML, CSS, Bootstrap 5, JavaScript
- **Backend:** PHP with AJAX (asynchronous requests to PHP endpoints, no full page reloads)
- **Database:** MySQL/MariaDB (natural pairing with PHP, well-suited to relational sales data)
- **Charts:** Chart.js (lightweight, works well with vanilla JS + AJAX-fetched data)
- **Auth:** PHP sessions, with role-based access (admin/staff)

### Notes on PHP/AJAX architecture
- Structure backend as PHP endpoints (e.g., `/api/sales.php`, `/api/products.php`, `/api/services.php`) returning JSON
- Frontend JS uses `fetch()` or jQuery AJAX to call these endpoints and update the DOM dynamically (e.g., sales table, analytics charts) without page reloads
- Use PDO with prepared statements for all database queries to prevent SQL injection
- Bootstrap 5 components (modals, tables, forms, navbar/sidebar) for the admin dashboard UI

---

## 6. Build Phases

### Phase 1 — Foundation
- [/] Set up project scaffold (frontend + backend + database)
- [ ] Auth (login/register, admin role)
- [ ] Database schema: Service, Product, Sale tables

### Phase 2 — Core CRUD
- [ ] Admin dashboard: add/edit/delete Services
- [ ] Admin dashboard: add/edit/delete Products
- [ ] Sales entry form (select service or product, quantity, auto-calc total)

### Phase 3 — Tracking & History
- [ ] Sales history table with filters (date range, type)
- [ ] Daily and monthly sales summary views

### Phase 4 — Analytics
- [ ] Tabular analytics view
- [ ] Monthly trend chart
- [ ] Best-selling items ranking
- [ ] Revenue breakdown (products vs services)

### Phase 5 — Polish & SaaS Readiness
- [ ] Multi-tenant support (if serving multiple businesses)
- [ ] Role-based permissions (admin vs staff)
- [ ] Responsive/mobile-friendly UI
- [ ] Export analytics (CSV/PDF)
- [ ] Deployment + monitoring

---

