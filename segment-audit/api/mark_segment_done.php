<?php
require_once("../config/db.php");

header('Content-Type: application/json');

try {
    $data       = json_decode(file_get_contents("php://input"), true);
    $segment_id = $data['segment_id'] ?? null;

    if (!$segment_id) {
        echo json_encode(["success" => false, "error" => "No segment_id provided"]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE segments 
        SET status = 'completed', completed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$segment_id]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>