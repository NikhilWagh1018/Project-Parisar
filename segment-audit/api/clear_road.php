<?php
require_once("../config/db.php");
header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    // Get all segment IDs first
    $segIds = $pdo->query("SELECT id FROM segments")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($segIds)) {
        $ph = implode(',', array_fill(0, count($segIds), '?'));

        // Get all audit IDs for those segments
        $stmt = $pdo->prepare("SELECT id FROM segment_audits WHERE segment_id IN ($ph)");
        $stmt->execute($segIds);
        $auditIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($auditIds)) {
            $ph2 = implode(',', array_fill(0, count($auditIds), '?'));
            $pdo->prepare("DELETE FROM obstructions  WHERE audit_id IN ($ph2)")->execute($auditIds);
            $pdo->prepare("DELETE FROM intersections WHERE audit_id IN ($ph2)")->execute($auditIds);
        }

        $pdo->prepare("DELETE FROM segment_audits WHERE segment_id IN ($ph)")->execute($segIds);
    }

    $pdo->exec("DELETE FROM segments");

    $pdo->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>