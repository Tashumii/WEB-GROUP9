// pill toggle
function initPills(selector) {
    document.querySelectorAll(selector + ' .btn-pill').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll(selector + ' .btn-pill').forEach(function (x) { x.classList.remove('active'); });
            b.classList.add('active');
        });
    });
}

// table search
function initSearch(inputId, tableId) {
    var input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function (e) {
        var q = e.target.value.toLowerCase();
        document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function (r) {
            r.style.display = r.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
}

// html escaping
function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
    });
}

// money formatting
function peso(amount) {
    var value = Number(amount);
    if (!isFinite(value)) value = 0;
    return '₱' + value.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });
}

// debounce helper
function debounce(fn, delay) {
    var timer = null;
    return function () {
        var args = arguments;
        var context = this;
        clearTimeout(timer);
        timer = setTimeout(function () {
            fn.apply(context, args);
        }, delay);
    };
}

// api helper
var Api = {
    base: '../backend/api/',

    request: function (endpoint, options) {
        var settings = Object.assign({ credentials: 'same-origin' }, options || {});
        var method = String(settings.method || 'GET').toUpperCase();

        if (['GET', 'HEAD', 'OPTIONS'].indexOf(method) === -1) {
            var meta = document.querySelector('meta[name="csrf-token"]');
            settings.headers = Object.assign({}, settings.headers || {});
            if (meta) {
                settings.headers['X-CSRF-Token'] = meta.getAttribute('content');
            }
        }

        return fetch(this.base + endpoint, settings).then(function (response) {
            return response.json().catch(function () {
                throw new Error('Unexpected server response');
            }).then(function (data) {
                if (!response.ok || data.success === false) {
                    if (response.status === 401) {
                        window.location.href = 'login.php';
                    }
                    var message = data.error || (response.status === 429
                        ? 'Too many requests. Please slow down and try again in a moment.'
                        : (response.status === 419
                            ? 'Your session expired. Please refresh the page.'
                            : 'Request failed'));
                    var error = new Error(message);
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        });
    },

    // get request
    get: function (endpoint, params) {
        var url = endpoint;
        if (params) {
            var qs = new URLSearchParams(params).toString();
            if (qs) url += '?' + qs;
        }
        return this.request(url, { method: 'GET' });
    },

    // post request
    post: function (endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        });
    },

    // put request
    put: function (endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        });
    },

    // delete request
    del: function (endpoint, params) {
        var url = endpoint;
        if (params) {
            var qs = new URLSearchParams(params).toString();
            if (qs) url += '?' + qs;
        }
        return this.request(url, { method: 'DELETE' });
    }
};

function showToast(message, isError) {
    var region = document.getElementById('toastRegion');
    if (!region) return;
    var toast = document.createElement('div');
    toast.className = 'toast' + (isError ? ' is-error' : '');
    toast.textContent = String(message || '');
    region.appendChild(toast);
    window.setTimeout(function () {
        toast.classList.add('is-leaving');
        window.setTimeout(function () { toast.remove(); }, 250);
    }, 3200);
}

(function () {
    var STORAGE_KEY = 'st-sidebar-collapsed';

    function mq(query) { return window.matchMedia(query); }
    var MQ_MOBILE = '(max-width:991.98px)';
    var MQ_AUTO = '(max-width:1199.98px)';

    function storedCollapsed() {
        try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { return false; }
    }
    function setStoredCollapsed(value) {
        try { localStorage.setItem(STORAGE_KEY, value ? '1' : '0'); } catch (e) {}
    }
    function effectiveCollapsed() {
        if (mq(MQ_MOBILE).matches) return false;
        if (mq(MQ_AUTO).matches) return true;
        return storedCollapsed();
    }
    function applyCollapsed() {
        var collapsed = effectiveCollapsed();
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        var btn = document.getElementById('sidebarCollapse');
        if (btn) {
            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            btn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            btn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
            btn.setAttribute('data-tooltip', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }
    }

    function initSidebarCollapse() {
        var btn = document.getElementById('sidebarCollapse');
        if (!btn) return;
        applyCollapsed();
        btn.addEventListener('click', function () {
            var next = !effectiveCollapsed();
            if (mq(MQ_AUTO).matches && !mq(MQ_MOBILE).matches) {
                next = !storedCollapsed();
                setStoredCollapsed(next);
                applyCollapsed();
                return;
            }
            setStoredCollapsed(next);
            applyCollapsed();
        });
        [mq(MQ_MOBILE), mq(MQ_AUTO)].forEach(function (list) {
            if (list.addEventListener) list.addEventListener('change', applyCollapsed);
            else if (list.addListener) list.addListener(applyCollapsed);
        });
    }

    function isTypingTarget(target) {
        if (!target || target === document.body) return false;
        var tag = (target.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || target.isContentEditable;
    }

    function initJumpPalette() {
        var palette = document.getElementById('jumpPalette');
        var input = document.getElementById('jumpInput');
        var list = document.getElementById('jumpList');
        if (!palette || !input || !list) return;
        var items = Array.prototype.slice.call(list.querySelectorAll('.jump-item'));
        var lastFocused = null;
        var selectedIndex = -1;

        function visibleItems() {
            return items.filter(function (item) { return item.style.display !== 'none'; });
        }
        function syncSelection(idx) {
            var visible = visibleItems();
            selectedIndex = idx;
            items.forEach(function (item) { item.setAttribute('aria-selected', 'false'); });
            if (visible[idx]) {
                visible[idx].setAttribute('aria-selected', 'true');
                visible[idx].scrollIntoView({ block: 'nearest' });
            }
        }
        function filter() {
            var q = input.value.trim().toLowerCase();
            items.forEach(function (item) {
                var haystack = ((item.getAttribute('data-label') || '') + ' ' + (item.getAttribute('data-search') || '')).toLowerCase();
                item.style.display = haystack.indexOf(q) !== -1 ? '' : 'none';
            });
            var visible = visibleItems();
            syncSelection(visible.length ? 0 : -1);
        }
        function openPalette() {
            lastFocused = document.activeElement;
            palette.hidden = false;
            input.value = '';
            filter();
            input.focus();
            input.select();
        }
        function closePalette(returnFocus) {
            palette.hidden = true;
            if (returnFocus && lastFocused && lastFocused.focus) lastFocused.focus();
        }
        function isOpen() { return !palette.hidden; }

        input.addEventListener('input', filter);
        input.addEventListener('keydown', function (event) {
            var visible = visibleItems();
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                syncSelection(Math.min(selectedIndex + 1, visible.length - 1));
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                syncSelection(Math.max(selectedIndex - 1, 0));
            } else if (event.key === 'Enter') {
                event.preventDefault();
                var target = visible[selectedIndex] || visible[0];
                if (target && target.getAttribute('data-href')) window.location.href = target.getAttribute('data-href');
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closePalette(true);
            }
        });
        items.forEach(function (item) {
            item.addEventListener('click', function () {
                var href = item.getAttribute('data-href');
                if (href) window.location.href = href;
            });
            item.addEventListener('mouseenter', function () {
                var visible = visibleItems();
                syncSelection(visible.indexOf(item));
            });
        });
        palette.addEventListener('click', function (event) {
            if (event.target === palette) closePalette(true);
        });

        palette.open = openPalette;
        palette.close = closePalette;
        palette.isOpen = isOpen;
    }

    function initShortcuts() {
        document.addEventListener('keydown', function (event) {
            var palette = document.getElementById('jumpPalette');
            var input = document.getElementById('jumpInput');

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (!palette) return;
                if (!palette.hidden) { palette.close(true); } else { palette.open(); }
                return;
            }
            if (event.key === 'Escape' && palette && !palette.hidden && document.activeElement !== input) {
                palette.close(true);
                return;
            }
            if (isTypingTarget(event.target)) return;
            if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                var search = document.querySelector('input[type="search"]');
                if (search) { event.preventDefault(); search.focus(); search.select(); }
                return;
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                var path = window.location.pathname;
                if (path.indexOf('RecordSales.php') !== -1) {
                    var btn = document.getElementById('saveSaleBtn');
                    if (btn && !btn.disabled) { event.preventDefault(); btn.click(); }
                }
            }
        });
    }

    function init() {
        initSidebarCollapse();
        initJumpPalette();
        initShortcuts();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
