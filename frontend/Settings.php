<?php

$pageTitle    = 'Settings';
$pageSubtitle = 'Manage your account, security, team, and businesses';
$activePage   = 'settings';

include 'includes/header.php';

$usernameRaw = (string)($_SESSION['username'] ?? 'User');
$roleKey = $currentRoleKey;
$roleLabel = ucfirst($roleKey);
$initial = htmlspecialchars(strtoupper(substr($usernameRaw, 0, 2)));
$isOwner = $roleKey === 'owner';
?>

<style>
    .settings-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .settings-panel {
        display: none;
    }
    .settings-panel.active {
        display: block;
    }
    .team-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .team-role-select {
        min-width: 110px;
    }
    .settings-message {
        font-size: 0.82rem;
    }
</style>

<!-- profile card -->
<section class="ui-card profile-card">
    <div class="avatar avatar-lg avatar-placeholder"><?= $initial ?></div>

    <div class="profile-text">
        <div class="profile-name"><?= htmlspecialchars($usernameRaw) ?></div>
        <div class="profile-meta">
            <span id="profileRoleLabel"><?= htmlspecialchars($roleLabel) ?></span>
            &middot;
            <span id="profileBusiness">Loading business...</span>
        </div>
    </div>
</section>

<div id="pageMessage" class="alert d-none settings-message" role="alert"></div>

<div class="settings-tabs" role="tablist">
    <button type="button" class="btn btn-pill active" data-settings-tab="profile">Profile</button>
    <button type="button" class="btn btn-pill" data-settings-tab="security">Security</button>
    <button type="button" class="btn btn-pill" data-settings-tab="sessions">Sessions</button>
    <?php if ($isOwner): ?>
        <button type="button" class="btn btn-pill" data-settings-tab="team">Team</button>
    <?php endif; ?>
    <button type="button" class="btn btn-pill" data-settings-tab="businesses">Businesses</button>
</div>

<!-- profile -->
<section id="tab-profile" class="settings-panel active">
    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Account details</h2>
            <p class="ui-card-subtitle">Your account is managed through the business you belong to.</p>
        </div>

        <div class="ui-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="field-label" for="profileUsername">Username</label>
                    <input type="text" id="profileUsername" class="form-control ui-input" value="<?= htmlspecialchars($usernameRaw) ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="field-label" for="profileRole">Role in active business</label>
                    <input type="text" id="profileRole" class="form-control ui-input" value="<?= htmlspecialchars($roleLabel) ?>" readonly>
                </div>
                <div class="col-12">
                    <label class="field-label" for="profileBusinessInput">Active business</label>
                    <input type="text" id="profileBusinessInput" class="form-control ui-input" value="Loading..." readonly>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- security -->
<section id="tab-security" class="settings-panel">
    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Change password</h2>
            <p class="ui-card-subtitle">Use at least 8 characters. You will stay signed in after changing it.</p>
        </div>

        <div class="ui-card-body">
            <div id="securityMsg" class="alert d-none settings-message" role="alert"></div>

            <form id="passwordForm" class="row g-3" novalidate>
                <div class="col-12">
                    <label class="field-label" for="currentPassword">Current password</label>
                    <input type="password" id="currentPassword" class="form-control ui-input" autocomplete="current-password" required>
                </div>
                <div class="col-md-6">
                    <label class="field-label" for="newPassword">New password</label>
                    <input type="password" id="newPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <div class="col-md-6">
                    <label class="field-label" for="confirmNewPassword">Confirm new password</label>
                    <input type="password" id="confirmNewPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-ui btn-ui-primary" id="passwordBtn">Update password</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- sessions -->
<section id="tab-sessions" class="settings-panel">
    <div class="ui-card">
        <div class="ui-card-header d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <h2 class="ui-card-title">Signed-in devices</h2>
                <p class="ui-card-subtitle">Sessions are tied to your account. Sign out a device you do not recognise.</p>
            </div>
            <button type="button" class="btn-ui btn-ui-outline" id="revokeAllSessionsBtn">Sign out other devices</button>
        </div>

        <div class="ui-card-body">
            <div id="sessionsMsg" class="alert d-none settings-message" role="alert"></div>
            <div class="table-responsive">
                <table class="table sales-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>IP address</th>
                            <th>Last active</th>
                            <th>Expires</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="sessionsBody">
                        <tr><td colspan="5" class="text-muted text-center py-3">Loading sessions...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php if ($isOwner): ?>
<!-- team -->
<section id="tab-team" class="settings-panel">
    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Add a team member</h2>
            <p class="ui-card-subtitle">
                Create a new account, or enter the username and current password of an existing account to add it to this business.
            </p>
        </div>

        <div class="ui-card-body">
            <div id="teamMsg" class="alert d-none settings-message" role="alert"></div>

            <form id="addMemberForm" class="row g-3" novalidate>
                <div class="col-md-4">
                    <label class="field-label" for="memberUsername">Username</label>
                    <input type="text" id="memberUsername" class="form-control ui-input" maxlength="50" autocomplete="off" required>
                </div>
                <div class="col-md-3">
                    <label class="field-label" for="memberPassword">Password</label>
                    <input type="password" id="memberPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <div class="col-md-3">
                    <label class="field-label" for="memberConfirmPassword">Confirm password</label>
                    <input type="password" id="memberConfirmPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <div class="col-md-2">
                    <label class="field-label" for="memberRole">Role</label>
                    <select id="memberRole" class="form-select ui-input">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn-ui btn-ui-primary" id="addMemberBtn">Add member</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Team members</h2>
            <p class="ui-card-subtitle">Owners manage roles, passwords, and access for this business.</p>
        </div>

        <div class="ui-card-body p-0">
            <div class="table-responsive">
                <table class="table sales-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Last login</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teamBody">
                        <tr><td colspan="4" class="text-muted text-center py-3">Loading team...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- businesses -->
<section id="tab-businesses" class="settings-panel">
    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Your businesses</h2>
            <p class="ui-card-subtitle">Switch the active business or create a new one. Creating a business makes you its owner.</p>
        </div>

        <div class="ui-card-body p-0">
            <div class="table-responsive">
                <table class="table sales-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Your role</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="businessBody">
                        <tr><td colspan="4" class="text-muted text-center py-3">Loading businesses...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="ui-card">
        <div class="ui-card-header">
            <h2 class="ui-card-title">Create a business</h2>
            <p class="ui-card-subtitle">The new business starts empty and becomes your active business.</p>
        </div>

        <div class="ui-card-body">
            <div id="businessMsg" class="alert d-none settings-message" role="alert"></div>

            <form id="createBusinessForm" class="row g-3" novalidate>
                <div class="col-md-8">
                    <label class="field-label" for="businessNameInput">Business name</label>
                    <input type="text" id="businessNameInput" class="form-control ui-input" maxlength="255" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-ui btn-ui-primary w-100" id="createBusinessBtn">Create business</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- reset password modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="resetPasswordForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordTitle">Reset password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="resetPasswordMsg" class="alert alert-danger d-none settings-message" role="alert"></div>
                    <p class="text-muted settings-message mb-3">
                        Set a temporary password for <strong id="resetPasswordUser"></strong>. The member must change it after signing in.
                    </p>
                    <input type="hidden" id="resetUserId">
                    <div class="mb-3">
                        <label class="field-label" for="resetNewPassword">New password</label>
                        <input type="password" id="resetNewPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <div class="mb-0">
                        <label class="field-label" for="resetConfirmPassword">Confirm password</label>
                        <input type="password" id="resetConfirmPassword" class="form-control ui-input" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark" id="resetPasswordBtn">Reset password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var roleLabels = { owner: 'Owner', admin: 'Admin', staff: 'Staff' };
    var teamLoaded = false;
    var businessesLoaded = false;
    var sessionsLoaded = false;

    function showAlert(id, type, text) {
        var box = document.getElementById(id);
        if (!box) return;
        box.className = 'alert alert-' + type + ' settings-message';
        box.textContent = text;
        box.classList.remove('d-none');
    }

    function hideAlert(id) {
        var box = document.getElementById(id);
        if (box) box.classList.add('d-none');
    }

    function formatDateTime(value) {
        if (!value) return 'Never';
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) return value;
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function loadProfile() {
        Api.get('auth.php').then(function (data) {
            if (!data.authenticated) return;

            var active = null;
            (data.businesses || []).forEach(function (business) {
                if (Number(business.id) === Number(data.active_business_id)) active = business;
            });

            if (active) {
                document.getElementById('profileBusiness').textContent = active.name;
                document.getElementById('profileBusinessInput').value = active.name;
                document.getElementById('profileRoleLabel').textContent = roleLabels[active.role] || active.role;
                document.getElementById('profileRole').value = roleLabels[active.role] || active.role;
            }
        }).catch(function (err) {
            showAlert('pageMessage', 'danger', err.message);
        });
    }

    function loadBusinesses() {
        Api.get('businesses.php').then(function (data) {
            var body = document.getElementById('businessBody');
            body.innerHTML = '';
            var list = data.businesses || [];

            if (!list.length) {
                body.innerHTML = '<tr><td colspan="4" class="text-muted text-center py-3">No businesses yet.</td></tr>';
                return;
            }

            list.forEach(function (business) {
                var tr = document.createElement('tr');
                var isActive = Number(business.id) === Number(data.active_business_id);
                var action = isActive
                    ? '<span class="status-badge status-available">Active</span>'
                    : '<button type="button" class="btn btn-sm btn-outline-dark switch-business-btn" data-business-id="' + Number(business.id) + '">Switch</button>';

                tr.innerHTML =
                    '<td class="fw-semibold">' + escapeHtml(business.name) + '</td>' +
                    '<td>' + escapeHtml(roleLabels[business.role] || business.role) + '</td>' +
                    '<td>' + (isActive ? '<span class="status-badge status-available">Active</span>' : '<span class="status-badge status-low">Inactive</span>') + '</td>' +
                    '<td class="text-end">' + action + '</td>';
                body.appendChild(tr);
            });

            body.querySelectorAll('.switch-business-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    hideAlert('businessMsg');
                    button.disabled = true;
                    Api.post('auth.php', {
                        action: 'switch_business',
                        business_id: Number(button.dataset.businessId)
                    }).then(function () {
                        window.location.reload();
                    }).catch(function (err) {
                        showAlert('businessMsg', 'danger', err.message);
                        button.disabled = false;
                    });
                });
            });
        }).catch(function (err) {
            showAlert('businessMsg', 'danger', err.message);
        });
    }

    function loadSessions() {
        var body = document.getElementById('sessionsBody');
        if (!body) return;

        Api.get('sessions.php').then(function (data) {
            body.innerHTML = '';
            var list = data.sessions || [];

            if (!list.length) {
                body.innerHTML = '<tr><td colspan="5" class="text-muted text-center py-3">No active sessions.</td></tr>';
                return;
            }

            list.forEach(function (sessionItem) {
                var tr = document.createElement('tr');
                var badge = sessionItem.is_current
                    ? ' <span class="status-badge status-available">Current</span>'
                    : '';
                var action = sessionItem.is_current
                    ? '<button type="button" class="btn btn-sm btn-outline-danger revoke-session-btn" data-session-id="' + Number(sessionItem.id) + '">Sign out</button>'
                    : '<button type="button" class="btn btn-sm btn-outline-danger revoke-session-btn" data-session-id="' + Number(sessionItem.id) + '">Sign out</button>';

                tr.innerHTML =
                    '<td class="fw-semibold">' + escapeHtml(sessionItem.user_agent || 'Unknown device') + badge + '</td>' +
                    '<td>' + escapeHtml(sessionItem.ip_address || '—') + '</td>' +
                    '<td class="text-muted" style="font-size:.78rem;">' + escapeHtml(formatDateTime(sessionItem.last_seen_at)) + '</td>' +
                    '<td class="text-muted" style="font-size:.78rem;">' + escapeHtml(formatDateTime(sessionItem.expires_at)) + '</td>' +
                    '<td class="text-end">' + action + '</td>';
                body.appendChild(tr);
            });

            body.querySelectorAll('.revoke-session-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    hideAlert('sessionsMsg');
                    button.disabled = true;
                    Api.post('sessions.php', {
                        action: 'revoke',
                        session_id: Number(button.dataset.sessionId)
                    }).then(function (data) {
                        if (data.logged_out) {
                            window.location.href = 'login.php';
                            return;
                        }
                        showAlert('sessionsMsg', 'success', data.message);
                        loadSessions();
                    }).catch(function (err) {
                        showAlert('sessionsMsg', 'danger', err.message);
                        button.disabled = false;
                    });
                });
            });
        }).catch(function (err) {
            body.innerHTML = '<tr><td colspan="5" class="text-danger text-center py-3">' + escapeHtml(err.message) + '</td></tr>';
        });
    }

    function loadTeam() {
        var body = document.getElementById('teamBody');
        if (!body) return;

        Api.get('users.php').then(function (data) {
            body.innerHTML = '';
            var members = data.members || [];

            if (!members.length) {
                body.innerHTML = '<tr><td colspan="4" class="text-muted text-center py-3">No team members found.</td></tr>';
                return;
            }

            members.forEach(function (member) {
                var tr = document.createElement('tr');
                var isOwner = member.role === 'owner';
                var roleCell = isOwner
                    ? '<span class="status-badge status-available">Owner</span>'
                    : '<select class="form-select form-select-sm ui-input team-role-select" data-role-user="' + Number(member.user_id) + '">' +
                        '<option value="staff"' + (member.role === 'staff' ? ' selected' : '') + '>Staff</option>' +
                        '<option value="admin"' + (member.role === 'admin' ? ' selected' : '') + '>Admin</option>' +
                      '</select>';

                var actions = isOwner
                    ? '<span class="text-muted" style="font-size:.78rem;">Owner</span>'
                    : '<div class="team-actions justify-content-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-dark save-role-btn" data-user-id="' + Number(member.user_id) + '">Save role</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-dark reset-password-btn" data-user-id="' + Number(member.user_id) + '" data-username="' + escapeHtml(member.username) + '">Reset password</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-user-id="' + Number(member.user_id) + '" data-username="' + escapeHtml(member.username) + '">Remove</button>' +
                      '</div>';

                tr.innerHTML =
                    '<td class="fw-semibold">' + escapeHtml(member.username) + '</td>' +
                    '<td>' + roleCell + '</td>' +
                    '<td class="text-muted" style="font-size:.78rem;">' + escapeHtml(formatDateTime(member.last_login_at)) + '</td>' +
                    '<td class="text-end">' + actions + '</td>';
                body.appendChild(tr);
            });

            body.querySelectorAll('.save-role-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var userId = Number(button.dataset.userId);
                    var select = body.querySelector('[data-role-user="' + userId + '"]');
                    hideAlert('teamMsg');
                    button.disabled = true;
                    Api.post('users.php', {
                        action: 'update_role',
                        user_id: userId,
                        role: select.value
                    }).then(function (data) {
                        showAlert('teamMsg', 'success', data.message);
                    }).catch(function (err) {
                        showAlert('teamMsg', 'danger', err.message);
                    }).then(function () {
                        button.disabled = false;
                    });
                });
            });

            body.querySelectorAll('.reset-password-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('resetUserId').value = button.dataset.userId;
                    document.getElementById('resetPasswordUser').textContent = button.dataset.username;
                    document.getElementById('resetNewPassword').value = '';
                    document.getElementById('resetConfirmPassword').value = '';
                    hideAlert('resetPasswordMsg');
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('resetPasswordModal')).show();
                });
            });

            body.querySelectorAll('.remove-member-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!window.confirm('Remove ' + button.dataset.username + ' from this business?')) return;
                    hideAlert('teamMsg');
                    button.disabled = true;
                    Api.post('users.php', {
                        action: 'remove',
                        user_id: Number(button.dataset.userId)
                    }).then(function (data) {
                        showAlert('teamMsg', 'success', data.message);
                        loadTeam();
                    }).catch(function (err) {
                        showAlert('teamMsg', 'danger', err.message);
                        button.disabled = false;
                    });
                });
            });
        }).catch(function (err) {
            body.innerHTML = '<tr><td colspan="4" class="text-danger text-center py-3">' + escapeHtml(err.message) + '</td></tr>';
        });
    }

    // tabs
    document.querySelectorAll('[data-settings-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.dataset.settingsTab;
            document.querySelectorAll('[data-settings-tab]').forEach(function (item) {
                item.classList.toggle('active', item === button);
            });
            document.querySelectorAll('.settings-panel').forEach(function (panel) {
                panel.classList.toggle('active', panel.id === 'tab-' + target);
            });

            if (target === 'team' && !teamLoaded) {
                teamLoaded = true;
                loadTeam();
            }
            if (target === 'sessions' && !sessionsLoaded) {
                sessionsLoaded = true;
                loadSessions();
            }
            if (target === 'businesses' && !businessesLoaded) {
                businessesLoaded = true;
                loadBusinesses();
            }
        });
    });

    // sign out other sessions
    var revokeAllSessionsBtn = document.getElementById('revokeAllSessionsBtn');
    if (revokeAllSessionsBtn) {
        revokeAllSessionsBtn.addEventListener('click', function () {
            hideAlert('sessionsMsg');
            revokeAllSessionsBtn.disabled = true;
            Api.post('sessions.php', { action: 'revoke_all' }).then(function (data) {
                showAlert('sessionsMsg', 'success', data.message);
                loadSessions();
            }).catch(function (err) {
                showAlert('sessionsMsg', 'danger', err.message);
            }).then(function () {
                revokeAllSessionsBtn.disabled = false;
            });
        });
    }

    // password form
    document.getElementById('passwordForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var current = document.getElementById('currentPassword').value;
        var next = document.getElementById('newPassword').value;
        var confirm = document.getElementById('confirmNewPassword').value;
        var button = document.getElementById('passwordBtn');

        hideAlert('securityMsg');
        if (!current || !next || !confirm) {
            showAlert('securityMsg', 'danger', 'Please fill in all password fields.');
            return;
        }
        if (next.length < 8) {
            showAlert('securityMsg', 'danger', 'The new password must be at least 8 characters.');
            return;
        }
        if (next !== confirm) {
            showAlert('securityMsg', 'danger', 'The new passwords do not match.');
            return;
        }

        button.disabled = true;
        Api.post('users.php', {
            action: 'change_password',
            current_password: current,
            new_password: next,
            confirm_password: confirm
        }).then(function (data) {
            document.getElementById('passwordForm').reset();
            showAlert('securityMsg', 'success', data.message);
        }).catch(function (err) {
            showAlert('securityMsg', 'danger', err.message);
        }).then(function () {
            button.disabled = false;
        });
    });

    // add member
    var addMemberForm = document.getElementById('addMemberForm');
    if (addMemberForm) {
        addMemberForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var username = document.getElementById('memberUsername').value.trim();
            var password = document.getElementById('memberPassword').value;
            var confirm = document.getElementById('memberConfirmPassword').value;
            var role = document.getElementById('memberRole').value;
            var button = document.getElementById('addMemberBtn');

            hideAlert('teamMsg');
            if (!username || !password || !confirm) {
                showAlert('teamMsg', 'danger', 'Please fill in all member fields.');
                return;
            }
            if (password.length < 8) {
                showAlert('teamMsg', 'danger', 'The password must be at least 8 characters.');
                return;
            }
            if (password !== confirm) {
                showAlert('teamMsg', 'danger', 'The passwords do not match.');
                return;
            }

            button.disabled = true;
            Api.post('users.php', {
                action: 'create',
                username: username,
                password: password,
                confirm_password: confirm,
                role: role
            }).then(function (data) {
                addMemberForm.reset();
                showAlert('teamMsg', 'success', data.message);
                loadTeam();
            }).catch(function (err) {
                showAlert('teamMsg', 'danger', err.message);
            }).then(function () {
                button.disabled = false;
            });
        });
    }

    // reset password modal
    document.getElementById('resetPasswordForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var userId = Number(document.getElementById('resetUserId').value);
        var next = document.getElementById('resetNewPassword').value;
        var confirm = document.getElementById('resetConfirmPassword').value;
        var button = document.getElementById('resetPasswordBtn');

        hideAlert('resetPasswordMsg');
        if (next.length < 8) {
            showAlert('resetPasswordMsg', 'danger', 'The password must be at least 8 characters.');
            return;
        }
        if (next !== confirm) {
            showAlert('resetPasswordMsg', 'danger', 'The passwords do not match.');
            return;
        }

        button.disabled = true;
        Api.post('users.php', {
            action: 'reset_password',
            user_id: userId,
            new_password: next,
            confirm_password: confirm
        }).then(function (data) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resetPasswordModal')).hide();
            showAlert('teamMsg', 'success', data.message);
            loadTeam();
        }).catch(function (err) {
            showAlert('resetPasswordMsg', 'danger', err.message);
        }).then(function () {
            button.disabled = false;
        });
    });

    // create business
    document.getElementById('createBusinessForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var input = document.getElementById('businessNameInput');
        var name = input.value.trim();
        var button = document.getElementById('createBusinessBtn');

        hideAlert('businessMsg');
        if (!name) {
            showAlert('businessMsg', 'danger', 'Please enter a business name.');
            return;
        }

        button.disabled = true;
        Api.post('businesses.php', { action: 'create', name: name }).then(function () {
            window.location.reload();
        }).catch(function (err) {
            showAlert('businessMsg', 'danger', err.message);
            button.disabled = false;
        });
    });

    loadProfile();
    if (window.APP_USER && Number(window.APP_USER.mustChangePassword) === 1) {
        var securityTab = document.querySelector('[data-settings-tab="security"]');
        if (securityTab) securityTab.click();
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
