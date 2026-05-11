<?php
require_once("../config/db.php");

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM segments ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows || count($rows) === 0) {
        echo json_encode(["road" => null, "segments" => []]);
        exit;
    }

    $first = $rows[0];
    $road  = [
        "name"          => $first['road_name'],
        "start"         => $first['road_start'],
        "end"           => $first['road_end'],
        "length"        => (float)$first['road_length'],
        "gpsStart"      => $first['road_gps_start'],
        "gpsEnd"        => $first['road_gps_end'],
        "method"        => $first['road_method'],
        "segmentLength" => (float)$first['road_segment_length']
    ];

    $segments = array_map(function($row) {
        return [
            "id"            => (int)$row['id'],
            "number"        => (int)$row['id'],
            "startDistance" => (float)$row['start_distance'],
            "endDistance"   => (float)$row['end_distance'],
            "length"        => (float)$row['length'],
            "startLandmark" => $row['start_label'],
            "endLandmark"   => $row['end_label'],
            "status"        => $row['status'],
            "auditData"     => $row['status'] === 'completed'
                                ? ["completedAt" => $row['completed_at'] ?? null]
                                : null
        ];
    }, $rows);

    echo json_encode(["road" => $road, "segments" => $segments]);

} catch (Exception $e) {
    echo json_encode(["road" => null, "segments" => [], "error" => $e->getMessage()]);
}
?>