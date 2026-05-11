<?php
require_once("../config/db.php");

$stmt = $pdo->query("
    SELECT seg.id AS segment_id, seg.length, sa.*
    FROM segments seg
    LEFT JOIN segment_audits sa
        ON sa.id = (SELECT id FROM segment_audits WHERE segment_id = seg.id ORDER BY id DESC LIMIT 1)
    ORDER BY seg.id
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Scoring function — consistent with view.php and report.php ──
function calculateScore($row, $pdo) {
    if (!$row['id']) return null;

    $stmtObs = $pdo->prepare("
        SELECT SUM(partial_obstructions) p, SUM(total_obstructions) t, SUM(cyclist_slowed) sl
        FROM obstructions WHERE audit_id=?
    ");
    $stmtObs->execute([$row['id']]);
    $obs     = $stmtObs->fetch(PDO::FETCH_ASSOC);
    $partial = (float)($obs['p']  ?? 0);
    $totalO  = (float)($obs['t']  ?? 0);
    $slowed  = (float)($obs['sl'] ?? 0);

    $stmtInt = $pdo->prepare("SELECT * FROM intersections WHERE audit_id=?");
    $stmtInt->execute([$row['id']]);
    $ints = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

    // Safety
    $bufP  = ($row['buffer_zone'] === 'None') ? 100 : 0;
    $lgt   = $row['light_after_sunset'] ?? 'No';
    $ltP   = $lgt==='Yes' ? 0 : ($lgt==='Partial' ? 50 : 100);
    $noRamp= count(array_filter($ints, fn($i)=>($i['off_ramp']??'')==='No Ramp'||($i['on_ramp']??'')==='No Ramp'));
    $trP   = $noRamp===0?0:($noRamp===1?50:($noRamp<=3?75:100));
    $partP = $partial<5?0:($partial<=10?50:100);
    $safety= ($bufP+$ltP+$trP+$partP)/4;

    // Continuity
    $rP    = $noRamp===0?0:($noRamp>=5?100:($noRamp>=3?50:25));
    $noS   = count(array_filter($ints, fn($i)=>($i['markings']??'')==='Absent'||($i['signage']??'')==='Absent'));
    $sP    = $noS===0?0:($noS===1?50:($noS<=3?75:100));
    $obsP  = $totalO<5?0:($totalO<=10?50:100);
    $cont  = ($rP+$sP+$obsP)/3;

    // Comfort
    $surfP = ($row['surface_material']==='Interlock Blocks')?100:0;
    $slwP  = $slowed<5?0:($slowed<=10?50:($slowed<=20?75:100));
    $sh    = $row['shade']??'No';
    $shP   = $sh==='Yes'?0:($sh==='Partial'?50:100);
    $comf  = ($surfP+$slwP+$shP)/3;

    return round(100 - (($safety+$cont+$comf)/3), 1);
}

$allCompleted = !empty($rows) && count(array_filter($rows, fn($r)=>!$r['id'])) === 0;

$totalWeighted = 0; $totalLength = 0;
if ($allCompleted) {
    foreach ($rows as $row) {
        $score = calculateScore($row, $pdo);
        if ($score !== null) {
            $len = (float)($row['length'] ?? 1);
            $totalWeighted += $score * $len;
            $totalLength   += $len;
        }
    }
}
$roadScore = ($allCompleted && $totalLength) ? round($totalWeighted / $totalLength, 1) : 0;

function ratingColor($s) {
    if ($s >= 80) return ['Good',     '#27ae60'];
    if ($s >= 50) return ['Moderate', '#f39c12'];
    return               ['Poor',     '#e74c3c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Road Audit Result</title>
  <!-- FIXED: was ../css/style.css (missing file) → now view.css -->
  <link rel="stylesheet" href="../css/view.css">
  <style>
    h1{font-family:'Segoe UI',sans-serif;color:#2d5a18;margin-bottom:20px}
    .final-box{background:#fff;border-radius:12px;padding:28px;margin-top:20px;
      box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center}
    .final-score{font-size:3rem;font-weight:800;line-height:1}
    .final-label{font-size:1rem;font-weight:600;margin-top:6px}
    .warn-box{background:#fff8e1;border:1px solid #ffe082;border-radius:10px;
      padding:16px;color:#b8860b;font-weight:600;margin-top:20px;text-align:center}
    .back-btn{display:inline-block;margin-top:20px;background:#3d7a1f;color:#fff;
      padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700}
    .back-btn:hover{background:#2d5a18}
  </style>
</head>
<body>
<div class="container">
  <h1>Road Audit Result</h1>

  <?php foreach ($rows as $row):
    $score = calculateScore($row, $pdo);
    $sid   = $row['segment_id'];
    if ($score !== null) [$rl,$rc] = ratingColor($score);
  ?>
  <div class="card">
    <div class="title">Segment <?= $sid ?></div>
    <?php if ($score === null): ?>
      <div class="status pending">Pending — Not yet audited</div>
    <?php else: ?>
      <p><strong>Score:</strong>
        <span style="color:<?= $rc ?>;font-weight:800;font-size:1.1rem"><?= $score ?>/100</span>
        &nbsp;<span style="color:<?= $rc ?>; font-weight:600">(<?= $rl ?>)</span>
      </p>
      <a href="view.php?segment_id=<?= $sid ?>" style="font-size:.85rem;color:#3d7a1f">View Details →</a>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <hr style="margin:24px 0; border-color:#e0ead4">

  <?php if (!$allCompleted): ?>
    <div class="warn-box">Complete all segment audits to view the final road score.</div>
  <?php else: ?>
    <?php [$rl2,$rc2] = ratingColor($roadScore); ?>
    <div class="final-box">
      <div style="font-size:.8rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#9aaa88;margin-bottom:8px">Final Road Score</div>
      <div class="final-score" style="color:<?= $rc2 ?>"><?= $roadScore ?></div>
      <div style="font-size:.9rem;color:#9aaa88">out of 100</div>
      <div class="final-label" style="color:<?= $rc2 ?>"><?= $rl2 ?></div>
    </div>
  <?php endif; ?>

  <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>
</body>
</html>