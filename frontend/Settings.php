<?php

$pageTitle    = 'Settings';
$pageSubtitle = 'Manage your account and preferences';
$activePage   = 'settings';

include 'includes/header.php';
?>

      <!-- ============ Profile card ============ -->
      <section class="ui-card profile-card">
        <!-- Replace with the profile photo: <img src="assets/img/avatar.jpg" alt="" class="avatar avatar-lg"> -->
        <div class="avatar avatar-lg avatar-placeholder">M</div>

        <div class="profile-text">
          <div class="profile-name">Macabante</div>
          <div class="profile-meta">macabante@gmail.com · Owner</div>
        </div>

        <button type="button" class="btn-ui btn-ui-outline">Change photo</button>
      </section>

      <!-- ============ Account details ============ -->
      <section class="ui-card">
        <div class="ui-card-header">
          <h2 class="ui-card-title">Account details</h2>
          <p class="ui-card-subtitle">Visible to your team</p>
        </div>

        <div class="ui-card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="fullName" class="field-label">Full name</label>
              <input type="text" id="fullName" class="form-control ui-input" value="Macabante, Hendrickson">
            </div>
            <div class="col-md-6">
              <label for="email" class="field-label">Email address</label>
              <input type="email" id="email" class="form-control ui-input" value="macabante@gmail.com">
            </div>
            <div class="col-md-6">
              <label for="company" class="field-label">Company</label>
              <input type="text" id="company" class="form-control ui-input" placeholder="Your company name">
            </div>
            <div class="col-md-6">
              <label for="phone" class="field-label">Phone number</label>
              <input type="tel" id="phone" class="form-control ui-input" placeholder="+63 900 000 0000">
            </div>
          </div>
        </div>
      </section>

      <!-- ============ Notification preferences ============ -->
      <section class="ui-card">
        <div class="ui-card-header">
          <h2 class="ui-card-title">Notification preferences</h2>
          <p class="ui-card-subtitle">How you'd like updates delivered</p>
        </div>

        <div class="pref-row">
          <div>
            <div class="pref-label">Email notifications</div>
            <div class="pref-desc">Deal updates, weekly summaries</div>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="prefEmail" aria-label="Email notifications" checked>
          </div>
        </div>

        <div class="pref-row">
          <div>
            <div class="pref-label">SMS alerts</div>
            <div class="pref-desc">Urgent updates about deals and low stock</div>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="prefSms" aria-label="SMS alerts">
          </div>
        </div>

        <div class="pref-row">
          <div>
            <div class="pref-label">Weekly summary report</div>
            <div class="pref-desc">A recap of revenue and pipeline every Monday</div>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="prefWeekly" aria-label="Weekly summary report" checked>
          </div>
        </div>

        <div class="pref-row">
          <div>
            <div class="pref-label">Dark mode</div>
            <div class="pref-desc">Use a darker interface across the dashboard</div>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="prefDark" aria-label="Dark mode">
          </div>
        </div>
      </section>

      <!-- ============ Save bar ============ -->
      <div class="save-bar">
        <button type="button" class="btn-ui btn-ui-outline">Cancel</button>
        <button type="button" class="btn-ui btn-ui-primary">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 8.5 6.5 12 13 4.5"/></svg>
          Save changes
        </button>
      </div>

      <?php include 'includes/footer.php'; ?>