// ============================================================
//  STATE
// ============================================================
let roadData  = {};
let segments  = [];
let manualCount = 0;

// ============================================================
//  BOOT — single entry point
// ============================================================
window.addEventListener('DOMContentLoaded', () => {

  document.getElementById('segmentLength').addEventListener('change', function () {
    document.getElementById('customLengthInput').style.display =
      this.value === 'custom' ? 'block' : 'none';
    updateAutoPreview();
  });
  document.getElementById('roadLength').addEventListener('input', updateAutoPreview);
  document.getElementById('customSegmentLength').addEventListener('input', updateAutoPreview);

  const params = new URLSearchParams(window.location.search);
  const status = params.get('status');
  const segId  = parseInt(params.get('segment_id'));

  if (status === 'done' && segId) {
    // Returning from audit form — mark segment done, then show segments view
    markSegmentCompleted(segId);
  } else {
    // Every other load: fresh open, refresh, typing URL — always blank Define Road
    showRoadForm();
  }
});

// Re-sync only when returning to a tab that already has segments loaded in memory
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible' && segments.length > 0) {
    syncStatusFromDB();
  }
});

// ============================================================
//  MARK SEGMENT COMPLETED (return from audit form only)
// ============================================================
function markSegmentCompleted(segId) {
  fetch('../api/mark_segment_done.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ segment_id: segId })
  })
  .then(r => r.json())
  .then(() => {
    window.history.replaceState({}, '', 'segment.html');
    loadRoadFromDB();
  })
  .catch(() => loadRoadFromDB());
}

// ============================================================
//  LOAD ROAD FROM DB (only after returning from audit form)
// ============================================================
function loadRoadFromDB() {
  fetch('../api/get_road.php')
    .then(r => r.json())
    .then(data => {
      if (data && data.road && data.segments && data.segments.length > 0) {
        roadData = data.road;
        segments = data.segments;
        displaySegments();
      } else {
        showRoadForm();
      }
    })
    .catch(() => showRoadForm());
}

// ============================================================
//  SYNC STATUS ONLY (tab visibility, not boot)
// ============================================================
function syncStatusFromDB() {
  fetch('../api/get_road.php')
    .then(r => r.json())
    .then(data => {
      if (data && data.segments) {
        segments = data.segments;
        roadData = data.road || roadData;
        displaySegments();
      }
    })
    .catch(() => {});
}

// ============================================================
//  SHOW ROAD FORM — always completely blank
// ============================================================
function showRoadForm() {
  roadData = {};
  segments = [];

  document.getElementById('roadName').value     = '';
  document.getElementById('roadStart').value    = '';
  document.getElementById('roadEnd').value      = '';
  document.getElementById('roadLength').value   = '';
  document.getElementById('roadGpsStart').value = '';
  document.getElementById('roadGpsEnd').value   = '';

  selectMethod('auto');
  document.getElementById('segmentLength').value             = '200';
  document.getElementById('customLengthInput').style.display = 'none';
  document.getElementById('autoPreview').classList.remove('show');

  document.getElementById('manualSegmentsList').innerHTML = '';
  manualCount = 0;
  updateManualEmpty();
  clearErrors();

  document.getElementById('roadSetupSection').style.display = 'block';
  document.getElementById('segmentsSection').style.display  = 'none';
  setStep(1);
}

// ============================================================
//  GPS HELPER
// ============================================================
function getGPS(endpoint) {
  if (!navigator.geolocation) {
    showToast('Geolocation not supported by your browser.', 'error');
    return;
  }
  showToast('Getting location…');
  navigator.geolocation.getCurrentPosition(
    pos => {
      const coord = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`;
      document.getElementById(endpoint === 'start' ? 'roadGpsStart' : 'roadGpsEnd').value = coord;
      showToast('Location captured!', 'success');
    },
    () => showToast('Location access denied.', 'error')
  );
}

// ============================================================
//  VALIDATION
// ============================================================
function clearErrors() {
  document.querySelectorAll('.field-error').forEach(el => el.classList.remove('show'));
  document.querySelectorAll('input.error, select.error').forEach(el => el.classList.remove('error'));
}

function showError(fieldId, errId) {
  const field = document.getElementById(fieldId);
  const err   = document.getElementById(errId);
  if (field) field.classList.add('error');
  if (err)   err.classList.add('show');
  return false;
}

function validateRoadFields() {
  clearErrors();
  let valid = true;
  if (!document.getElementById('roadName').value)         { showError('roadName',   'err-roadName');   valid = false; }
  if (!document.getElementById('roadStart').value.trim()) { showError('roadStart',  'err-roadStart');  valid = false; }
  if (!document.getElementById('roadEnd').value.trim())   { showError('roadEnd',    'err-roadEnd');    valid = false; }
  const len = parseFloat(document.getElementById('roadLength').value);
  if (!len || len <= 0)                                   { showError('roadLength', 'err-roadLength'); valid = false; }
  return valid;
}

// ============================================================
//  METHOD SELECTION
// ============================================================
function selectMethod(method) {
  ['auto','manual'].forEach(m => {
    const el = document.getElementById(`opt-${m}`);
    if (el) el.classList.remove('selected');
  });
  const sel = document.getElementById(`opt-${method}`);
  if (sel) sel.classList.add('selected');

  document.getElementById('autoContent').classList.remove('active');
  document.getElementById('manualContent').classList.remove('active');
  document.getElementById(`${method}Content`).classList.add('active');
  if (method === 'auto') updateAutoPreview();
  updateManualEmpty();
}

// ============================================================
//  AUTO PREVIEW
// ============================================================
function updateAutoPreview() {
  const roadLength = parseFloat(document.getElementById('roadLength').value) || 0;
  const sel    = document.getElementById('segmentLength').value;
  const segLen = sel === 'custom'
    ? parseFloat(document.getElementById('customSegmentLength').value) || 0
    : parseFloat(sel);
  const preview = document.getElementById('autoPreview');
  if (roadLength > 0 && segLen > 0) {
    const n    = Math.ceil(roadLength / segLen);
    const last = (roadLength % segLen) || segLen;
    document.getElementById('autoPreviewText').innerHTML =
      `Road → <strong>${n} segment${n > 1 ? 's' : ''}</strong>: ` +
      (n > 1 ? `${n-1} × ${segLen}m + 1 × ${last.toFixed(0)}m` : `1 × ${last.toFixed(0)}m`);
    preview.classList.add('show');
  } else {
    preview.classList.remove('show');
  }
}

// ============================================================
//  AUTO SEGMENTATION
// ============================================================
function generateAutoSegments() {
  if (!validateRoadFields()) { showToast('Please fix the highlighted fields.', 'error'); return; }

  const sel = document.getElementById('segmentLength').value;
  let segLen;
  if (sel === 'custom') {
    segLen = parseFloat(document.getElementById('customSegmentLength').value);
    if (!segLen || segLen < 50) {
      showError('customSegmentLength', 'err-customLength');
      showToast('Enter a valid segment length.', 'error');
      return;
    }
  } else {
    segLen = parseFloat(sel);
  }

  const roadLength = parseFloat(document.getElementById('roadLength').value);
  const roadStart  = document.getElementById('roadStart').value.trim();
  const roadEnd    = document.getElementById('roadEnd').value.trim();

  roadData = {
    name:          document.getElementById('roadName').value,
    start:         roadStart,
    end:           roadEnd,
    length:        roadLength,
    gpsStart:      document.getElementById('roadGpsStart').value.trim(),
    gpsEnd:        document.getElementById('roadGpsEnd').value.trim(),
    method:        'auto',
    segmentLength: segLen,
    createdAt:     new Date().toISOString()
  };

  segments = [];
  let cur = 0, num = 1;
  while (cur < roadLength) {
    const end = Math.min(cur + segLen, roadLength);
    segments.push({
      id:            num,
      number:        num,
      startDistance: cur,
      endDistance:   end,
      length:        end - cur,
      startLandmark: num === 1 ? roadStart : `${cur}m from start`,
      endLandmark:   end === roadLength ? roadEnd : `${end}m from start`,
      status:        'pending',
      auditData:     null
    });
    cur = end; num++;
  }

  roadData.segments = segments;
  saveRoadToDB(roadData, segments, () => {
    showToast(`${segments.length} segments generated!`, 'success');
    displaySegments();
  });
}

// ============================================================
//  MANUAL SEGMENTS
// ============================================================
function updateManualEmpty() {
  const list  = document.getElementById('manualSegmentsList');
  const empty = document.getElementById('manualEmpty');
  if (!empty) return;
  empty.style.display = list.children.length === 0 ? 'block' : 'none';
}

function addManualSegment() {
  manualCount++;
  document.getElementById('manualEmpty').style.display = 'none';
  const container = document.getElementById('manualSegmentsList');
  const div = document.createElement('div');
  div.className = 'manual-seg';
  div.id = `mseg-${manualCount}`;
  div.innerHTML = `
    <div class="manual-seg-header">
      <span class="seg-badge">Segment ${manualCount}</span>
      <button class="btn btn-danger btn-sm" onclick="removeManualSegment(${manualCount})">Remove</button>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Start Distance (m)</label>
        <input type="number" class="seg-start" placeholder="0" min="0" autocomplete="off">
      </div>
      <div class="form-group">
        <label>End Distance (m)</label>
        <input type="number" class="seg-end" placeholder="200" min="1" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Start Landmark</label>
        <input type="text" class="seg-start-landmark" placeholder="Landmark / intersection" autocomplete="off">
      </div>
      <div class="form-group">
        <label>End Landmark</label>
        <input type="text" class="seg-end-landmark" placeholder="Landmark / intersection" autocomplete="off">
      </div>
    </div>`;
  container.appendChild(div);
}

function removeManualSegment(id) {
  document.getElementById(`mseg-${id}`)?.remove();
  updateManualEmpty();
}

function saveManualSegments() {
  if (!validateRoadFields()) { showToast('Please fix the highlighted fields.', 'error'); return; }
  const cards = document.querySelectorAll('#manualSegmentsList .manual-seg');
  if (cards.length === 0) { showToast('Add at least one segment first.', 'error'); return; }

  segments = [];
  let valid = true;
  cards.forEach((card, i) => {
    const s  = parseFloat(card.querySelector('.seg-start').value);
    const e  = parseFloat(card.querySelector('.seg-end').value);
    const sl = card.querySelector('.seg-start-landmark').value.trim();
    const el = card.querySelector('.seg-end-landmark').value.trim();
    if (isNaN(s) || isNaN(e) || !sl || !el) { valid = false; return; }
    if (e <= s) { showToast(`Segment ${i+1}: end must be > start.`, 'error'); valid = false; return; }
    segments.push({
      id: i+1, number: i+1,
      startDistance: s, endDistance: e, length: e - s,
      startLandmark: sl, endLandmark: el,
      status: 'pending', auditData: null
    });
  });
  if (!valid) return;
  segments.sort((a,b) => a.startDistance - b.startDistance);

  roadData = {
    name:      document.getElementById('roadName').value,
    start:     document.getElementById('roadStart').value.trim(),
    end:       document.getElementById('roadEnd').value.trim(),
    length:    parseFloat(document.getElementById('roadLength').value),
    gpsStart:  document.getElementById('roadGpsStart').value.trim(),
    gpsEnd:    document.getElementById('roadGpsEnd').value.trim(),
    method:    'manual',
    createdAt: new Date().toISOString()
  };
  roadData.segments = segments;

  saveRoadToDB(roadData, segments, () => {
    showToast(`${segments.length} segments saved!`, 'success');
    displaySegments();
  });
}

// ============================================================
//  SAVE ROAD + SEGMENTS TO DB
// ============================================================
function saveRoadToDB(road, segs, callback) {
  fetch('../api/save_segments.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ road, segments: segs })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (callback) callback();
    } else {
      showToast('❌ Failed to save: ' + (data.error || 'Unknown error'), 'error');
    }
  })
  .catch(err => {
    console.error(err);
    showToast('❌ Could not connect to server. Is XAMPP running?', 'error');
  });
}

// ============================================================
//  CLEAR ROAD FROM DB (when user clicks Edit Road → Yes)
// ============================================================
function clearRoadFromDB(callback) {
  fetch('../api/clear_road.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(r => r.json())
  .then(() => { if (callback) callback(); })
  .catch(() => { if (callback) callback(); });
}

// ============================================================
//  DISPLAY SEGMENTS
// ============================================================
function displaySegments() {
  document.getElementById('roadSetupSection').style.display = 'none';
  document.getElementById('segmentsSection').style.display  = 'block';
  setStep(2);

  document.getElementById('roadNameDisplay').textContent  = roadData.name  || '';
  document.getElementById('roadRouteDisplay').textContent = `${roadData.start} → ${roadData.end}`;

  const pills = document.getElementById('roadPills');
  pills.innerHTML = `
    <span class="pill">📏 ${roadData.length}m</span>
    <span class="pill">🔖 ${segments.length} segments</span>
    <span class="pill">${roadData.method === 'auto' ? '⚡ Auto' : '✏️ Manual'}</span>
  `;

  const done    = segments.filter(s => s.status === 'completed').length;
  const pending = segments.length - done;
  const pct     = segments.length ? Math.round((done / segments.length) * 100) : 0;
  const allDone = pending === 0 && segments.length > 0;

  document.getElementById('progressBar').style.width     = pct + '%';
  document.getElementById('progressPercent').textContent = pct + '%';
  document.getElementById('progressLabel').textContent   = `${done} of ${segments.length} completed`;

  const viewBtn = document.getElementById('viewResultsBtn');
  if (viewBtn) {
    viewBtn.disabled = !allDone;
    viewBtn.title    = allDone ? '' : `${pending} segment${pending > 1 ? 's' : ''} still need to be audited`;
  }

  document.getElementById('completionBanner').style.display = allDone ? 'block' : 'none';
  document.getElementById('blockedBanner').style.display    = (!allDone && segments.length > 0) ? 'block' : 'none';

  if (allDone) {
    document.getElementById('completionTitle').textContent =
      `All ${segments.length} segment${segments.length > 1 ? 's' : ''} audited ✓ — Road is ready for final scoring.`;
  } else if (segments.length > 0) {
    document.getElementById('blockedText').textContent =
      `${pending} segment${pending > 1 ? 's' : ''} still pending — complete all audits to unlock the final result.`;
  }

  const list = document.getElementById('segmentsList');
  list.innerHTML = '';
  if (segments.length === 0) {
    list.innerHTML = '<div class="empty-state"><p>No segments found.</p></div>';
    return;
  }

  const totalPending = segments.filter(s => s.status !== 'completed').length;
  segments.forEach(seg => {
    const isDone        = seg.status === 'completed';
    const isLastPending = !isDone && totalPending === 1;
    const card          = document.createElement('div');
    card.className      = `seg-list-card${isDone ? ' seg-done' : ''}`;

    let statusHtml;
    if (isDone) {
      const ts = seg.auditData?.completedAt
        ? `<span class="seg-timestamp">Audited ${formatTime(seg.auditData.completedAt)}</span>` : '';
      statusHtml = `<div class="status-col"><span class="status-chip status-completed">✓ Audited</span>${ts}</div>`;
    } else if (isLastPending) {
      statusHtml = `<div class="status-col"><span class="status-chip status-blocking">⚠ Last Remaining</span><span class="seg-timestamp">Blocks final result</span></div>`;
    } else {
      statusHtml = `<div class="status-col"><span class="status-chip status-pending">Pending</span><span class="seg-timestamp">Needed for final score</span></div>`;
    }

    card.innerHTML = `
      <div class="seg-num ${isDone ? 'done' : ''}">${isDone ? '✓' : seg.number}</div>
      <div class="seg-list-info">
        <div class="seg-list-name">Segment ${seg.number}: ${seg.startLandmark} → ${seg.endLandmark}</div>
        <div class="seg-list-meta">${seg.startDistance}m – ${seg.endDistance}m &nbsp;|&nbsp; ${seg.length.toFixed(0)}m long</div>
      </div>
      ${statusHtml}
      <div class="seg-actions">
        ${isDone
          ? `<button class="btn btn-secondary btn-sm" disabled style="background:gray;">🔒 Locked</button>
             <button class="btn btn-secondary btn-sm" onclick="viewSegmentResult(${seg.id})">Results</button>`
          : `<button class="btn btn-primary btn-sm" onclick="auditSegment(${seg.id})">Start Audit</button>`
        }
      </div>`;
    list.appendChild(card);
  });
}

// ============================================================
//  NAVIGATION
// ============================================================
function auditSegment(segId) {
  const seg = segments.find(s => s.id === segId);
  if (!seg) return;
  if (seg.status === 'completed') {
    alert('This segment is already audited and locked!');
    return;
  }
  const context = {
    segmentId:         seg.id,
    segmentNumber:     seg.number,
    segmentTotal:      segments.length,
    startDistance:     seg.startDistance,
    endDistance:       seg.endDistance,
    length:            seg.length,
    startLandmark:     seg.startLandmark,
    endLandmark:       seg.endLandmark,
    roadName:          roadData.name,
    roadStart:         roadData.start,
    roadEnd:           roadData.end,
    roadLength:        roadData.length,
    roadGpsStart:      roadData.gpsStart,
    roadGpsEnd:        roadData.gpsEnd,
    existingAuditData: seg.auditData || null,
    auditStartedAt:    new Date().toISOString()
  };
  localStorage.setItem('currentSegment', JSON.stringify(context));
  window.location.href = `form.html?segment_id=${segId}`;
}

function viewSegmentResult(segId) {
  window.location.href = `view.php?segment_id=${segId}`;
}

// ============================================================
//  EDIT ROAD
// ============================================================
function editRoadInfo() {
  const done  = segments.filter(s => s.status === 'completed').length;
  const total = segments.length;
  let bodyMsg, warningMsg;

  if (done === 0) {
    bodyMsg    = `You're about to edit the road details for <strong>${roadData.name}</strong>. No audits have been started yet, so this is safe to change.`;
    warningMsg = `⚠ Regenerating segments will reset the current segment list.`;
  } else if (done < total) {
    bodyMsg    = `You're about to edit <strong>${roadData.name}</strong>. <strong>${done} of ${total} segments</strong> have already been audited.`;
    warningMsg = `⚠ If you regenerate segments, all existing audit progress will be lost and cannot be recovered.`;
  } else {
    bodyMsg    = `All <strong>${total} segments</strong> of <strong>${roadData.name}</strong> have been audited.`;
    warningMsg = `⚠ Editing a fully audited road will require re-auditing all segments before viewing results.`;
  }

  document.getElementById('editModalBody').innerHTML      = bodyMsg;
  document.getElementById('editModalWarning').textContent = warningMsg;
  document.getElementById('editModal').classList.add('show');
}

function closeEditModal() { document.getElementById('editModal').classList.remove('show'); }

function confirmEditRoad() {
  closeEditModal();
  // Clear DB so refresh won't restore the old road, then show blank form
  clearRoadFromDB(() => showRoadForm());
}



// ============================================================
//  UTILITIES
// ============================================================
function formatTime(isoString) {
  try {
    const d       = new Date(isoString);
    const diffMs  = Date.now() - d;
    const diffMin = Math.floor(diffMs / 60000);
    const diffHr  = Math.floor(diffMs / 3600000);
    const diffDay = Math.floor(diffMs / 86400000);
    if (diffMin < 1)   return 'just now';
    if (diffMin < 60)  return `${diffMin}m ago`;
    if (diffHr  < 24)  return `${diffHr}h ago`;
    if (diffDay === 1) return 'yesterday';
    return d.toLocaleDateString();
  } catch { return ''; }
}

function setStep(n) {
  [1,2,3].forEach(i => {
    const el = document.getElementById(`step${i}`);
    el.classList.remove('active','done');
    if (i < n)        el.classList.add('done');
    else if (i === n) el.classList.add('active');
  });
}

let toastTimer;
function showToast(msg, type = '') {
  const icons = {
    success: `<svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`,
    error:   `<svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`,
    '':      `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
  };
  const t = document.getElementById('toast');
  t.className = `toast ${type}`;
  t.innerHTML = (icons[type] || icons['']) + msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
}