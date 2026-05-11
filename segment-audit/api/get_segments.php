<?php
require_once("../config/db.php");

$stmt = $pdo->query("SELECT * FROM segments ORDER BY id");
$segments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($segments);