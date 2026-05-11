<?php
require_once("../config/db.php");

echo "<link rel='stylesheet' href='../css/view.css'>";
echo "<div class='container'>";

$segment_id = $_GET['segment_id'] ?? null;

if ($segment_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM segment_audits WHERE segment_id = ? ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$segment_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("
        SELECT seg.id AS segment_id, seg.status, sa.*
        FROM segments seg
        LEFT JOIN segment_audits sa
            ON sa.id = (SELECT id FROM segment_audits WHERE segment_id = seg.id ORDER BY id DESC LIMIT 1)
        ORDER BY seg.id
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getColor($score) {
    if ($score >= 80) return "#2ecc71";
    if ($score >= 50) return "#f1c40f";
    return "#e74c3c";
}

foreach ($rows as $row) {
    if (!$row['id']) {
        echo "<div class='card'>
                <div class='title'>Segment {$row['segment_id']}</div>
                <div class='status pending'>Pending — No audit yet</div>
              </div>";
        continue;
    }

    $id    = $row['id'];
    $start = htmlspecialchars($row['start_landmark'] ?? 'N/A');
    $end   = htmlspecialchars($row['end_landmark']   ?? 'N/A');

    echo "<div class='card'>";
    echo "<div class='title'>Segment {$row['segment_id']}</div>";
    echo "<p><b>Route:</b> $start &rarr; $end</p>";
    echo "<div class='status completed'>Completed</div>";

    // Fetch obstructions
    $stmtObs = $pdo->prepare("
        SELECT SUM(partial_obstructions) p, SUM(total_obstructions) t, SUM(cyclist_slowed) sl
        FROM obstructions WHERE audit_id=?
    ");
    $stmtObs->execute([$id]);
    $obs     = $stmtObs->fetch(PDO::FETCH_ASSOC);
    $partial = (float)($obs['p']  ?? 0);
    $totalObs= (float)($obs['t']  ?? 0);
    $slowed  = (float)($obs['sl'] ?? 0);

    // Fetch intersections
    $stmtInt = $pdo->prepare("SELECT * FROM intersections WHERE audit_id=?");
    $stmtInt->execute([$id]);
    $intersections = $stmtInt->fetchAll(PDO::FETCH_ASSOC);

    // ── SAFETY ──────────────────────────────────────────────────
    $bufferScore = ($row['buffer_zone'] === 'None') ? 100 : 0;
    $light       = $row['light_after_sunset'] ?? 'No';
    $lightScore  = $light==='Yes' ? 0 : ($light==='Partial' ? 50 : 100);
    // traffic_device removed — use ramp quality as proxy for intersection safety
    $noRampCount = 0;
    foreach ($intersections as $i) {
        if (($i['off_ramp'] ?? '') === 'No Ramp' || ($i['on_ramp'] ?? '') === 'No Ramp') $noRampCount++;
    }
    $trafficScore= $noRampCount===0 ? 0 : ($noRampCount===1 ? 50 : ($noRampCount<=3 ? 75 : 100));
    $partialScore= $partial<5 ? 0 : ($partial<=10 ? 50 : 100);
    $safety      = ($bufferScore + $lightScore + $trafficScore + $partialScore) / 4;

    // ── CONTINUITY ────────────────────────────────────────────────
    $missingRamps = $noRampCount;
    $rampScore    = $missingRamps===0 ? 0 : ($missingRamps>=5 ? 100 : ($missingRamps>=3 ? 50 : 25));

    $noSign = 0;
    foreach ($intersections as $i) {
        if (($i['markings'] ?? '')==='Absent' || ($i['signage'] ?? '')==='Absent') $noSign++;
    }
    $signScore   = $noSign===0 ? 0 : ($noSign===1 ? 50 : ($noSign<=3 ? 75 : 100));
    $totObsScore = $totalObs<5 ? 0 : ($totalObs<=10 ? 50 : 100);
    $continuity  = ($rampScore + $signScore + $totObsScore) / 3;

    // ── COMFORT ───────────────────────────────────────────────────
    $surfaceScore= ($row['surface_material']==='Interlock Blocks') ? 100 : 0;
    $slowScore   = $slowed<5 ? 0 : ($slowed<=10 ? 50 : ($slowed<=20 ? 75 : 100));
    $shade       = $row['shade'] ?? 'No';
    $shadeScore  = $shade==='Yes' ? 0 : ($shade==='Partial' ? 50 : 100);
    $comfort     = ($surfaceScore + $slowScore + $shadeScore) / 3;

    // ── FINAL ─────────────────────────────────────────────────────
    $finalScore = round(100 - (($safety + $continuity + $comfort) / 3), 2);
    $color      = getColor($finalScore);

    echo "<h4>Scores</h4>";
    echo "<p>Safety Penalty: " . round($safety,1) . " &nbsp; → &nbsp; Safety Score: " . round(100-$safety,1) . "</p>";
    echo "<div class='progress'><div class='bar' style='width:" . round(100-$safety) . "%; background:#3498db'>" . round(100-$safety,1) . "</div></div>";

    echo "<p>Continuity Penalty: " . round($continuity,1) . " &nbsp; → &nbsp; Continuity Score: " . round(100-$continuity,1) . "</p>";
    echo "<div class='progress'><div class='bar' style='width:" . round(100-$continuity) . "%; background:#9b59b6'>" . round(100-$continuity,1) . "</div></div>";

    echo "<p>Comfort Penalty: " . round($comfort,1) . " &nbsp; → &nbsp; Comfort Score: " . round(100-$comfort,1) . "</p>";
    echo "<div class='progress'><div class='bar' style='width:" . round(100-$comfort) . "%; background:#e67e22'>" . round(100-$comfort,1) . "</div></div>";

    echo "<h3 style='color:{$color}; margin-top:12px'>Final Score: {$finalScore} / 100</h3>";
    echo "</div>";
}

echo "</div>";
?>