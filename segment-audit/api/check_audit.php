<?php
require_once("../config/db.php");

$stmt = $pdo->query("
    SELECT
        s.id,
        s.start_landmark,
        COUNT(DISTINCT o.id) as obstructions,
        COUNT(DISTINCT i.id) as intersections
    FROM segment_audits s
    LEFT JOIN obstructions o ON s.id = o.audit_id
    LEFT JOIN intersections i ON s.id = i.audit_id
    GROUP BY s.id
    ORDER BY s.id DESC
");

echo "<h2>Audit Debug Table</h2>";
while ($row = $stmt->fetch()) {
    echo "<p>
        Audit ID: {$row['id']} |
        " . htmlspecialchars($row['start_landmark']) . " |
        Obstructions: {$row['obstructions']} |
        Intersections: {$row['intersections']}
    </p>";
}
?>