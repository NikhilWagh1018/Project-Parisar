<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once "../config/db.php";

$uid   = $_SESSION['user_id'];
$uname = $_SESSION['user_name'];

// ── stat queries ──────────────────────────────────────
$stmt = $pdo->prepare("SELECT COUNT(*) FROM segment_audits WHERE surveyor_id = ?");
$stmt->execute([$uid]); $my_audits = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM segments WHERE status='completed'");
$done = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM segments");
$total_segs = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT ROUND(AVG(footpath_score),1) FROM segment_audits WHERE surveyor_id=?");
$stmt->execute([$uid]); $avg_fp = (float)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT road_name) FROM segments");
$road_count = (int)$stmt->fetchColumn();

$pct = $total_segs > 0 ? round(($done/$total_segs)*100) : 0;

// ── recent audits ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT sa.id, sa.segment_id, sa.start_landmark, sa.end_landmark,
           sa.created_at, sa.footpath_score, sa.buffer_zone,
           sa.light_after_sunset, sa.surface_material, sa.shade,
           seg.road_name, seg.length
    FROM segment_audits sa
    LEFT JOIN segments seg ON seg.id = sa.segment_id
    WHERE sa.surveyor_id = ?
    ORDER BY sa.created_at DESC LIMIT 8
");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── segment list ──────────────────────────────────────
$stmt = $pdo->query("SELECT id,road_name,start_label,end_label,status,length FROM segments ORDER BY id ASC LIMIT 15");
$segs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── score per recent audit ────────────────────────────
function calcScore($row, $pdo) {
    $sid = $row['id'];
    $obsStmt = $pdo->prepare("SELECT SUM(partial_obstructions) p, SUM(total_obstructions) t, SUM(cyclist_slowed) s FROM obstructions WHERE audit_id=?");
    $obsStmt->execute([$sid]); $obs = $obsStmt->fetch(PDO::FETCH_ASSOC);
    $partial = $obs['p'] ?? 0; $totalO = $obs['t'] ?? 0; $slowed = $obs['s'] ?? 0;

    $intStmt = $pdo->prepare("SELECT * FROM intersections WHERE audit_id=?");
    $intStmt->execute([$sid]); $ints = $intStmt->fetchAll(PDO::FETCH_ASSOC);

    $buf   = ($row['buffer_zone'] == 'None') ? 100 : 0;
    $light = ($row['light_after_sunset'] == 'Yes') ? 0 : (($row['light_after_sunset'] == 'Partial') ? 50 : 100);
    $absent = count(array_filter($ints, fn($i) => ($i['traffic_device'] ?? '') == 'Absent'));
    $traffic = $absent == 0 ? 0 : ($absent == 1 ? 50 : ($absent == 2 ? 75 : 100));
    $partS   = $partial < 5 ? 0 : ($partial <= 10 ? 50 : 100);
    $safety  = ($buf + $light + $traffic + $partS) / 4;

    $noRamp  = count(array_filter($ints, fn($i) => ($i['off_ramp']??'')=='No Ramp' || ($i['on_ramp']??'')=='No Ramp'));
    $rampS   = $noRamp == 0 ? 0 : ($noRamp >= 5 ? 100 : ($noRamp >= 3 ? 50 : 25));
    $noSign  = count(array_filter($ints, fn($i) => ($i['markings']??'')=='Absent' || ($i['signage']??'')=='Absent'));
    $signS   = $noSign == 0 ? 0 : ($noSign == 1 ? 50 : ($noSign == 2 ? 75 : 100));
    $totOS   = $totalO < 5 ? 0 : ($totalO <= 10 ? 50 : 100);
    $continuity = ($rampS + $signS + $totOS) / 3;

    $surfS  = ($row['surface_material'] == 'Interlock Blocks') ? 100 : 0;
    $slowS  = $slowed < 5 ? 0 : ($slowed <= 10 ? 50 : ($slowed <= 20 ? 75 : 100));
    $shade  = $row['shade'] ?? 'No';
    $shadeS = $shade == 'Yes' ? 0 : ($shade == 'Partial' ? 50 : 100);
    $comfort = ($surfS + $slowS + $shadeS) / 3;

    $final = 100 - (($safety + $continuity + $comfort) / 3);
    return ['safety'=>round($safety,1), 'continuity'=>round($continuity,1), 'comfort'=>round($comfort,1), 'final'=>round($final,1)];
}

function ratingLabel($s) {
    if ($s >= 80) return ['Good','#15803d','#dcfce7'];
    if ($s >= 50) return ['Moderate','#d97706','#fef3c7'];
    return ['Poor','#dc2626','#fee2e2'];
}

$hour = (int)date('H');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — CycleAudit</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --g:#3d7a1f;--gm:#5a9e2f;--gl:#86B93B;--gp:#edf7d6;--gd:#1e3d0d;
  --cream:#f7f4ee;--ink:#181f10;--gray:#5e6b54;--grl:#9aaa88;
  --bd:rgba(61,122,31,.13);--sw:232px;--hh:64px;--r:12px;--T:.25s cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--ink);display:flex;min-height:100vh}

/* SIDEBAR */
aside{width:var(--sw);background:var(--gd);color:#fff;display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:100}
.sb-brand{display:flex;align-items:center;gap:10px;padding:20px 18px;border-bottom:1px solid rgba(255,255,255,.07);font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700}
.sb-mark{width:34px;height:34px;border-radius:9px;background:var(--gl);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sb-mark svg{width:18px;height:18px;fill:#fff}
.sb-nav{flex:1;padding:16px 10px;display:flex;flex-direction:column;gap:3px}
.sn{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:9px;font-size:.86rem;font-weight:500;color:rgba(255,255,255,.65);text-decoration:none;transition:all var(--T)}
.sn:hover{background:rgba(255,255,255,.08);color:#fff}
.sn.act{background:var(--gl);color:#fff}
.sn .ic{font-size:1rem;flex-shrink:0}
.sb-foot{padding:14px 10px;border-top:1px solid rgba(255,255,255,.07)}
.u-card{display:flex;align-items:center;gap:9px;padding:10px;border-radius:9px;background:rgba(255,255,255,.05)}
.u-av{width:34px;height:34px;border-radius:50%;background:var(--gl);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.88rem;flex-shrink:0}
.u-info strong{display:block;font-size:.82rem;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px}
.u-info span{font-size:.7rem;color:rgba(255,255,255,.45)}
.logout{display:block;text-align:center;margin-top:8px;padding:8px;border-radius:8px;background:rgba(220,38,38,.14);color:#fca5a5;text-decoration:none;font-size:.78rem;font-weight:600;transition:background var(--T)}
.logout:hover{background:rgba(220,38,38,.25)}

/* MAIN */
main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh}

/* TOPBAR */
.topbar{height:var(--hh);background:#fff;border-bottom:1px solid rgba(61,122,31,.1);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50;box-shadow:0 2px 8px rgba(24,31,16,.05)}
.tb-left h2{font-size:.97rem;font-weight:700;color:var(--ink)}
.tb-left p{font-size:.76rem;color:var(--grl)}
.btn-new{background:var(--g);color:#fff;padding:9px 18px;border:none;border-radius:9px;font-family:'DM Sans',sans-serif;font-size:.84rem;font-weight:700;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px;transition:all var(--T);box-shadow:0 2px 10px rgba(61,122,31,.25)}
.btn-new:hover{background:var(--gd);transform:translateY(-1px)}

/* CONTENT */
.content{padding:26px 28px;flex:1}

/* STAT CARDS */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px}
.st{background:#fff;border-radius:var(--r);padding:20px;border:1px solid var(--bd);display:flex;align-items:flex-start;gap:13px;transition:transform var(--T),box-shadow var(--T);animation:slideUp .45s ease both}
.st:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(24,31,16,.09)}
.st:nth-child(2){animation-delay:.06s}.st:nth-child(3){animation-delay:.12s}.st:nth-child(4){animation-delay:.18s}.st:nth-child(5){animation-delay:.24s}
@keyframes slideUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.st-ic{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
.ic-g{background:var(--gp)}.ic-b{background:#dbeafe}.ic-o{background:#fef3c7}.ic-p{background:#ede9fe}.ic-r{background:#fce7f3}
.st-v{font-family:'Playfair Display',serif;font-size:1.7rem;font-weight:800;color:var(--ink);line-height:1}
.st-l{font-size:.76rem;color:var(--gray);margin-top:4px;font-weight:500}
.st-s{font-size:.68rem;color:var(--grl);margin-top:2px}

/* PROGRESS CARD */
.prog-card{background:#fff;border-radius:var(--r);padding:20px 24px;border:1px solid var(--bd);margin-bottom:22px}
.prog-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.prog-head span{font-size:.84rem;color:var(--gray)}
.prog-head strong{font-size:.97rem;font-weight:800;color:var(--g)}
.prog-track{height:10px;background:var(--gp);border-radius:99px;overflow:hidden}
.prog-fill{height:100%;background:linear-gradient(90deg,var(--gl),var(--g));border-radius:99px;transition:width 1.2s cubic-bezier(.4,0,.2,1)}

/* TWO COL */
.two-col{display:grid;grid-template-columns:1.7fr 1fr;gap:20px}

/* CARD BASE */
.card{background:#fff;border-radius:var(--r);border:1px solid var(--bd);overflow:hidden}
.card-head{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid rgba(61,122,31,.07)}
.card-head h3{font-size:.9rem;font-weight:700;color:var(--ink)}
.card-head a{font-size:.76rem;color:var(--g);font-weight:600;text-decoration:none}
.card-head a:hover{text-decoration:underline}

/* AUDIT TABLE */
.audit-table{width:100%;border-collapse:collapse}
.audit-table th{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--grl);padding:10px 16px;text-align:left;background:#fafdf6;border-bottom:1px solid var(--bd)}
.audit-table td{padding:12px 16px;font-size:.82rem;border-bottom:1px solid rgba(61,122,31,.06);vertical-align:middle}
.audit-table tr:last-child td{border-bottom:none}
.audit-table tr:hover td{background:#fafdf6}

.score-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:.75rem;font-weight:800}
.road-name{font-weight:700;font-size:.82rem;color:var(--ink);max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.route-cell{font-size:.76rem;color:var(--gray);max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

/* SCORE BAR */
.mini-bars{display:flex;flex-direction:column;gap:4px;min-width:100px}
.mini-bar-row{display:flex;align-items:center;gap:5px}
.mini-bar-lbl{font-size:.62rem;color:var(--grl);width:10px;font-weight:600}
.mini-bar-track{flex:1;height:5px;background:var(--gp);border-radius:99px;overflow:hidden}
.mini-bar-fill{height:100%;border-radius:99px}

/* SEG LIST */
.seg-list{display:flex;flex-direction:column}
.seg-item{display:flex;align-items:center;gap:10px;padding:11px 18px;border-bottom:1px solid rgba(61,122,31,.06);transition:background var(--T)}
.seg-item:last-child{border-bottom:none}
.seg-item:hover{background:#fafdf6}
.seg-num{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0}
.sn-ok{background:#dcfce7;color:#15803d}.sn-pd{background:#fef3c7;color:#d97706}
.seg-info{flex:1;overflow:hidden}
.seg-info strong{display:block;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.seg-info span{font-size:.7rem;color:var(--grl)}
.seg-badge{font-size:.67rem;font-weight:700;padding:2px 8px;border-radius:100px}
.sb-ok{background:#dcfce7;color:#15803d}.sb-pd{background:#fef3c7;color:#d97706}

.empty{text-align:center;padding:36px;color:var(--grl);font-size:.88rem}
.empty .em{font-size:2rem;margin-bottom:8px}

@media(max-width:1100px){.stats{grid-template-columns:repeat(3,1fr)}.two-col{grid-template-columns:1fr}}
@media(max-width:768px){aside{display:none}main{margin-left:0}.stats{grid-template-columns:1fr 1fr}.content{padding:16px}}
</style>
</head>
<body>

<aside>
  <div class="sb-brand">
    <div class="sb-mark"><svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.3 7 13 7 13s7-7.7 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg></div>
    CycleAudit
  </div>
  <nav class="sb-nav">
    <a class="sn act" href="dashboard.php"><span class="ic">📊</span>Dashboard</a>
    <a class="sn" href="segment.html"><span class="ic">🗺️</span>New Audit</a>
    <a class="sn" href="road_result.php"><span class="ic">🏁</span>Road Scores</a>
    <a class="sn" href="view.php"><span class="ic">👁️</span>View All Audits</a>
    <a class="sn" href="../reports/report.php"><span class="ic">📄</span>PDF Reports</a>
  </nav>
  <div class="sb-foot">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(substr($uname,0,1)) ?></div>
      <div class="u-info"><strong><?= htmlspecialchars($uname) ?></strong><span>Surveyor</span></div>
    </div>
    <a href="../auth/logout.php" class="logout">🚪 Sign Out</a>
  </div>
</aside>

<main>
  <div class="topbar">
    <div class="tb-left">
      <h2><?= $greet ?>, <?= htmlspecialchars(explode(' ',$uname)[0]) ?>! 👋</h2>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <a href="segment.html" class="btn-new">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Start New Audit
    </a>
  </div>

  <div class="content">

    <!-- STAT CARDS -->
    <div class="stats">
      <div class="st"><div class="st-ic ic-g">📋</div><div><div class="st-v"><?= $my_audits ?></div><div class="st-l">Your Audits</div><div class="st-s">Segments submitted</div></div></div>
      <div class="st"><div class="st-ic ic-b">✅</div><div><div class="st-v"><?= $done ?></div><div class="st-l">Completed Segs</div><div class="st-s">of <?= $total_segs ?> total</div></div></div>
      <div class="st"><div class="st-ic ic-o">📈</div><div><div class="st-v"><?= $avg_fp ?>%</div><div class="st-l">Avg Footpath Score</div><div class="st-s">Your submissions</div></div></div>
      <div class="st"><div class="st-ic ic-p">🏙️</div><div><div class="st-v"><?= $road_count ?></div><div class="st-l">Roads in DB</div><div class="st-s">With segments</div></div></div>
      <div class="st"><div class="st-ic ic-r">📊</div><div><div class="st-v"><?= $pct ?>%</div><div class="st-l">Overall Progress</div><div class="st-s">Network audit</div></div></div>
    </div>

    <!-- PROGRESS BAR -->
    <div class="prog-card">
      <div class="prog-head">
        <span>🚴 Road Network Audit — <?= $done ?> of <?= $total_segs ?> segments completed</span>
        <strong><?= $pct ?>%</strong>
      </div>
      <div class="prog-track"><div class="prog-fill" id="pf" style="width:0%"></div></div>
    </div>

    <div class="two-col">

      <!-- RECENT AUDITS TABLE -->
      <div class="card">
        <div class="card-head">
          <h3>📋 Your Recent Audits</h3>
          <a href="view.php">View all →</a>
        </div>
        <?php if (count($recent) > 0): ?>
        <div style="overflow-x:auto">
        <table class="audit-table">
          <thead>
            <tr>
              <th>Road</th>
              <th>Route</th>
              <th>Score Breakdown</th>
              <th>Final</th>
              <th>Rating</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($recent as $r):
            $sc = calcScore($r, $pdo);
            [$rLabel, $rColor, $rBg] = ratingLabel($sc['final']);
          ?>
            <tr>
              <td><div class="road-name"><?= htmlspecialchars($r['road_name'] ?? 'N/A') ?></div></td>
              <td><div class="route-cell"><?= htmlspecialchars($r['start_landmark']) ?> → <?= htmlspecialchars($r['end_landmark']) ?></div></td>
              <td>
                <div class="mini-bars">
                  <div class="mini-bar-row"><span class="mini-bar-lbl" title="Safety">S</span><div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?= min(100,100-$sc['safety']) ?>%;background:#3b82f6"></div></div></div>
                  <div class="mini-bar-row"><span class="mini-bar-lbl" title="Continuity">C</span><div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?= min(100,100-$sc['continuity']) ?>%;background:var(--gl)"></div></div></div>
                  <div class="mini-bar-row"><span class="mini-bar-lbl" title="Comfort">F</span><div class="mini-bar-track"><div class="mini-bar-fill" style="width:<?= min(100,100-$sc['comfort']) ?>%;background:#f59e0b"></div></div></div>
                </div>
              </td>
              <td><span class="score-chip" style="background:<?= $rBg ?>;color:<?= $rColor ?>"><?= $sc['final'] ?></span></td>
              <td><span class="score-chip" style="background:<?= $rBg ?>;color:<?= $rColor ?>"><?= $rLabel ?></span></td>
              <td style="color:var(--grl);font-size:.76rem;white-space:nowrap"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty"><div class="em">📭</div><p>No audits yet.<br>Click <strong>Start New Audit</strong> to begin!</p></div>
        <?php endif; ?>
      </div>

      <!-- SEGMENT STATUS -->
      <div class="card">
        <div class="card-head">
          <h3>📍 Segment Status</h3>
          <a href="segment.html">Manage →</a>
        </div>
        <?php if (count($segs) > 0): ?>
        <div class="seg-list">
        <?php foreach($segs as $s): $ok = $s['status']==='completed'; ?>
          <div class="seg-item">
            <div class="seg-num <?= $ok?'sn-ok':'sn-pd' ?>"><?= $s['id'] ?></div>
            <div class="seg-info">
              <strong><?= htmlspecialchars($s['road_name'] ?? 'Road') ?></strong>
              <span><?= htmlspecialchars($s['start_label']??'') ?> → <?= htmlspecialchars($s['end_label']??'') ?></span>
            </div>
            <span class="seg-badge <?= $ok?'sb-ok':'sb-pd' ?>"><?= $ok?'✅ Done':'⏳ Pending' ?></span>
          </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty"><div class="em">🗺️</div><p>No segments yet.<br>Set up a road first.</p></div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<script>
window.addEventListener('load', () => {
  const f = document.getElementById('pf');
  if (f) setTimeout(() => f.style.width = '<?= $pct ?>%', 300);
});
</script>
</body>
</html>