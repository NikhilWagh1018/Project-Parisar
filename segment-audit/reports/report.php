<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once "../config/db.php";

// ── INPUTS ──────────────────────────────────────────────────────
// Accept road name OR segment IDs — road name is more reliable
$road_name   = trim($_GET['road']    ?? '');
$seg_ids_raw = trim($_GET['seg_ids'] ?? '');

// ── FETCH SEGMENTS FROM DATABASE ────────────────────────────────
// Strategy: try seg_ids first, fallback to road_name, fallback to all
$segs = [];

if ($seg_ids_raw !== '') {
    $seg_ids = array_filter(array_map('intval', explode(',', $seg_ids_raw)));
    if (!empty($seg_ids)) {
        $ph   = implode(',', array_fill(0, count($seg_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM segments WHERE id IN ($ph) ORDER BY id ASC");
        $stmt->execute(array_values($seg_ids));
        $segs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (empty($segs) && $road_name !== '') {
    $stmt = $pdo->prepare("SELECT * FROM segments WHERE road_name = ? ORDER BY id ASC");
    $stmt->execute([$road_name]);
    $segs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// If still empty, get the most recently used road from segments table
if (empty($segs)) {
    $stmt = $pdo->query("SELECT * FROM segments ORDER BY id ASC");
    $segs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── ROAD META ────────────────────────────────────────────────────
if (!empty($segs)) {
    $road_name  = $road_name  ?: ($segs[0]['road_name']  ?? 'Unknown Road');
    $road_start = $segs[0]['road_start'] ?? 'N/A';
    $road_end   = end($segs)['road_end'] ?? 'N/A';
    $road_length= $segs[0]['road_length'] ?? 0;
} else {
    // Nothing in DB at all
    $road_name = $road_name ?: 'No Data';
    $road_start = $road_end = 'N/A';
    $road_length = 0;
}

$audit_date = date('d F Y');
$surveyor   = $_SESSION['user_name']  ?? 'N/A';
$s_email    = $_SESSION['user_email'] ?? '';

// ── HELPER FUNCTIONS ─────────────────────────────────────────────
function getAudit($pdo, $seg_id) {
    $s = $pdo->prepare("SELECT * FROM segment_audits WHERE segment_id=? ORDER BY id DESC LIMIT 1");
    $s->execute([$seg_id]);
    return $s->fetch(PDO::FETCH_ASSOC);
}
function getObs($pdo, $aid) {
    $s = $pdo->prepare("SELECT SUM(partial_obstructions) p, SUM(total_obstructions) t, SUM(cyclist_slowed) sl FROM obstructions WHERE audit_id=?");
    $s->execute([$aid]);
    return $s->fetch(PDO::FETCH_ASSOC);
}
function getInts($pdo, $aid) {
    $s = $pdo->prepare("SELECT * FROM intersections WHERE audit_id=?");
    $s->execute([$aid]);
    return $s->fetchAll(PDO::FETCH_ASSOC);
}
function calcScore($row, $obs, $ints) {
    if (!$row) return null;
    $partial = (float)($obs['p']  ?? 0);
    $totalO  = (float)($obs['t']  ?? 0);
    $slowed  = (float)($obs['sl'] ?? 0);

    // Safety
    $bufP  = ($row['buffer_zone']       === 'None')    ? 100 : 0;
    $lgt   = $row['light_after_sunset'] ?? 'No';
    $ltP   = $lgt==='Yes' ? 0 : ($lgt==='Partial' ? 40 : 100);
    $absDev= count(array_filter($ints, fn($i)=>($i['traffic_device']??'')==='Absent'));
    $trP   = $absDev===0?0:($absDev===1?50:($absDev<=3?75:100));
    $partP = $partial<5?0:($partial<=10?50:100);
    $safety= ($bufP+$ltP+$trP+$partP)/4;

    // Continuity
    $noR  = count(array_filter($ints, fn($i)=>($i['off_ramp']??'')==='No Ramp'||($i['on_ramp']??'')==='No Ramp'));
    $rP   = $noR===0?0:($noR>=5?100:($noR>=3?50:25));
    $noS  = count(array_filter($ints, fn($i)=>($i['markings']??'')==='Absent'||($i['signage']??'')==='Absent'));
    $sP   = $noS===0?0:($noS===1?50:($noS<=3?75:100));
    $obsP = $totalO<5?0:($totalO<=10?50:100);
    $cont = ($rP+$sP+$obsP)/3;

    // Comfort
    $surfP = ($row['surface_material']==='Interlock Blocks')?100:0;
    $slwP  = $slowed<5?0:($slowed<=10?50:($slowed<=20?75:100));
    $sh    = $row['shade']??'No';
    $shP   = $sh==='Yes'?0:($sh==='Partial'?50:100);
    $comf  = ($surfP+$slwP+$shP)/3;

    $pen   = ($safety+$cont+$comf)/3;
    $final = max(0, round(100-$pen, 1));
    return [
        'final'      => $final,
        'safety'     => round(100-$safety,1),
        'continuity' => round(100-$cont,1),
        'comfort'    => round(100-$comf,1),
    ];
}
function ratingInfo($s) {
    if ($s>=80) return ['Good',    '#15803d','#dcfce7','🟢'];
    if ($s>=50) return ['Moderate','#d97706','#fef3c7','🟡'];
    return              ['Poor',   '#dc2626','#fee2e2','🔴'];
}
function bar($pct,$color,$w=180,$h=10) {
    $fw=max(0,min($w,round($w*$pct/100)));
    return "<svg width='{$w}' height='{$h}' style='vertical-align:middle'>"
          ."<rect width='{$w}' height='{$h}' rx='5' fill='#e8f5d0'/>"
          ."<rect width='{$fw}' height='{$h}' rx='5' fill='{$color}'/>"
          ."</svg>";
}

// ── BUILD SEGMENT DATA ────────────────────────────────────────────
$seg_data = []; $tw=0; $ws=0;
$observations=[]; $critical=[]; $recs_flags=[];

foreach ($segs as $seg) {
    $audit = getAudit($pdo, $seg['id']);
    $obs   = $audit ? getObs($pdo,$audit['id'])  : ['p'=>0,'t'=>0,'sl'=>0];
    $ints  = $audit ? getInts($pdo,$audit['id']) : [];
    $sc    = calcScore($audit, $obs, $ints);
    $len   = (float)($seg['length']??500);

    if ($sc!==null) { $ws+=$sc['final']*$len; $tw+=$len; }

    $seg_data[] = ['seg'=>$seg,'audit'=>$audit,'obs'=>$obs,'ints'=>$ints,'sc'=>$sc,'len'=>$len];

    if ($audit) {
        if ((float)($obs['t']??0)>5)              $observations[]="Segment {$seg['id']}: High obstruction count (".((int)$obs['t'])." total)";
        if (($audit['light_after_sunset']??'')=='No') $observations[]="Segment {$seg['id']}: No after-sunset lighting";
        if (($audit['cycle_track_missing']??'')=='Yes') { $observations[]="Segment {$seg['id']}: Cycle track section missing"; $critical[]="Missing cycle track in Segment {$seg['id']}"; $recs_flags['missing']=true; }
        if (($audit['buffer_zone']??'')=='None')  $observations[]="Segment {$seg['id']}: No buffer zone";
        $noRamp=count(array_filter($ints,fn($i)=>($i['off_ramp']??'')==='No Ramp'||($i['on_ramp']??'')==='No Ramp'));
        if ($noRamp>0) { $observations[]="Segment {$seg['id']}: {$noRamp} intersection(s) missing ramps"; if($noRamp>=2)$critical[]="Unsafe intersections in Segment {$seg['id']} — {$noRamp} missing ramps"; $recs_flags['ramps']=true; }
        if (($audit['people_walking']??'')=='Yes') { $observations[]="Segment {$seg['id']}: Pedestrians walking on cycle track"; }
        if ($sc && $sc['final']<40)               $critical[]="Segment {$seg['id']} scored {$sc['final']}/100 — critically poor";
        if (($audit['light_after_sunset']??'')=='No') $recs_flags['lighting']=true;
        if (($audit['buffer_zone']??'')=='None')  $recs_flags['buffer']=true;
    }
}

$road_score = $tw>0 ? round($ws/$tw,1) : 0;
[$rl,$rc,$rbg,$ri] = ratingInfo($road_score);

$recs=['Conduct regular maintenance and remove all obstructions from the cycle track.','Install signage at intersections indicating the presence of the cycle track.','Repair damaged or uneven surface sections to improve cycling comfort.'];
if ($recs_flags['missing']??false) $recs[]='Construct missing cycle track sections to restore full network continuity.';
if ($recs_flags['ramps']??false)   $recs[]='Build on/off ramps at all intersection points for smooth transitions.';
if ($recs_flags['lighting']??false) $recs[]='Install functional street lighting along all unlit segments for night safety.';
if ($recs_flags['buffer']??false)  $recs[]='Construct buffer zones (bollards/raised curbs) to separate cycle track from vehicular traffic.';
$recs[]='Enforce no-encroachment rules and remove parked vehicles from the cycle track.';

$cnt=0; $as=$ac=$acf=0;
foreach($seg_data as $d){if($d['sc']){$as+=$d['sc']['safety'];$ac+=$d['sc']['continuity'];$acf+=$d['sc']['comfort'];$cnt++;}}
if($cnt){$as=round($as/$cnt,1);$ac=round($ac/$cnt,1);$acf=round($acf/$cnt,1);}

$pending = count(array_filter($seg_data, fn($d)=>!$d['audit']));
$audited = count($seg_data)-$pending;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cycle Track Audit Report — <?= htmlspecialchars($road_name) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--g:#3d7a1f;--gl:#86B93B;--gp:#edf7d6;--gd:#1e3d0d;--ink:#181f10;--gray:#5e6b54;--bd:#d6e8c0;--cream:#f7f4ef}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:#f0f0e8;color:var(--ink);font-size:10pt;line-height:1.5}

/* print bar */
.pb-bar{background:var(--gd);color:#fff;padding:10px 48px;display:flex;align-items:center;justify-content:space-between;max-width:900px;margin:0 auto 0}
.pb-bar p{font-size:.8rem;color:rgba(255,255,255,.7)}
.pb-btn{background:var(--gl);color:#fff;border:none;padding:8px 20px;border-radius:7px;font-family:'DM Sans',sans-serif;font-weight:700;font-size:.85rem;cursor:pointer;display:flex;align-items:center;gap:7px}
.pb-btn:hover{background:#6eaf30}
@media print{.pb-bar{display:none}.page{box-shadow:none;margin:0;padding:20px 28px}}

/* page */
.page{max-width:900px;margin:0 auto;background:#fff;padding:40px 48px;box-shadow:0 8px 32px rgba(0,0,0,.12)}

/* header */
.hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:26px;padding-bottom:18px;border-bottom:2.5px solid var(--g)}
.hdr-lbl{font-size:.62rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gl);margin-bottom:5px}
.hdr-title{font-family:'Playfair Display',serif;font-size:1.45rem;font-weight:800;color:var(--ink);margin-bottom:2px}
.hdr-road{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--g);margin-bottom:10px}
.hdr-meta{display:flex;flex-direction:column;gap:3px}
.hdr-meta span{font-size:.74rem;color:var(--gray)}
.hdr-meta strong{color:var(--ink)}
.logo-box{width:68px;height:68px;background:var(--gd);border-radius:13px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;flex-shrink:0}
.logo-box svg{width:28px;height:28px;fill:#fff}
.logo-box span{font-size:.58rem;color:rgba(255,255,255,.8);font-weight:700;letter-spacing:.07em}
.logo-meta{text-align:right;font-size:.62rem;color:var(--gray);margin-top:6px}

/* section */
.sec{margin-bottom:20px}
.sh{display:flex;align-items:center;gap:8px;margin-bottom:10px;padding-bottom:6px;border-bottom:1.5px solid var(--bd)}
.sh-num{width:22px;height:22px;background:var(--g);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;flex-shrink:0}
.sh h3{font-size:.84rem;font-weight:800;color:var(--ink);text-transform:uppercase;letter-spacing:.07em}

/* score hero */
.sc-hero{display:grid;grid-template-columns:190px 1fr;gap:18px;margin-bottom:4px}
.sc-left{background:<?= $rbg ?>;border:2px solid <?= $rc ?>;border-radius:13px;padding:20px;text-align:center}
.sc-icon{font-size:2.2rem;margin-bottom:5px}
.sc-num{font-family:'Playfair Display',serif;font-size:3rem;font-weight:800;color:<?= $rc ?>;line-height:1}
.sc-max{font-size:.72rem;color:var(--gray);margin-top:2px}
.sc-badge{display:inline-block;margin-top:7px;background:<?= $rc ?>;color:#fff;font-size:.68rem;font-weight:700;padding:3px 12px;border-radius:100px;text-transform:uppercase}
.sc-right{background:var(--cream);border-radius:13px;padding:18px;display:flex;flex-direction:column;justify-content:space-between}
.sc-rt{font-size:.67rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gray);margin-bottom:11px}
.dim-r{display:flex;align-items:center;gap:9px;margin-bottom:8px}
.dim-l{font-size:.76rem;font-weight:700;width:82px;flex-shrink:0}
.dim-v{font-size:.76rem;font-weight:800;width:34px;text-align:right;flex-shrink:0}
.sc-sum{margin-top:9px;padding-top:9px;border-top:1px solid var(--bd);font-size:.76rem;color:var(--gray);line-height:1.6}

/* summary box */
.sum-box{background:var(--cream);border-radius:10px;padding:13px 15px;border-left:4px solid var(--g);font-size:.8rem;line-height:1.75}

/* segment table */
.st{width:100%;border-collapse:collapse;font-size:.76rem}
.st th{background:var(--gd);color:#fff;padding:7px 9px;text-align:left;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.st td{padding:7px 9px;border-bottom:1px solid var(--bd);vertical-align:middle}
.st tr:nth-child(even) td{background:#fafdf6}
.st tr:last-child td{border-bottom:none}
.sc-cell{font-family:'Playfair Display',serif;font-size:.92rem;font-weight:800}
.chip{display:inline-block;padding:2px 8px;border-radius:100px;font-size:.63rem;font-weight:700}

/* dim cards */
.dg{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.dc{border-radius:10px;padding:13px;border:1.5px solid var(--bd)}
.dc.s{border-top:3px solid #3b82f6;background:#eff6ff}
.dc.c{border-top:3px solid var(--gl);background:var(--gp)}
.dc.f{border-top:3px solid #f59e0b;background:#fef3c7}
.dc-i{font-size:1.1rem;margin-bottom:3px}
.dc-t{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--gray);margin-bottom:5px}
.dc-n{font-family:'Playfair Display',serif;font-size:1.3rem;font-weight:800;color:var(--ink)}
.dc-x{font-size:.65rem;color:var(--gray)}
.dc-its{margin-top:7px;display:flex;flex-direction:column;gap:3px}
.dc-it{font-size:.7rem;color:var(--gray);padding-left:10px;position:relative}
.dc-it::before{content:'•';position:absolute;left:0;color:var(--g);font-weight:900}

/* obs */
.obs-l{display:flex;flex-direction:column;gap:5px}
.obs-i{display:flex;gap:7px;align-items:flex-start;padding:6px 10px;background:#fafdf6;border-radius:6px;border-left:3px solid var(--gl);font-size:.76rem}
.crit-i{display:flex;gap:7px;align-items:flex-start;padding:6px 10px;background:#fff5f5;border-radius:6px;border-left:3px solid #dc2626;font-size:.76rem;color:#7f1d1d}

/* recs */
.rec-l{display:flex;flex-direction:column;gap:5px}
.rec-i{display:flex;gap:9px;align-items:flex-start;padding:7px 11px;background:var(--gp);border-radius:6px;font-size:.76rem;color:var(--gd)}
.rec-n{width:19px;height:19px;border-radius:50%;background:var(--g);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;flex-shrink:0;margin-top:1px}

/* seg detail */
.sd{margin-bottom:12px;border:1px solid var(--bd);border-radius:10px;overflow:hidden;break-inside:avoid}
.sd-h{display:flex;align-items:center;justify-content:space-between;padding:9px 13px;background:var(--cream)}
.sd-hl h4{font-size:.8rem;font-weight:800}
.sd-hl p{font-size:.68rem;color:var(--gray)}
.sd-sc .sn{font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:800}
.sd-b{padding:11px 13px;display:grid;grid-template-columns:1fr 1fr;gap:9px}
.sd-dims{display:flex;gap:5px;margin-bottom:8px}
.sd-d{flex:1;text-align:center;padding:5px;background:#fff;border-radius:7px;border:1px solid var(--bd)}
.sd-dn{font-size:.6rem;font-weight:700;color:var(--gray);text-transform:uppercase;margin-bottom:1px}
.sd-dv{font-size:.84rem;font-weight:800}
.sd-f{font-size:.7rem;color:var(--gray);display:flex;justify-content:space-between;padding:2px 0;border-bottom:1px solid rgba(0,0,0,.04)}
.sd-f strong{color:var(--ink)}

/* footer */
.rft{margin-top:26px;padding-top:12px;border-top:2px solid var(--g);display:flex;justify-content:space-between;align-items:flex-end}
.rf-p{font-family:'Playfair Display',serif;font-size:.8rem;font-weight:700;color:var(--g);margin-bottom:2px}
.rf-s{font-size:.66rem;color:var(--gray)}
.rf-r{text-align:right;font-size:.66rem;color:var(--gray);line-height:1.7}

.pb{page-break-before:always;padding-top:14px}
.no-data{text-align:center;padding:32px;color:var(--gray);font-size:.88rem;background:var(--cream);border-radius:10px;border:1px dashed var(--bd)}
</style>
</head>
<body>

<div class="pb-bar">
  <p>CycleAudit Report &nbsp;|&nbsp; <?= htmlspecialchars($road_name) ?> &nbsp;|&nbsp; Score: <?= $road_score ?>/100 (<?= $rl ?>)</p>
  <button class="pb-btn" onclick="window.print()">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print / Save as PDF
  </button>
</div>

<div class="page">

<!-- 1. HEADER -->
<div class="hdr">
  <div>
    <div class="hdr-lbl">Parisar Cycle Track Audit Programme</div>
    <div class="hdr-title">Cycle Track Audit Report</div>
    <div class="hdr-road"><?= htmlspecialchars($road_name) ?></div>
    <div class="hdr-meta">
      <span><strong>Route:</strong> <?= htmlspecialchars($road_start) ?> → <?= htmlspecialchars($road_end) ?></span>
      <span><strong>Total Length:</strong> <?= number_format((float)$road_length) ?> m &nbsp;|&nbsp; <strong>Segments:</strong> <?= count($segs) ?> (<?= $audited ?> audited<?= $pending>0?", {$pending} pending":'' ?>)</span>
      <span><strong>Date of Audit:</strong> <?= $audit_date ?></span>
      <span><strong>Surveyor:</strong> <?= htmlspecialchars($surveyor) ?><?= $s_email?" &lt;".htmlspecialchars($s_email)."&gt;":'' ?></span>
    </div>
  </div>
  <div>
    <div class="logo-box">
      <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.3 7 13 7 13s7-7.7 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
      <span>PARISAR</span>
    </div>
    <div class="logo-meta">parisar.org<br>Pune, Maharashtra</div>
  </div>
</div>

<!-- 2. FINAL ROAD SCORE -->
<div class="sec">
  <div class="sh"><div class="sh-num">2</div><h3>Final Road Score</h3></div>
  <div class="sc-hero">
    <div class="sc-left">
      <div class="sc-icon"><?= $ri ?></div>
      <div class="sc-num"><?= $road_score ?></div>
      <div class="sc-max">out of 100</div>
      <div class="sc-badge"><?= $rl ?></div>
    </div>
    <div class="sc-right">
      <div>
        <div class="sc-rt">Score Breakdown by Dimension</div>
        <div class="dim-r"><span class="dim-l">🛡️ Safety</span><?= bar($as,'#3b82f6') ?><span class="dim-v"><?= $as ?></span></div>
        <div class="dim-r"><span class="dim-l">🔗 Continuity</span><?= bar($ac,'#86B93B') ?><span class="dim-v"><?= $ac ?></span></div>
        <div class="dim-r"><span class="dim-l">🌿 Comfort</span><?= bar($acf,'#f59e0b') ?><span class="dim-v"><?= $acf ?></span></div>
      </div>
      <div class="sc-sum">
        <?php
        if($road_score>=80) echo "This road's cycle track is in <strong>Good</strong> condition overall. Minor targeted improvements can maintain quality.";
        elseif($road_score>=50) echo "This road is in <strong>Moderate</strong> condition — usable for experienced cyclists but with significant issues that deter regular use.";
        else echo "This road is in <strong>Poor</strong> condition and requires urgent intervention to make it safe and accessible for everyday cyclists.";
        ?>
      </div>
    </div>
  </div>
</div>

<!-- 3. QUICK SUMMARY -->
<div class="sec">
  <div class="sh"><div class="sh-num">3</div><h3>Quick Summary</h3></div>
  <div class="sum-box">
    <?php
    $worst=null;$ws2=999; $best=null;$bs2=-1;
    foreach($seg_data as $d){if($d['sc']){if($d['sc']['final']<$ws2){$ws2=$d['sc']['final'];$worst=$d;}if($d['sc']['final']>$bs2){$bs2=$d['sc']['final'];$best=$d;}}}
    ?>
    <strong><?= htmlspecialchars($road_name) ?></strong> was audited across <strong><?= count($segs) ?> segments</strong>
    covering approximately <strong><?= number_format((float)$road_length) ?> metres</strong> of cycle track.
    The overall road score is <strong><?= $road_score ?>/100</strong>, rated <strong><?= $rl ?></strong>.
    <?php if($worst): ?>Weakest segment is <strong>Segment <?= $worst['seg']['id'] ?></strong> (<?= $worst['sc']['final'] ?>/100);<?php endif; ?>
    <?php if($best): ?> strongest is <strong>Segment <?= $best['seg']['id'] ?></strong> (<?= $best['sc']['final'] ?>/100).<?php endif; ?>
    <?php
    if($as<$ac&&$as<$acf) echo " <strong>Safety</strong> is the primary concern — inadequate lighting and missing buffer zones are key penalty drivers.";
    elseif($ac<$as&&$ac<$acf) echo " <strong>Continuity</strong> is the primary concern — obstructions and missing ramps break cycling flow.";
    else echo " <strong>Comfort</strong> is the primary concern — surface quality, pedestrian conflict, and lack of shade reduce usability.";
    ?>
  </div>
</div>

<!-- 4. SEGMENT-WISE SCORES -->
<div class="sec">
  <div class="sh"><div class="sh-num">4</div><h3>Segment-Wise Scores</h3></div>
  <?php if(empty($seg_data)): ?>
  <div class="no-data">No segment data found in database for this road.</div>
  <?php else: ?>
  <table class="st">
    <thead><tr><th>#</th><th>Route</th><th>Length</th><th>Safety</th><th>Continuity</th><th>Comfort</th><th>Final</th><th>Rating</th></tr></thead>
    <tbody>
    <?php foreach($seg_data as $d):
      $s=$d['seg'];$sc=$d['sc'];
      if($sc){[$rl2,$rc2,$rb2]=[ratingInfo($sc['final'])[0],ratingInfo($sc['final'])[1],ratingInfo($sc['final'])[2]];}
    ?>
    <tr>
      <td style="font-weight:700"><?= $s['id'] ?></td>
      <td style="font-size:.7rem;max-width:160px"><?= htmlspecialchars($s['start_label']??'—') ?> → <?= htmlspecialchars($s['end_label']??'—') ?></td>
      <td><?= number_format($d['len']) ?>m</td>
      <?php if($sc): ?>
      <td><?= bar($sc['safety'],'#3b82f6',50,8) ?> <span style="font-size:.7rem"><?= $sc['safety'] ?></span></td>
      <td><?= bar($sc['continuity'],'#86B93B',50,8) ?> <span style="font-size:.7rem"><?= $sc['continuity'] ?></span></td>
      <td><?= bar($sc['comfort'],'#f59e0b',50,8) ?> <span style="font-size:.7rem"><?= $sc['comfort'] ?></span></td>
      <td><span class="sc-cell" style="color:<?= $rc2 ?>"><?= $sc['final'] ?></span></td>
      <td><span class="chip" style="background:<?= $rb2 ?>;color:<?= $rc2 ?>"><?= $rl2 ?></span></td>
      <?php else: ?>
      <td colspan="5" style="color:#aaa;font-size:.7rem;font-style:italic">Pending — not yet audited</td>
      <td><span class="chip" style="background:#f3f4f6;color:#9ca3af">Pending</span></td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- 4b. DIMENSION BREAKDOWN -->
<div class="sec">
  <div class="sh"><div class="sh-num">4b</div><h3>Score Breakdown by Dimension</h3></div>
  <div class="dg">
    <div class="dc s"><div class="dc-i">🛡️</div><div class="dc-t">Safety</div><div><span class="dc-n"><?= $as ?></span><span class="dc-x"> / 100</span></div><div class="dc-its"><div class="dc-it">Buffer zone presence</div><div class="dc-it">After-sunset lighting</div><div class="dc-it">Intersection traffic devices</div><div class="dc-it">Partial obstruction density</div></div></div>
    <div class="dc c"><div class="dc-i">🔗</div><div class="dc-t">Continuity</div><div><span class="dc-n"><?= $ac ?></span><span class="dc-x"> / 100</span></div><div class="dc-its"><div class="dc-it">Missing ramps at intersections</div><div class="dc-it">Absent markings and signage</div><div class="dc-it">Total obstruction count</div><div class="dc-it">Missing track sections</div></div></div>
    <div class="dc f"><div class="dc-i">🌿</div><div class="dc-t">Comfort</div><div><span class="dc-n"><?= $acf ?></span><span class="dc-x"> / 100</span></div><div class="dc-its"><div class="dc-it">Surface material type</div><div class="dc-it">Cyclist slowed by obstructions</div><div class="dc-it">Shade availability</div><div class="dc-it">Footpath quality rating</div></div></div>
  </div>
</div>

<!-- 5. KEY OBSERVATIONS -->
<div class="sec">
  <div class="sh"><div class="sh-num">5</div><h3>Key Observations</h3></div>
  <?php if(!empty($observations)): ?>
  <div class="obs-l">
    <?php foreach(array_slice($observations,0,8) as $o): ?>
    <div class="obs-i"><span>📌</span><span><?= htmlspecialchars($o) ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="obs-i" style="border-left-color:#16a34a;background:#f0fdf4"><span>✅</span><span>No major issues recorded. All segments appear to be in acceptable condition.</span></div>
  <?php endif; ?>
</div>

<!-- 6. CRITICAL ISSUES -->
<div class="sec">
  <div class="sh"><div class="sh-num">6</div><h3>Critical Issues</h3></div>
  <?php if(!empty($critical)): ?>
  <div class="obs-l">
    <?php foreach($critical as $c): ?>
    <div class="crit-i"><span>⛔</span><span><?= htmlspecialchars($c) ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="obs-i" style="border-left-color:#16a34a;background:#f0fdf4"><span>✅</span><span>No critical issues identified across audited segments.</span></div>
  <?php endif; ?>
</div>

<!-- 7. RECOMMENDATIONS -->
<div class="sec">
  <div class="sh"><div class="sh-num">7</div><h3>Recommendations</h3></div>
  <div class="rec-l">
    <?php foreach($recs as $i=>$r): ?>
    <div class="rec-i"><div class="rec-n"><?= $i+1 ?></div><span><?= htmlspecialchars($r) ?></span></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- SEGMENT DETAIL CARDS -->
<div class="sec pb">
  <div class="sh"><div class="sh-num">+</div><h3>Segment Detail Cards</h3></div>
  <?php foreach($seg_data as $d):
    $seg=$d['seg'];$audit=$d['audit'];$sc=$d['sc'];
    if(!$audit) continue;
    [$rl3,$rc3,$rb3]=ratingInfo($sc['final']??0);
  ?>
  <div class="sd">
    <div class="sd-h">
      <div class="sd-hl">
        <h4>Segment <?= $seg['id'] ?> &nbsp;·&nbsp; <?= htmlspecialchars($audit['start_landmark']??$seg['start_label']??'—') ?> → <?= htmlspecialchars($audit['end_landmark']??$seg['end_label']??'—') ?></h4>
        <p>GPS: <?= htmlspecialchars($audit['gps_start']??'N/A') ?> → <?= htmlspecialchars($audit['gps_end']??'N/A') ?> &nbsp;|&nbsp; Length: <?= number_format($d['len']) ?>m</p>
      </div>
      <?php if($sc): ?>
      <div class="sd-sc" style="text-align:right">
        <div class="sn" style="color:<?= $rc3 ?>"><?= $sc['final'] ?><span style="font-size:.58rem;color:var(--gray);font-weight:400">/100</span></div>
        <span class="chip" style="background:<?= $rb3 ?>;color:<?= $rc3 ?>"><?= $rl3 ?></span>
      </div>
      <?php endif; ?>
    </div>
    <?php if($sc): ?>
    <div class="sd-b">
      <div>
        <div class="sd-dims">
          <div class="sd-d"><div class="sd-dn">Safety</div><div class="sd-dv" style="color:#3b82f6"><?= $sc['safety'] ?></div></div>
          <div class="sd-d"><div class="sd-dn">Continuity</div><div class="sd-dv" style="color:#86B93B"><?= $sc['continuity'] ?></div></div>
          <div class="sd-d"><div class="sd-dn">Comfort</div><div class="sd-dv" style="color:#f59e0b"><?= $sc['comfort'] ?></div></div>
        </div>
        <div class="sd-f"><span>Surface Material</span><strong><?= htmlspecialchars($audit['surface_material']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Track Missing</span><strong><?= htmlspecialchars($audit['cycle_track_missing']??'N/A') ?><?= ($audit['missing_length']??0)>0?' ('.$audit['missing_length'].'m)':'' ?></strong></div>
        <div class="sd-f"><span>Buffer Zone</span><strong><?= htmlspecialchars($audit['buffer_zone']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Lighting</span><strong><?= htmlspecialchars($audit['light_after_sunset']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Shade</span><strong><?= htmlspecialchars($audit['shade']??'N/A') ?></strong></div>
      </div>
      <div>
        <div class="sd-f"><span>Cyclist Can Use</span><strong><?= htmlspecialchars($audit['cyclist_use']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Better Surface</span><strong><?= htmlspecialchars($audit['better_surface']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Signage Count</span><strong><?= (int)($audit['signage_count']??0) ?></strong></div>
        <div class="sd-f"><span>People Walking</span><strong><?= htmlspecialchars($audit['people_walking']??'N/A') ?></strong></div>
        <div class="sd-f"><span>Intersections</span><strong><?= count($d['ints']) ?></strong></div>
        <div class="sd-f"><span>Obstructions (total)</span><strong><?= (int)($d['obs']['t']??0) ?></strong></div>
        <?php if($audit['comments']): ?>
        <div style="margin-top:5px;font-size:.68rem;color:var(--gray);font-style:italic">"<?= htmlspecialchars($audit['comments']) ?>"</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- 8. FOOTER -->
<div class="rft">
  <div>
    <div class="rf-p">Parisar — Cycle Track Audit Programme</div>
    <div class="rf-s">parisar.org &nbsp;|&nbsp; Pune, Maharashtra</div>
  </div>
  <div class="rf-r">
    Road: <?= htmlspecialchars($road_name) ?><br>
    Final Score: <?= $road_score ?>/100 (<?= $rl ?>)<br>
    Generated: <?= date('d F Y, h:i A') ?><br>
    Surveyor: <?= htmlspecialchars($surveyor) ?>
  </div>
</div>

</div><!-- /page -->
</body>
</html>
