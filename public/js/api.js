(function () {
    'use strict';

    var apiMeta = document.querySelector('meta[name="api-base-url"]');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var loginMeta = document.querySelector('meta[name="login-url"]');
    var apiBase = apiMeta ? apiMeta.content.replace(/\/$/, '') : '/api';

    function endpointUrl(endpoint, params) {
        var name = String(endpoint || '').split('/').pop();
        var url = apiBase + '/' + encodeURIComponent(name);
        if (params && Object.keys(params).length) {
            var query = new URLSearchParams();
            Object.keys(params).forEach(function (key) {
                if (params[key] !== undefined && params[key] !== null) query.set(key, params[key]);
            });
            url += '?' + query.toString();
        }
        return url;
    }

    function syncCsrfToken(token) {
        if (!token) return;
        if (csrfMeta) csrfMeta.content = token;
        // Plain HTML forms (logout, etc.) carry their own hidden token; keep
        // those in sync after actions that rotate the CSRF token.
        document.querySelectorAll('input[name="csrf_token"]').forEach(function (input) {
            input.value = token;
        });
    }

    function request(method, endpoint, payload, params) {
        var headers = { 'Accept': 'application/json' };
        var options = { method: method, headers: headers, credentials: 'same-origin' };
        if (method !== 'GET') {
            headers['Content-Type'] = 'application/json';
            headers['X-CSRF-Token'] = csrfMeta ? csrfMeta.content : '';
            options.body = JSON.stringify(payload || {});
        }
        return fetch(endpointUrl(endpoint, params), options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (data.csrf_token) syncCsrfToken(data.csrf_token);
                if (!response.ok || data.success === false) {
                    if (response.status === 401 && loginMeta && window.APP_USER && Number(window.APP_USER.id) > 0) {
                        window.location.assign(loginMeta.content);
                    }
                    var error = new Error(data.error || data.message || 'Request failed.');
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        });
    }

    window.Api = {
        get: function (endpoint, params) { return request('GET', endpoint, null, params); },
        post: function (endpoint, payload) { return request('POST', endpoint, payload); },
        put: function (endpoint, payload) { return request('PUT', endpoint, payload); },
        delete: function (endpoint, payload) { return request('DELETE', endpoint, payload); }
    };
})();
