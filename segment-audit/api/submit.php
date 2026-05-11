<?php
session_start();
require_once("../config/db.php");

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    $surveyor_id = $_SESSION['user_id'] ?? null;
    $segment_id  = $_POST['segment_id'] ?? null;

    if (!$segment_id) {
        echo json_encode(["success" => false, "error" => "No segment_id provided"]);
        exit;
    }

    // LOCK CHECK
    $stmt = $pdo->prepare("SELECT status FROM segments WHERE id = ?");
    $stmt->execute([$segment_id]);
    $segment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($segment && $segment['status'] === 'completed') {
        echo json_encode(["success" => false, "message" => "Segment already audited (LOCKED)"]);
        exit;
    }

    // VALIDATION
    foreach (['start_landmark','end_landmark','gps_start','gps_end'] as $field) {
        if (empty($_POST[$field])) throw new Exception("Required field '$field' is missing.");
    }

    // MAIN INSERT — includes surveyor_id
    $main_fields = [
        'start_landmark','end_landmark','gps_start','gps_end',
        'cycle_track_missing','missing_length','cyclist_use','better_surface',
        'surface_material','people_walking','signage_count','shade',
        'light_after_sunset','track_geometry','buffer_zone',
        'segment_width','segment_length','comments'
    ];
    $main_values  = array_map(fn($f) => $_POST[$f] ?? null, $main_fields);
    $placeholders = rtrim(str_repeat('?,', count($main_fields)), ',');

    $pdo->prepare("
        INSERT INTO segment_audits (segment_id, surveyor_id, " . implode(',', $main_fields) . ")
        VALUES (?, ?, $placeholders)
    ")->execute(array_merge([$segment_id, $surveyor_id], $main_values));

    $audit_id = $pdo->lastInsertId();

    // MARK SEGMENT DONE
    $pdo->prepare("UPDATE segments SET status='completed', completed_at=NOW() WHERE id=?")
        ->execute([$segment_id]);

    // JSON CHECKBOX FIELDS
    $pdo->prepare("
        UPDATE segment_audits SET surface_issues=?, overhead_issues=?, footpath_rating=? WHERE id=?
    ")->execute([
        json_encode($_POST['surface_issues']  ?? []),
        json_encode($_POST['overhead_issues'] ?? []),
        json_encode($_POST['footpath_rating'] ?? []),
        $audit_id
    ]);

    // OBSTRUCTIONS
    $obs_types = [
        'fixed'   => ['Trees','Poles','CCTV','TrafficSignal','SignBoard','TelephonePanel',
                      'ElectricalPanel','BusStand','BuiltEncroachment','Bollards',
                      'PropertyEntrance','UtilityChambers'],
        'movable' => ['Hawkers','GarbageBins','ConstructionMaterial',
                      'TrafficBarricade','PeopleSitting','Hoardings'],
        'parked'  => ['ReligiousLandmark','RestaurantEatery','AutoGarage',
                      'CommercialRetailShops','OnStreetVending','PublicSpace']
    ];

    foreach ($obs_types as $category => $types) {
        foreach ($types as $obs) {
            $slowed  = (int)($_POST["{$category}_{$obs}_slowed"]  ?? 0);
            $partial = (int)($_POST["{$category}_{$obs}_partial"] ?? 0);
            $total   = (int)($_POST["{$category}_{$obs}_total"]   ?? 0);
            if ($slowed + $partial + $total > 0) {
                $pdo->prepare("
                    INSERT INTO obstructions
                        (audit_id, segment_id, obstruction_category, obstruction_type,
                         cyclist_slowed, partial_obstructions, total_obstructions)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([$audit_id, $segment_id, $category, $obs, $slowed, $partial, $total]);
            }
        }
    }

    // INTERSECTIONS — only the 6 columns in clean schema (no traffic_device etc.)
    $intersections = json_decode($_POST['intersections'] ?? '[]', true);
    foreach ($intersections as $idx => $i) {
        $pdo->prepare("
            INSERT INTO intersections
                (audit_id, segment_id, intersection_num,
                 gps_coords, landmark_name, off_ramp, on_ramp, markings, signage)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $audit_id, $segment_id, $idx + 1,
            $i['gps_coords']    ?? null,
            $i['landmark_name'] ?? null,
            $i['off_ramp']      ?? null,
            $i['on_ramp']       ?? null,
            $i['markings']      ?? null,
            $i['signage']       ?? null
        ]);
    }

    // FOOTPATH SCORE
    $pdo->prepare("UPDATE segment_audits SET footpath_score=? WHERE id=?")
        ->execute([count($_POST['footpath_rating'] ?? []) * 20, $audit_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'audit_id' => $audit_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>