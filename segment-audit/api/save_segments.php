<?php
require_once("../config/db.php");
header('Content-Type: application/json');

try {
    $data     = json_decode(file_get_contents("php://input"), true);
    $road     = $data['road']     ?? null;
    $segments = $data['segments'] ?? [];

    if (!$road || !$road['name']) {
        echo json_encode(["success" => false, "error" => "Road data missing"]);
        exit;
    }

    $pdo->beginTransaction();

    // Safe cascade delete before re-inserting
    $segIds = $pdo->query("SELECT id FROM segments")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($segIds)) {
        $ph = implode(',', array_fill(0, count($segIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM segment_audits WHERE segment_id IN ($ph)");
        $stmt->execute($segIds);
        $auditIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($auditIds)) {
            $ph2 = implode(',', array_fill(0, count($auditIds), '?'));
            $pdo->prepare("DELETE FROM obstructions  WHERE audit_id IN ($ph2)")->execute($auditIds);
            $pdo->prepare("DELETE FROM intersections WHERE audit_id IN ($ph2)")->execute($auditIds);
            $pdo->prepare("DELETE FROM segment_audits WHERE id IN ($ph2)")->execute($auditIds);
        }
        $pdo->prepare("DELETE FROM segments WHERE id IN ($ph)")->execute($segIds);
    }

    $stmt = $pdo->prepare("
        INSERT INTO segments
            (id, road_name, road_start, road_end, road_length,
             road_gps_start, road_gps_end, road_method, road_segment_length,
             start_label, end_label, start_distance, end_distance, length, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    foreach ($segments as $seg) {
        $stmt->execute([
            $seg['id'],
            $road['name'],
            $road['start']         ?? '',
            $road['end']           ?? '',
            $road['length']        ?? 0,
            $road['gpsStart']      ?? '',
            $road['gpsEnd']        ?? '',
            $road['method']        ?? 'auto',
            $road['segmentLength'] ?? 0,
            $seg['startLandmark']  ?? '',
            $seg['endLandmark']    ?? '',
            $seg['startDistance']  ?? 0,
            $seg['endDistance']    ?? 0,
            $seg['length']         ?? 0
        ]);
    }

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>