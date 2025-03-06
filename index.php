<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $key = $_POST['key'];
    $device_id = $_POST['device_id'];

    $stmt = $conn->prepare("SELECT * FROM keys WHERE key_value = ? AND expires_at > NOW()");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if ($row['device_id'] === NULL) {
            $conn->prepare("UPDATE keys SET device_id = ? WHERE key_value = ?")->execute([$device_id, $key]);
        } elseif ($row['device_id'] !== $device_id) {
            die("Key is locked to another device.");
        }

        $conn->prepare("INSERT INTO logs (key_value, device_id) VALUES (?, ?)")->execute([$key, $device_id]);
        header("Location: scripts.php");
        exit();
    } else {
        die("Invalid or expired key.");
    }
}
?>

<form method="POST">
    <input type="text" name="key" placeholder="Enter Access Key" required>
    <input type="text" name="device_id" placeholder="Enter Device ID" required>
    <button type="submit">Access</button>
</form>
