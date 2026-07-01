<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/auth.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    if (TRACKER_TOKEN && hash_equals(TRACKER_TOKEN, $_POST['token'])) {
        set_session_cookie();
        header('Location: /tracker/');
        exit;
    }
    $login_error = true;
}

$authed = check_session();
?>
<!DOCTYPE html>
<html lang="en" data-brand="minotaur" data-scheme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Job Search Navigator — by Richard Lee</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/tracker/css/tokens.primitives.css">
  <link rel="stylesheet" href="/tracker/css/brand.minotaur.css">
  <link rel="stylesheet" href="/tracker/css/components.css">
  <link rel="stylesheet" href="/tracker/css/tracker-dark.css">
</head>
<body>

<?php if (!$authed): ?>
<!-- ── Auth gate ── -->
<div class="auth-gate">
  <div class="auth-card">
    <h1>Job Tracker</h1>
    <p>Minotaur Design · private</p>
    <?php if (!empty($login_error)): ?>
      <p style="color:#f87171;font-size:var(--text-xs);margin-bottom:var(--space-4)">Wrong token.</p>
    <?php endif; ?>
    <form method="POST">
      <div style="margin-bottom:var(--space-4)">
        <label for="token">Access token</label>
        <input type="password" id="token" name="token" autofocus autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-save" style="width:100%">Enter</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ── Authenticated app ── -->

<nav class="app-nav">
  <div class="app-nav__branding">
    <div class="app-nav__wordmark">Job Search Navigator</div>
    <div class="app-nav__byline">Organize. Discover. Coach.</div>
  </div>
  <div class="app-nav__tabs">
    <button class="nav-tab active" data-tab="applications">Applications</button>
    <button class="nav-tab" data-tab="organizations">Organizations</button>
    <button class="nav-tab" data-tab="contacts">Contacts</button>
    <button class="nav-tab" data-tab="discoveries">Discoveries</button>
    <button class="nav-tab" data-tab="brief">Daily Brief</button>
    <button class="nav-tab" data-tab="coaching">Coaching</button>
  </div>
</nav>

<main class="app-content">

  <!-- ── Applications tab ── -->
  <div class="tab-panel active" id="tab-applications">
    <div class="stats-bar">
      <div style="display:flex;gap:var(--space-2);align-items:flex-end">
        <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0" id="app-heading">Applications</h2>
        <span style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--fg-muted);margin-bottom:3px" id="last-sweep"></span>
      </div>
      <div style="display:flex;gap:var(--space-6)">
        <div class="stat-block"><div class="stat-block__num" id="stat-total">—</div><div class="stat-block__label">Total</div></div>
        <div class="stat-block"><div class="stat-block__num" id="stat-active" style="color:#4ade80">—</div><div class="stat-block__label">Active</div></div>
        <div class="stat-block"><div class="stat-block__num" id="stat-screen" style="color:var(--prim-mahog-300)">—</div><div class="stat-block__label">Screens</div></div>
        <div class="stat-block"><div class="stat-block__num" id="stat-no" style="color:#f87171">—</div><div class="stat-block__label">No</div></div>
      </div>
    </div>

    <div class="controls">
      <button class="filter-btn active" data-filter="all">All</button>
      <button class="filter-btn" data-filter="active">Active</button>
      <button class="filter-btn" data-filter="screen">Screens</button>
      <button class="filter-btn" data-filter="interview">Interview</button>
      <button class="filter-btn" data-filter="no">Rejected</button>
      <button class="filter-btn" data-filter="hold">Hold</button>
      <button class="action-btn-primary" id="btn-add-app">+ Add Application</button>
    </div>

    <div class="table-wrap">
      <table id="app-table">
        <thead><tr>
          <th style="width:36px;text-align:center">#</th>
          <th data-sort="company_name">Company</th>
          <th data-sort="role_title">Role</th>
          <th data-sort="date_applied">Applied</th>
          <th data-sort="status">Status</th>
          <th data-sort="fit">Fit</th>
          <th class="notes-col">Notes</th>
          <th></th>
        </tr></thead>
        <tbody id="app-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ── Organizations tab ── -->
  <div class="tab-panel" id="tab-organizations">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-6)">
      <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0">Organizations</h2>
      <button class="action-btn-primary" id="btn-add-org">+ Add Org</button>
    </div>
    <div class="controls" style="margin-bottom:var(--space-6)">
      <button class="filter-btn active" data-org-filter="all">All</button>
      <button class="filter-btn" data-org-filter="high">High fit</button>
      <button class="filter-btn" data-org-filter="med">Med fit</button>
    </div>
    <div class="org-grid" id="org-grid"></div>
  </div>

  <!-- ── Contacts tab ── -->
  <div class="tab-panel" id="tab-contacts">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-6)">
      <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0">Contacts</h2>
      <button class="action-btn-primary" id="btn-add-contact">+ Add Contact</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Org</th>
          <th>Name</th>
          <th>Title</th>
          <th>Email</th>
          <th>LinkedIn</th>
          <th>Type</th>
          <th class="notes-col">Notes</th>
          <th></th>
        </tr></thead>
        <tbody id="contact-tbody"></tbody>
      </table>
    </div>
    <div id="suggestions-section" style="margin-top:var(--space-8)">
      <h3 style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--fg-muted);text-transform:uppercase;letter-spacing:var(--tracking-wider);margin-bottom:var(--space-4)">LinkedIn Search Suggestions</h3>
      <div id="suggestions-list"></div>
    </div>
  </div>

  <!-- ── Discoveries tab ── -->
  <div class="tab-panel" id="tab-discoveries">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-6)">
      <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0">Role Discoveries</h2>
    </div>
    <div class="controls" style="margin-bottom:var(--space-5)">
      <button class="filter-btn active" data-disc-filter="new">New</button>
      <button class="filter-btn" data-disc-filter="all">All</button>
      <button class="filter-btn" data-disc-filter="seen">Seen</button>
      <button class="filter-btn" data-disc-filter="dismissed">Dismissed</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>Company</th>
          <th>Role</th>
          <th>Location</th>
          <th>Source</th>
          <th>Found</th>
          <th>Status</th>
          <th></th>
        </tr></thead>
        <tbody id="disc-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ── Daily Brief tab ── -->
  <div class="tab-panel" id="tab-brief">
    <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0 0 var(--space-6)">Daily Brief</h2>
    <div id="brief-latest"></div>
    <details style="margin-top:var(--space-8)">
      <summary style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--fg-muted);text-transform:uppercase;letter-spacing:var(--tracking-wider);cursor:pointer">Past reports</summary>
      <div id="brief-history" style="margin-top:var(--space-4)"></div>
    </details>
  </div>

  <!-- ── Coaching tab ── -->
  <div class="tab-panel" id="tab-coaching">
    <h2 style="font-family:var(--font-serif);font-weight:400;font-size:var(--text-xl);margin:0 0 var(--space-6)">Coaching</h2>
    <div class="coaching-prompt-area">
      <div class="coaching-presets">
        <button class="preset-btn" data-prompt="What patterns do you see in my rejections?">Rejection patterns</button>
        <button class="preset-btn" data-prompt="What should I de-emphasize in my materials based on what isn't landing?">What to drop</button>
        <button class="preset-btn" data-prompt="What market signals are you seeing from the discovery data — any pivots I should consider?">Market signals</button>
        <button class="preset-btn" data-prompt="Which active applications should I prioritize following up on, and why?">Follow-up priority</button>
        <button class="preset-btn" data-prompt="What's working? What types of roles and orgs seem most responsive?">What's working</button>
      </div>
      <div style="display:flex;gap:var(--space-3);align-items:flex-end">
        <div style="flex:1">
          <label for="coaching-input">Ask something</label>
          <textarea id="coaching-input" rows="3" placeholder="e.g. What does my rejection pattern tell me about how I'm positioning myself?"></textarea>
        </div>
        <button class="action-btn-primary" style="margin-left:0;margin-bottom:1px" id="btn-coaching-submit">Submit</button>
      </div>
    </div>
    <div id="coaching-responses"></div>
  </div>

</main>

<!-- ── Application modal ── -->
<div class="modal-overlay" id="modal-app">
  <div class="modal">
    <h2 id="modal-app-title">Add Application</h2>
    <input type="hidden" id="f-app-id">
    <div class="form-grid">
      <div><label>Company / Org</label>
        <select id="f-app-org"></select>
      </div>
      <div><label>Role Title</label><input type="text" id="f-app-role" placeholder="e.g. Principal Design Ops PM"></div>
      <div><label>Date Applied</label><input type="date" id="f-app-date"></div>
      <div><label>Status</label>
        <select id="f-app-status">
          <option value="active">Active</option>
          <option value="screen">Phone Screen</option>
          <option value="interview">Interview</option>
          <option value="offer">Offer</option>
          <option value="no">No / Rejected</option>
          <option value="canceled">Canceled by Co.</option>
          <option value="hold">Hold</option>
        </select>
      </div>
      <div><label>Fit</label>
        <select id="f-app-fit">
          <option value="high">High</option><option value="med">Medium</option><option value="low">Low</option>
        </select>
      </div>
      <div><label>Resume File</label><input type="text" id="f-app-resume" placeholder="e.g. Company - Role.docx"></div>
      <div><label>Salary Min</label><input type="number" id="f-app-salmin" placeholder="e.g. 150000"></div>
      <div><label>Salary Max</label><input type="number" id="f-app-salmax" placeholder="e.g. 200000"></div>
      <div><label>Location</label><input type="text" id="f-app-location" placeholder="e.g. Remote / Nashville"></div>
      <div style="display:flex;align-items:center;gap:var(--space-3);padding-top:var(--space-5)">
        <input type="checkbox" id="f-app-remote" style="width:auto">
        <label for="f-app-remote" style="text-transform:none;letter-spacing:0;margin:0">Remote OK</label>
        <input type="checkbox" id="f-app-local" style="width:auto;margin-left:var(--space-4)">
        <label for="f-app-local" style="text-transform:none;letter-spacing:0;margin:0">Local (Knoxville)</label>
      </div>
      <div class="form-full"><label>Source URL</label><input type="url" id="f-app-url" placeholder="https://..."></div>
      <div class="form-full"><label>Notes</label><textarea id="f-app-notes" placeholder="Recruiter name, connections, signals..."></textarea></div>
      <div class="form-full"><label>Job Description</label><textarea id="f-app-jd" rows="4" placeholder="Paste JD for context..."></textarea></div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-delete" id="btn-app-delete" style="display:none">Delete</button>
      <button class="btn btn-cancel" onclick="closeModal('modal-app')">Cancel</button>
      <button class="btn btn-save" id="btn-app-save">Save</button>
    </div>
  </div>
</div>

<!-- ── Org modal ── -->
<div class="modal-overlay" id="modal-org">
  <div class="modal">
    <h2 id="modal-org-title">Add Organization</h2>
    <input type="hidden" id="f-org-id">
    <div class="form-grid">
      <div><label>Name</label><input type="text" id="f-org-name"></div>
      <div><label>Domain</label><input type="text" id="f-org-domain" placeholder="e.g. stripe.com"></div>
      <div><label>Industry</label><input type="text" id="f-org-industry" placeholder="e.g. Fintech"></div>
      <div><label>Size Range</label><input type="text" id="f-org-size" placeholder="e.g. 500-2000"></div>
      <div><label>HQ Location</label><input type="text" id="f-org-hq"></div>
      <div><label>Fit Rating</label>
        <select id="f-org-fit">
          <option value="high">High</option><option value="med" selected>Medium</option><option value="low">Low</option>
        </select>
      </div>
      <div><label>Glassdoor URL</label><input type="url" id="f-org-glassdoor"></div>
      <div><label>LinkedIn URL</label><input type="url" id="f-org-linkedin"></div>
      <div class="form-full"><label>Careers URL</label><input type="url" id="f-org-careers" placeholder="https://company.com/careers"></div>
      <div class="form-full"><label>Notes</label><textarea id="f-org-notes"></textarea></div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-cancel" onclick="closeModal('modal-org')">Cancel</button>
      <button class="btn btn-save" id="btn-org-save">Save</button>
    </div>
  </div>
</div>

<!-- ── Contact modal ── -->
<div class="modal-overlay" id="modal-contact">
  <div class="modal">
    <h2>Add Contact</h2>
    <input type="hidden" id="f-contact-id">
    <div class="form-grid">
      <div><label>Organization</label><select id="f-contact-org"></select></div>
      <div><label>Name</label><input type="text" id="f-contact-name"></div>
      <div><label>Title</label><input type="text" id="f-contact-title" placeholder="e.g. Head of Talent Acquisition"></div>
      <div><label>Email</label><input type="text" id="f-contact-email"></div>
      <div class="form-full"><label>LinkedIn URL</label><input type="url" id="f-contact-linkedin"></div>
      <div style="display:flex;align-items:center;gap:var(--space-3);padding-top:var(--space-5)">
        <input type="checkbox" id="f-contact-hm" style="width:auto">
        <label for="f-contact-hm" style="text-transform:none;letter-spacing:0;margin:0">Hiring manager</label>
      </div>
      <div class="form-full"><label>Notes</label><textarea id="f-contact-notes"></textarea></div>
    </div>
    <div class="modal-actions">
      <button class="btn btn-delete" id="btn-contact-delete" style="display:none">Delete</button>
      <button class="btn btn-cancel" onclick="closeModal('modal-contact')">Cancel</button>
      <button class="btn btn-save" id="btn-contact-save">Save</button>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
// ── API helpers ──────────────────────────────────────────────────────────

const api = {
  get:    (url) => fetch(url + (url.includes('?') ? '&' : '?') + 'token=<?= htmlspecialchars(TRACKER_TOKEN, ENT_QUOTES) ?>').then(r => r.json()),
  post:   (url, data) => fetch(url + '?token=<?= htmlspecialchars(TRACKER_TOKEN, ENT_QUOTES) ?>', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)}).then(r => r.json()),
  put:    (url, data) => fetch(url + '?token=<?= htmlspecialchars(TRACKER_TOKEN, ENT_QUOTES) ?>', {method:'PUT',  headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)}).then(r => r.json()),
  del:    (url)       => fetch(url + '?token=<?= htmlspecialchars(TRACKER_TOKEN, ENT_QUOTES) ?>', {method:'DELETE'}).then(r => r.json()),
  patch:  (url, data) => fetch(url + '?token=<?= htmlspecialchars(TRACKER_TOKEN, ENT_QUOTES) ?>', {method:'PATCH',  headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)}).then(r => r.json()),
};

function esc(s) { return String(s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function $id(id) { return document.getElementById(id); }

// ── Tab navigation ───────────────────────────────────────────────────────

<?php if ($authed): ?>
const tabs = document.querySelectorAll('.nav-tab');
const panels = document.querySelectorAll('.tab-panel');
tabs.forEach(t => t.addEventListener('click', () => {
  tabs.forEach(x => x.classList.remove('active'));
  panels.forEach(x => x.classList.remove('active'));
  t.classList.add('active');
  document.getElementById('tab-' + t.dataset.tab).classList.add('active');
  if (t.dataset.tab === 'organizations') loadOrgs();
  if (t.dataset.tab === 'contacts')      { loadContacts(); loadSuggestions(); }
  if (t.dataset.tab === 'discoveries')   loadDiscoveries();
  if (t.dataset.tab === 'brief')         loadBrief();
  if (t.dataset.tab === 'coaching')      loadCoaching();
}));

// ── Applications ─────────────────────────────────────────────────────────

let apps = [], appFilter = 'all', appSort = {key:'date_applied', dir:-1};
const SM = {active:['badge-active','Active'],screen:['badge-screen','Screen'],interview:['badge-interview','Interview'],offer:['badge-offer','Offer'],no:['badge-no','No'],canceled:['badge-canceled','Canceled'],hold:['badge-hold','Hold']};
const FM = {high:'fit-high',med:'fit-med',low:'fit-low'};
const FL = {high:'▲ High',med:'● Med',low:'▼ Low'};

async function loadApps() {
  const res = await api.get('/tracker/api/applications.php');
  apps = res.data || [];
  renderApps();
}

function renderApps() {
  const filtered = appFilter === 'all' ? apps : apps.filter(a => a.status === appFilter);
  const sorted = [...filtered].sort((a,b) => {
    let av = a[appSort.key]||'', bv = b[appSort.key]||'';
    return av < bv ? appSort.dir : av > bv ? -appSort.dir : 0;
  });
  $id('stat-total').textContent = apps.length;
  $id('stat-active').textContent = apps.filter(a=>a.status==='active').length;
  $id('stat-screen').textContent = apps.filter(a=>a.status==='screen'||a.status==='interview').length;
  $id('stat-no').textContent     = apps.filter(a=>a.status==='no').length;

  const tbody = $id('app-tbody');
  if (!sorted.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No applications in this filter.</td></tr>'; return; }
  tbody.innerHTML = sorted.map((a, i) => {
    const [bc,bl] = SM[a.status]||['badge-hold',a.status];
    const pin = a.is_local=='1' ? ' <span title="Local Knoxville" style="font-size:10px">📍</span>' : '';
    return `<tr>
      <td style="text-align:center;font-family:var(--font-mono);font-size:11px;color:var(--fg-muted)">${i+1}</td>
      <td><div class="cell-company">${esc(a.company_name||'')}${pin}</div></td>
      <td><div class="cell-role">${esc(a.role_title)}</div></td>
      <td><span class="cell-date">${esc(a.date_applied)||'—'}</span></td>
      <td><span class="badge ${bc}">${esc(bl)}</span></td>
      <td><span class="fit ${FM[a.fit]||''}">${FL[a.fit]||'—'}</span></td>
      <td class="notes-col"><span class="notes-text" title="${esc(a.notes||'')}">${esc(a.notes)||'—'}</span></td>
      <td><button class="icon-btn" onclick="openAppModal(${a.id})">✎</button></td>
    </tr>`;
  }).join('');
}

document.querySelectorAll('.filter-btn[data-filter]').forEach(b => b.addEventListener('click', function() {
  document.querySelectorAll('.filter-btn[data-filter]').forEach(x=>x.classList.remove('active'));
  this.classList.add('active');
  appFilter = this.dataset.filter;
  renderApps();
}));

document.querySelectorAll('th[data-sort]').forEach(th => th.addEventListener('click', function() {
  const k = this.dataset.sort;
  if (appSort.key === k) appSort.dir *= -1; else { appSort.key = k; appSort.dir = -1; }
  renderApps();
}));

// ── App modal ────────────────────────────────────────────────────────────

let orgsCache = [];
async function populateOrgSelects() {
  if (!orgsCache.length) {
    const r = await api.get('/tracker/api/organizations.php');
    orgsCache = r.data || [];
  }
  ['f-app-org','f-contact-org'].forEach(id => {
    const sel = $id(id);
    if (!sel) return;
    sel.innerHTML = '<option value="">— select org —</option>' +
      orgsCache.map(o => `<option value="${o.id}">${esc(o.name)}</option>`).join('');
  });
}

function openAppModal(id) {
  populateOrgSelects();
  $id('f-app-id').value = id || '';
  $id('modal-app-title').textContent = id ? 'Edit Application' : 'Add Application';
  $id('btn-app-delete').style.display = id ? 'block' : 'none';
  if (id) {
    const a = apps.find(x => x.id == id);
    if (!a) return;
    $id('f-app-org').value      = a.org_id || '';
    $id('f-app-role').value     = a.role_title || '';
    $id('f-app-date').value     = a.date_applied || '';
    $id('f-app-status').value   = a.status || 'active';
    $id('f-app-fit').value      = a.fit || 'med';
    $id('f-app-resume').value   = a.resume_file || '';
    $id('f-app-url').value      = a.source_url || '';
    $id('f-app-salmin').value   = a.salary_min || '';
    $id('f-app-salmax').value   = a.salary_max || '';
    $id('f-app-location').value = a.location || '';
    $id('f-app-remote').checked = a.remote_ok == '1';
    $id('f-app-local').checked  = a.is_local  == '1';
    $id('f-app-notes').value    = a.notes || '';
    $id('f-app-jd').value       = a.job_description || '';
  } else {
    ['f-app-role','f-app-resume','f-app-url','f-app-notes','f-app-jd','f-app-location'].forEach(i => $id(i).value = '');
    $id('f-app-org').value    = '';
    $id('f-app-date').value   = new Date().toISOString().split('T')[0];
    $id('f-app-status').value = 'active';
    $id('f-app-fit').value    = 'med';
    $id('f-app-salmin').value = $id('f-app-salmax').value = '';
    $id('f-app-remote').checked = $id('f-app-local').checked = false;
  }
  $id('modal-app').classList.add('open');
}

$id('btn-add-app').onclick = () => openAppModal(null);

$id('btn-app-save').onclick = async () => {
  const id = $id('f-app-id').value;
  const data = {
    org_id: $id('f-app-org').value || null,
    role_title:      $id('f-app-role').value.trim(),
    date_applied:    $id('f-app-date').value || null,
    status:          $id('f-app-status').value,
    fit:             $id('f-app-fit').value,
    resume_file:     $id('f-app-resume').value.trim(),
    source_url:      $id('f-app-url').value.trim(),
    job_description: $id('f-app-jd').value.trim(),
    salary_min:      $id('f-app-salmin').value || null,
    salary_max:      $id('f-app-salmax').value || null,
    location:        $id('f-app-location').value.trim(),
    remote_ok:       $id('f-app-remote').checked ? 1 : 0,
    notes:           $id('f-app-notes').value.trim(),
    is_local:        $id('f-app-local').checked ? 1 : 0,
  };
  if (!data.role_title) return;
  if (id) await api.put('/tracker/api/applications.php?id=' + id, data);
  else    await api.post('/tracker/api/applications.php', data);
  closeModal('modal-app');
  await loadApps();
};

$id('btn-app-delete').onclick = async () => {
  const id = $id('f-app-id').value;
  if (!id || !confirm('Delete this application?')) return;
  await api.del('/tracker/api/applications.php?id=' + id);
  closeModal('modal-app');
  await loadApps();
};

// ── Organizations ────────────────────────────────────────────────────────

let orgs = [], orgFilter = 'all';

async function loadOrgs() {
  const r = await api.get('/tracker/api/organizations.php');
  orgs = r.data || [];
  orgsCache = orgs;
  renderOrgs();
}

function renderOrgs() {
  const filtered = orgFilter === 'all' ? orgs : orgs.filter(o => o.fit_rating === orgFilter);
  const FR = {high:'▲',med:'●',low:'▼'};
  $id('org-grid').innerHTML = filtered.length
    ? filtered.map(o => `
      <div class="org-card" onclick="openOrgModal(${o.id})">
        <div class="org-card__name">${esc(o.name)}</div>
        <div class="org-card__meta">${esc(o.industry||'')}${o.size_range?' · '+esc(o.size_range):''}</div>
        <div class="org-card__counts">
          ${o.app_count} app${o.app_count!=1?'s':''} · ${o.active_count} active · ${o.rejected_count} rejected
          ${FR[o.fit_rating]?` · <span class="fit fit-${o.fit_rating}">${FR[o.fit_rating]} ${o.fit_rating}</span>`:''}
        </div>
      </div>`).join('')
    : '<div class="empty-state">No organizations.</div>';
}

document.querySelectorAll('[data-org-filter]').forEach(b => b.addEventListener('click', function() {
  document.querySelectorAll('[data-org-filter]').forEach(x=>x.classList.remove('active'));
  this.classList.add('active');
  orgFilter = this.dataset.orgFilter;
  renderOrgs();
}));

function openOrgModal(id) {
  $id('f-org-id').value = id || '';
  $id('modal-org-title').textContent = id ? 'Edit Organization' : 'Add Organization';
  if (id) {
    const o = orgs.find(x => x.id == id);
    if (!o) return;
    $id('f-org-name').value      = o.name || '';
    $id('f-org-domain').value    = o.domain || '';
    $id('f-org-industry').value  = o.industry || '';
    $id('f-org-size').value      = o.size_range || '';
    $id('f-org-hq').value        = o.hq_location || '';
    $id('f-org-fit').value       = o.fit_rating || 'med';
    $id('f-org-glassdoor').value = o.glassdoor_url || '';
    $id('f-org-linkedin').value  = o.linkedin_url || '';
    $id('f-org-careers').value   = o.careers_url || '';
    $id('f-org-notes').value     = o.notes || '';
  } else {
    ['f-org-name','f-org-domain','f-org-industry','f-org-size','f-org-hq',
     'f-org-glassdoor','f-org-linkedin','f-org-careers','f-org-notes'].forEach(i => $id(i).value = '');
    $id('f-org-fit').value = 'med';
  }
  $id('modal-org').classList.add('open');
}

$id('btn-add-org').onclick = () => openOrgModal(null);

$id('btn-org-save').onclick = async () => {
  const id = $id('f-org-id').value;
  const data = {
    name:         $id('f-org-name').value.trim(),
    domain:       $id('f-org-domain').value.trim(),
    industry:     $id('f-org-industry').value.trim(),
    size_range:   $id('f-org-size').value.trim(),
    hq_location:  $id('f-org-hq').value.trim(),
    fit_rating:   $id('f-org-fit').value,
    glassdoor_url:$id('f-org-glassdoor').value.trim(),
    linkedin_url: $id('f-org-linkedin').value.trim(),
    careers_url:  $id('f-org-careers').value.trim(),
    notes:        $id('f-org-notes').value.trim(),
  };
  if (!data.name) return;
  if (id) await api.put('/tracker/api/organizations.php?id=' + id, data);
  else    await api.post('/tracker/api/organizations.php', data);
  closeModal('modal-org');
  orgsCache = [];
  await loadOrgs();
};

// ── Contacts ─────────────────────────────────────────────────────────────

async function loadContacts() {
  const r = await api.get('/tracker/api/contacts.php');
  const rows = r.data || [];
  const tbody = $id('contact-tbody');
  tbody.innerHTML = rows.length
    ? rows.map(c => `<tr>
        <td>${esc(c.org_name)}</td>
        <td style="font-weight:500">${esc(c.name)}</td>
        <td>${esc(c.title||'')}</td>
        <td>${c.email ? `<a href="mailto:${esc(c.email)}" style="color:var(--prim-mahog-300)">${esc(c.email)}</a>` : '—'}</td>
        <td>${c.linkedin_url ? `<a href="${esc(c.linkedin_url)}" target="_blank" rel="noopener" style="color:var(--prim-mahog-300)">LinkedIn</a>` : '—'}</td>
        <td>${c.is_hiring_manager=='1'?'<span class="badge badge-screen">HM</span>':'HR'}</td>
        <td class="notes-col"><span class="notes-text">${esc(c.notes||'')}</span></td>
        <td><button class="icon-btn" onclick="deleteContact(${c.id})">✕</button></td>
      </tr>`).join('')
    : '<tr><td colspan="8" class="empty-state">No contacts yet.</td></tr>';
}

async function loadSuggestions() {
  const r = await api.get('/tracker/api/contacts.php?suggestions=1');
  const rows = r.data || [];
  $id('suggestions-list').innerHTML = rows.length
    ? rows.map(o => {
        const titles = o.titles ? JSON.parse(o.titles) : [];
        return `<div style="margin-bottom:var(--space-4);padding:var(--space-4);background:var(--card-bg);border:1px solid var(--border-default);border-radius:var(--radius-card)">
          <div style="font-weight:500;margin-bottom:var(--space-2)">${esc(o.name)}</div>
          <div style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--fg-muted)">
            ${titles.length ? 'Search LinkedIn for: ' + titles.map(t=>`<strong style="color:var(--fg-secondary)">${esc(t)}</strong>`).join(', ') : 'Suggestions pending from agent'}
          </div>
        </div>`;
      }).join('')
    : '<div class="empty-state" style="padding:var(--space-6)">All organizations have contacts, or no suggestions yet from the weekly agent.</div>';
}

$id('btn-add-contact').onclick = () => {
  populateOrgSelects();
  $id('f-contact-id').value = '';
  ['f-contact-name','f-contact-title','f-contact-email','f-contact-linkedin','f-contact-notes'].forEach(i => $id(i).value = '');
  $id('f-contact-org').value = '';
  $id('f-contact-hm').checked = false;
  $id('btn-contact-delete').style.display = 'none';
  $id('modal-contact').classList.add('open');
};

$id('btn-contact-save').onclick = async () => {
  const data = {
    org_id:           $id('f-contact-org').value,
    name:             $id('f-contact-name').value.trim(),
    title:            $id('f-contact-title').value.trim(),
    email:            $id('f-contact-email').value.trim(),
    linkedin_url:     $id('f-contact-linkedin').value.trim(),
    is_hiring_manager:$id('f-contact-hm').checked ? 1 : 0,
    notes:            $id('f-contact-notes').value.trim(),
  };
  if (!data.org_id || !data.name) return;
  await api.post('/tracker/api/contacts.php', data);
  closeModal('modal-contact');
  loadContacts();
};

async function deleteContact(id) {
  if (!confirm('Delete this contact?')) return;
  await api.del('/tracker/api/contacts.php?id=' + id);
  loadContacts();
}

// ── Discoveries ──────────────────────────────────────────────────────────

let discFilter = 'new';

async function loadDiscoveries() {
  const url = discFilter === 'all'
    ? '/tracker/api/discoveries.php'
    : '/tracker/api/discoveries.php?status=' + discFilter;
  const r = await api.get(url);
  const rows = r.data || [];
  const tbody = $id('disc-tbody');
  tbody.innerHTML = rows.length
    ? rows.map(d => `<tr class="${d.status==='new'?'discovery-new':''}">
        <td>${esc(d.org_name||d.company_name||'')}</td>
        <td>${d.url ? `<a href="${esc(d.url)}" target="_blank" rel="noopener" style="color:var(--prim-mahog-300)">${esc(d.title)}</a>` : esc(d.title)}</td>
        <td>${esc(d.location||'')}${d.remote_ok=='1'?' 🌐':''}</td>
        <td><span class="badge badge-hold">${esc(d.source)}</span></td>
        <td class="cell-date">${esc(d.discovered_at?.split(' ')[0]||'')}</td>
        <td><span class="badge badge-${d.status==='new'?'screen':d.status==='applied'?'active':'hold'}">${esc(d.status)}</span></td>
        <td style="display:flex;gap:4px">
          ${d.status!=='applied'?`<button class="icon-btn" title="Promote to application" onclick="promoteDiscovery(${d.id}, '${esc(d.org_id||'')}', '${esc(d.title)}')">+</button>`:''}
          ${d.status!=='dismissed'?`<button class="icon-btn" title="Dismiss" onclick="dismissDiscovery(${d.id})">✕</button>`:''}
        </td>
      </tr>`).join('')
    : '<tr><td colspan="7" class="empty-state">No discoveries in this filter.</td></tr>';
}

document.querySelectorAll('[data-disc-filter]').forEach(b => b.addEventListener('click', function() {
  document.querySelectorAll('[data-disc-filter]').forEach(x=>x.classList.remove('active'));
  this.classList.add('active');
  discFilter = this.dataset.discFilter;
  loadDiscoveries();
}));

async function dismissDiscovery(id) {
  await api.patch('/tracker/api/discoveries.php?id=' + id, {status:'dismissed'});
  loadDiscoveries();
}

function promoteDiscovery(discId, orgId, title) {
  openAppModal(null);
  setTimeout(() => {
    if (orgId) $id('f-app-org').value = orgId;
    $id('f-app-role').value = title;
  }, 50);
}

// ── Daily Brief ──────────────────────────────────────────────────────────

async function loadBrief() {
  const [latestR, histR] = await Promise.all([
    api.get('/tracker/api/report.php'),
    api.get('/tracker/api/report.php?history=1'),
  ]);
  const latest = latestR.data;
  $id('brief-latest').innerHTML = latest
    ? `<div class="brief-card">
        <div class="brief-card__date">${esc(latest.report_date)} · generated ${esc(latest.generated_at?.split(' ')[0]||'')}</div>
        <div style="display:flex;gap:var(--space-8);margin-bottom:var(--space-4)">
          <div><div class="brief-card__count">${latest.new_roles_count}</div><div class="brief-card__label">New roles found</div></div>
        </div>
        ${latest.coaching_insight ? `<div class="brief-card__insight">${esc(latest.coaching_insight).replace(/\n/g,'<br>')}</div>` : ''}
       </div>`
    : '<div class="empty-state">No reports yet. Agents will write the first report tonight.</div>';

  const hist = histR.data || [];
  $id('brief-history').innerHTML = hist.slice(1).map(r =>
    `<div class="brief-card" style="border-left-color:var(--border-default);margin-bottom:var(--space-3)">
      <div class="brief-card__date">${esc(r.report_date)}</div>
      <div class="brief-card__count" style="font-size:20px">${r.new_roles_count} roles</div>
      ${r.coaching_insight ? `<div class="brief-card__insight">${esc(r.coaching_insight).replace(/\n/g,'<br>')}</div>` : ''}
     </div>`).join('') || '<div class="empty-state">No history yet.</div>';
}

// ── Coaching ─────────────────────────────────────────────────────────────

async function loadCoaching() {
  const r = await api.get('/tracker/api/coaching.php');
  const sessions = r.data || [];
  const el = $id('coaching-responses');
  el.innerHTML = sessions.length
    ? sessions.map(s => `
        <div class="coaching-response ${s.status==='pending'?'pending':''}">
          <div class="coaching-response__prompt">${esc(s.prompt_summary)}</div>
          <div class="coaching-response__text">
            ${s.status === 'pending'
              ? '⏳ Response pending — the coaching agent will process this on its next run.'
              : esc(s.response_text||'').replace(/\n/g,'<br>')}
          </div>
        </div>`).join('')
    : '<div class="empty-state">No coaching sessions yet. Ask a question above.</div>';
}

document.querySelectorAll('.preset-btn').forEach(b => b.addEventListener('click', function() {
  $id('coaching-input').value = this.dataset.prompt;
}));

$id('btn-coaching-submit').onclick = async () => {
  const prompt = $id('coaching-input').value.trim();
  if (!prompt) return;
  await api.post('/tracker/api/coaching.php', {prompt});
  $id('coaching-input').value = '';
  loadCoaching();
};

// ── Modal close ──────────────────────────────────────────────────────────

function closeModal(id) {
  $id(id).classList.remove('open');
}

document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});

// ── Init ─────────────────────────────────────────────────────────────────

loadApps();

<?php endif; ?>
</script>

</body>
</html>
