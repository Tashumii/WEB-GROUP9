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

// api helper
var Api = {
    base: '../backend/api/',

    // get request
    get: function (endpoint, params) {
        var url = this.base + endpoint;
        if (params) {
            var qs = new URLSearchParams(params).toString();
            if (qs) url += '?' + qs;
        }
        return fetch(url).then(function (r) { return r.json(); });
    },

    // post request
    post: function (endpoint, data) {
        return fetch(this.base + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data || {})
        }).then(function (r) { return r.json(); });
    }
};
