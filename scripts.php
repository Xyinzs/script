<?php
include 'config.php';

$stmt = $conn->query("SELECT * FROM scripts");
$scripts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($scripts as $script) {
    echo "<h3>{$script['name']}</h3>";
    echo "<pre>{$script['content']}</pre>";
}
?>
