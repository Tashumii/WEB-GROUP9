<?php
$pageTitle = $pageTitle ?? 'Settings';
$pageSubtitle = $pageSubtitle ?? 'Manage your account, security, team, and businesses';
$activePage = $activePage ?? 'settings';
$usernameRaw = (string) ($currentUsername ?? 'User');
$roleKey = (string) ($currentRoleKey ?? '');
$roleLabel = ucfirst($roleKey);
$initial = htmlspecialchars(strtoupper(substr($usernameRaw, 0, 2)));
$isOwner = $roleKey === 'owner';
?>

<?php include APPPATH . 'Views/includes/header.php'; ?>

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

<div class="settings-tabs" role="tablist" aria-label="Settings sections">
    <button type="button" class="btn btn-pill active" id="tabBtn-profile" role="tab" aria-selected="true" aria-controls="tab-profile" data-settings-tab="profile">Profile</button>
    <button type="button" class="btn btn-pill" id="tabBtn-security" role="tab" aria-selected="false" aria-controls="tab-security" tabindex="-1" data-settings-tab="security">Security</button>
    <button type="button" class="btn btn-pill" id="tabBtn-sessions" role="tab" aria-selected="false" aria-controls="tab-sessions" tabindex="-1" data-settings-tab="sessions">Sessions</button>
    <?php if ($isOwner): ?>
        <button type="button" class="btn btn-pill" id="tabBtn-team" role="tab" aria-selected="false" aria-controls="tab-team" tabindex="-1" data-settings-tab="team">Team</button>
    <?php endif; ?>
    <button type="button" class="btn btn-pill" id="tabBtn-businesses" role="tab" aria-selected="false" aria-controls="tab-businesses" tabindex="-1" data-settings-tab="businesses">Businesses</button>
</div>

<!-- profile -->
<section id="tab-profile" class="settings-panel active" role="tabpanel" aria-labelledby="tabBtn-profile" tabindex="0">
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

            <hr class="my-4">

            <form id="emailForm" class="row g-3" novalidate>
                <div class="col-md-8">
                    <label class="field-label" for="profileEmail">Email address <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="email" id="profileEmail" class="form-control ui-input" maxlength="254" placeholder="you@example.com" autocomplete="email">
                    <div class="form-text" style="font-size:0.76rem;">Used only to confirm password changes. Leave blank to remove it.</div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn-ui btn-ui-primary w-100" id="emailBtn">Save email</button>
                </div>
                <div class="col-12">
                    <div id="emailMsg" class="alert d-none settings-message" role="alert"></div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- security -->
<section id="tab-security" class="settings-panel" role="tabpanel" aria-labelledby="tabBtn-security" tabindex="0">
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
                <div class="col-12 d-none" id="passwordEmailWrap">
                    <label class="field-label" for="passwordEmail">Account email</label>
                    <input type="email" id="passwordEmail" class="form-control ui-input" maxlength="254" placeholder="Re-enter the email on this account" autocomplete="email">
                    <div class="form-text" style="font-size:0.76rem;">Required because this account has an email on file.</div>
                </div>
                <div class="col-md-6">
                    <label class="field-label" for="passwordTwoFactorCode">Authenticator code <span class="text-muted fw-normal">(if 2FA enabled)</span></label>
                    <input type="text" id="passwordTwoFactorCode" class="form-control ui-input" inputmode="numeric" maxlength="6" placeholder="123456">
                </div>
                <div class="col-md-6">
                    <label class="field-label" for="passwordRecoveryCode">Recovery code <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" id="passwordRecoveryCode" class="form-control ui-input" maxlength="32" placeholder="XXXXX-XXXXX">
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

            <hr class="my-4">

            <h2 class="ui-card-title">Two-factor authentication</h2>
            <p class="ui-card-subtitle">Add a second step using an authenticator app.</p>
            <div id="twoFactorMsg" class="alert d-none settings-message" role="alert"></div>
            <div id="twoFactorStatus" class="mb-3"></div>

            <div id="twoFactorSetup" class="d-none">
                <div id="twoFactorQr" class="mb-3"></div>
                <p class="form-text" style="font-size:0.76rem;">Secret: <code id="twoFactorSecret"></code></p>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="field-label" for="twoFactorCode">Verification code</label>
                        <input type="text" id="twoFactorCode" class="form-control ui-input" inputmode="numeric" maxlength="6" placeholder="123456">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn-ui btn-ui-primary w-100" id="enableTwoFactorBtn">Enable 2FA</button>
                    </div>
                </div>
            </div>

            <div id="recoveryCodesBox" class="d-none mb-3">
                <p class="fw-semibold mb-2">Save these recovery codes somewhere safe. Each can be used once.</p>
                <pre id="recoveryCodesList" class="p-3 bg-light border rounded" style="font-size:0.8rem;"></pre>
            </div>

            <div id="twoFactorActions" class="d-flex flex-wrap gap-2">
                <button type="button" class="btn-ui btn-ui-outline" id="startTwoFactorBtn">Set up 2FA</button>
                <button type="button" class="btn-ui btn-ui-outline d-none" id="regenerateRecoveryBtn">Regenerate recovery codes</button>
            </div>

            <div id="twoFactorDisableBox" class="d-none mt-3">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="field-label" for="disableTwoFactorPassword">Current password</label>
                        <input type="password" id="disableTwoFactorPassword" class="form-control ui-input" autocomplete="current-password">
                    </div>
                    <div class="col-md-4">
                        <label class="field-label" for="disableTwoFactorCode">Authenticator code</label>
                        <input type="text" id="disableTwoFactorCode" class="form-control ui-input" inputmode="numeric" maxlength="6" placeholder="123456">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn-ui btn-ui-danger w-100" id="disableTwoFactorBtn">Disable 2FA</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- sessions -->
<section id="tab-sessions" class="settings-panel" role="tabpanel" aria-labelledby="tabBtn-sessions" tabindex="0">
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
<section id="tab-team" class="settings-panel" role="tabpanel" aria-labelledby="tabBtn-team" tabindex="0">
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
<section id="tab-businesses" class="settings-panel" role="tabpanel" aria-labelledby="tabBtn-businesses" tabindex="0">
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
    var accountEmail = '';

    function applyAccountEmail() {
        var profileEmail = document.getElementById('profileEmail');
        var emailWrap = document.getElementById('passwordEmailWrap');
        var passwordEmail = document.getElementById('passwordEmail');

        if (profileEmail) profileEmail.value = accountEmail;
        if (emailWrap) emailWrap.classList.toggle('d-none', accountEmail === '');
        if (passwordEmail) {
            passwordEmail.value = '';
            passwordEmail.required = accountEmail !== '';
        }
    }

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
        Api.get('auth').then(function (data) {
            if (!data.authenticated) return;

            accountEmail = String((data.user && data.user.email) || '');
            applyAccountEmail();

            var active = null;
            (data.businesses || []).forEach(function (business) {
                if (Number(business.id) === Number(data.active_business_id)) active = business;
            });

            if (active) {
                document.getElementById('profileBusiness').textContent = active.name;
                document.getElementById('profileBusinessInput').value = active.name;
                document.getElementById('profileRoleLabel').textContent = roleLabels[active.role] || active.role;
                document.getElementById('profileRole').value = roleLabels[active.role] || active.role;
            } else {
                document.getElementById('profileBusiness').textContent = 'No business yet';
                document.getElementById('profileBusinessInput').value = 'No business yet';
                document.getElementById('profileRoleLabel').textContent = 'No role yet';
                document.getElementById('profileRole').value = 'No role yet';
            }
        }).catch(function (err) {
            showAlert('pageMessage', 'danger', err.message);
        });
    }

    function loadBusinesses() {
        Api.get('businesses').then(function (data) {
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
                    Api.post('auth', {
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

        Api.get('sessions').then(function (data) {
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
                    Api.post('sessions', {
                        action: 'revoke',
                        session_id: Number(button.dataset.sessionId)
                    }).then(function (data) {
                        if (data.logged_out) {
                            window.location.href = '<?= site_url('login') ?>';
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

        Api.get('users').then(function (data) {
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
                    Api.post('users', {
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
                    Api.post('users', {
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
    function activateSettingsTab(button, updateHash) {
        if (!button) return;
        var target = button.dataset.settingsTab;

        document.querySelectorAll('[data-settings-tab]').forEach(function (item) {
            var selected = item === button;
            item.classList.toggle('active', selected);
            item.setAttribute('aria-selected', selected ? 'true' : 'false');
            item.setAttribute('tabindex', selected ? '0' : '-1');
        });
        document.querySelectorAll('.settings-panel').forEach(function (panel) {
            var selected = panel.id === 'tab-' + target;
            panel.classList.toggle('active', selected);
            panel.hidden = !selected;
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

        if (updateHash) {
            try {
                history.replaceState(null, '', '#' + target);
            } catch (e) {}
        }
    }

    document.querySelectorAll('[data-settings-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            activateSettingsTab(button, true);
        });
    });

    var settingsTabList = document.querySelector('.settings-tabs');
    if (settingsTabList) {
        settingsTabList.addEventListener('keydown', function (event) {
            var keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
            if (keys.indexOf(event.key) === -1) return;

            var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-settings-tab]'));
            var index = buttons.indexOf(document.activeElement);
            if (index === -1) return;

            event.preventDefault();
            var nextIndex = index;
            if (event.key === 'ArrowLeft') nextIndex = (index - 1 + buttons.length) % buttons.length;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % buttons.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = buttons.length - 1;

            buttons[nextIndex].focus();
            buttons[nextIndex].click();
        });
    }

    // sign out other sessions
    var revokeAllSessionsBtn = document.getElementById('revokeAllSessionsBtn');
    if (revokeAllSessionsBtn) {
        revokeAllSessionsBtn.addEventListener('click', function () {
            hideAlert('sessionsMsg');
            revokeAllSessionsBtn.disabled = true;
            Api.post('sessions', { action: 'revoke_all' }).then(function (data) {
                showAlert('sessionsMsg', 'success', data.message);
                loadSessions();
            }).catch(function (err) {
                showAlert('sessionsMsg', 'danger', err.message);
            }).then(function () {
                revokeAllSessionsBtn.disabled = false;
            });
        });
    }

    // email form
    var emailForm = document.getElementById('emailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var input = document.getElementById('profileEmail');
            var email = input.value.trim();
            var button = document.getElementById('emailBtn');

            hideAlert('emailMsg');
            if (email !== '' && !input.checkValidity()) {
                showAlert('emailMsg', 'danger', 'Enter a valid email address (for example, name@example.com).');
                input.focus();
                return;
            }

            button.disabled = true;
            Api.post('users', {
                action: 'update_email',
                email: email
            }).then(function (data) {
                accountEmail = String(data.email || '');
                applyAccountEmail();
                showAlert('emailMsg', 'success', data.message);
            }).catch(function (err) {
                showAlert('emailMsg', 'danger', err.message);
            }).then(function () {
                button.disabled = false;
            });
        });
    }

    // password form
    document.getElementById('passwordForm').addEventListener('submit', function (event) {
        event.preventDefault();
        var current = document.getElementById('currentPassword').value;
        var next = document.getElementById('newPassword').value;
        var confirm = document.getElementById('confirmNewPassword').value;
        var emailInput = document.getElementById('passwordEmail');
        var email = emailInput ? emailInput.value.trim() : '';
        var twoFactorInput = document.getElementById('passwordTwoFactorCode');
        var recoveryInput = document.getElementById('passwordRecoveryCode');
        var twoFactorCode = twoFactorInput ? twoFactorInput.value.trim() : '';
        var recoveryCode = recoveryInput ? recoveryInput.value.trim() : '';
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
        if (next === current) {
            showAlert('securityMsg', 'danger', 'The new password must be different from your current password.');
            return;
        }
        if (next !== confirm) {
            showAlert('securityMsg', 'danger', 'The new passwords do not match.');
            return;
        }
        if (accountEmail !== '') {
            if (email === '') {
                showAlert('securityMsg', 'danger', 'Enter the email address on this account to confirm the change.');
                emailInput.focus();
                return;
            }
            if (emailInput && !emailInput.checkValidity()) {
                showAlert('securityMsg', 'danger', 'Enter a valid email address.');
                emailInput.focus();
                return;
            }
        }

        button.disabled = true;
        Api.post('users', {
            action: 'change_password',
            current_password: current,
            new_password: next,
            confirm_password: confirm,
            email: email,
            two_factor_code: twoFactorCode,
            recovery_code: recoveryCode
        }).then(function (data) {
            document.getElementById('passwordForm').reset();
            if (emailInput) emailInput.value = '';
            if (twoFactorInput) twoFactorInput.value = '';
            if (recoveryInput) recoveryInput.value = '';
            if (window.APP_USER) window.APP_USER.mustChangePassword = 0;
            showAlert('securityMsg', 'success', data.message);
        }).catch(function (err) {
            showAlert('securityMsg', 'danger', err.message);
        }).then(function () {
            button.disabled = false;
        });
    });

    function showTwoFactorMessage(type, text) {
        showAlert('twoFactorMsg', type, text);
    }

    function refreshTwoFactorStatus() {
        Api.post('users', { action: 'two_factor_status' }).then(function (data) {
            var status = document.getElementById('twoFactorStatus');
            var actions = document.getElementById('twoFactorActions');
            var disableBox = document.getElementById('twoFactorDisableBox');
            var regenerateBtn = document.getElementById('regenerateRecoveryBtn');
            var startBtn = document.getElementById('startTwoFactorBtn');
            if (data.enabled) {
                status.innerHTML = '<span class="status-badge status-available">Enabled</span> <span class="text-muted" style="font-size:0.82rem;">Recovery codes remaining: ' + Number(data.recovery_codes_remaining) + '</span>';
                startBtn.classList.add('d-none');
                regenerateBtn.classList.remove('d-none');
                disableBox.classList.remove('d-none');
            } else {
                status.innerHTML = '<span class="status-badge status-low">Disabled</span>';
                startBtn.classList.remove('d-none');
                regenerateBtn.classList.add('d-none');
                disableBox.classList.add('d-none');
                document.getElementById('twoFactorSetup').classList.add('d-none');
            }
        }).catch(function (err) {
            showTwoFactorMessage('danger', err.message);
        });
    }

    var startTwoFactorBtn = document.getElementById('startTwoFactorBtn');
    if (startTwoFactorBtn) {
        startTwoFactorBtn.addEventListener('click', function () {
            hideAlert('twoFactorMsg');
            startTwoFactorBtn.disabled = true;
            Api.post('users', { action: 'two_factor_setup' }).then(function (data) {
                document.getElementById('twoFactorSetup').classList.remove('d-none');
                document.getElementById('twoFactorSecret').textContent = data.secret;
                document.getElementById('twoFactorQr').innerHTML = data.qr_svg;
                document.getElementById('twoFactorCode').value = '';
            }).catch(function (err) {
                showTwoFactorMessage('danger', err.message);
            }).then(function () {
                startTwoFactorBtn.disabled = false;
            });
        });
    }

    var enableTwoFactorBtn = document.getElementById('enableTwoFactorBtn');
    if (enableTwoFactorBtn) {
        enableTwoFactorBtn.addEventListener('click', function () {
            hideAlert('twoFactorMsg');
            var code = document.getElementById('twoFactorCode').value.trim();
            enableTwoFactorBtn.disabled = true;
            Api.post('users', { action: 'two_factor_enable', code: code }).then(function (data) {
                document.getElementById('twoFactorSetup').classList.add('d-none');
                var box = document.getElementById('recoveryCodesBox');
                box.classList.remove('d-none');
                document.getElementById('recoveryCodesList').textContent = (data.recovery_codes || []).join('\n');
                showTwoFactorMessage('success', data.message);
                refreshTwoFactorStatus();
            }).catch(function (err) {
                showTwoFactorMessage('danger', err.message);
            }).then(function () {
                enableTwoFactorBtn.disabled = false;
            });
        });
    }

    var disableTwoFactorBtn = document.getElementById('disableTwoFactorBtn');
    if (disableTwoFactorBtn) {
        disableTwoFactorBtn.addEventListener('click', function () {
            hideAlert('twoFactorMsg');
            var password = document.getElementById('disableTwoFactorPassword').value;
            var code = document.getElementById('disableTwoFactorCode').value.trim();
            disableTwoFactorBtn.disabled = true;
            Api.post('users', { action: 'two_factor_disable', password: password, code: code }).then(function (data) {
                document.getElementById('disableTwoFactorPassword').value = '';
                document.getElementById('disableTwoFactorCode').value = '';
                document.getElementById('recoveryCodesBox').classList.add('d-none');
                showTwoFactorMessage('success', data.message);
                refreshTwoFactorStatus();
            }).catch(function (err) {
                showTwoFactorMessage('danger', err.message);
            }).then(function () {
                disableTwoFactorBtn.disabled = false;
            });
        });
    }

    var regenerateRecoveryBtn = document.getElementById('regenerateRecoveryBtn');
    if (regenerateRecoveryBtn) {
        regenerateRecoveryBtn.addEventListener('click', function () {
            hideAlert('twoFactorMsg');
            var password = window.prompt('Enter your current password to regenerate recovery codes:');
            var code = window.prompt('Enter your current authenticator code:');
            if (password === null || code === null) return;
            regenerateRecoveryBtn.disabled = true;
            Api.post('users', { action: 'two_factor_regenerate_recovery', password: password, code: code }).then(function (data) {
                var box = document.getElementById('recoveryCodesBox');
                box.classList.remove('d-none');
                document.getElementById('recoveryCodesList').textContent = (data.recovery_codes || []).join('\n');
                showTwoFactorMessage('success', data.message);
            }).catch(function (err) {
                showTwoFactorMessage('danger', err.message);
            }).then(function () {
                regenerateRecoveryBtn.disabled = false;
            });
        });
    }

    refreshTwoFactorStatus();

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
            Api.post('users', {
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
        Api.post('users', {
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
        Api.post('businesses', { action: 'create', name: name }).then(function () {
            window.location.reload();
        }).catch(function (err) {
            showAlert('businessMsg', 'danger', err.message);
            button.disabled = false;
        });
    });

    var initialTab = (window.location.hash || '').replace(/^#/, '').replace(/[^a-z0-9_-]/gi, '');
    if (initialTab) {
        var initialTabButton = document.querySelector('[data-settings-tab="' + initialTab + '"]');
        if (initialTabButton) activateSettingsTab(initialTabButton, false);
    }

    loadProfile();
    if (window.APP_USER && Number(window.APP_USER.mustChangePassword) === 1) {
        var securityTab = document.querySelector('[data-settings-tab="security"]');
        if (securityTab) securityTab.click();
    }
})();
</script>

<?php include APPPATH . 'Views/includes/footer.php'; ?>
