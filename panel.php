<?php
$host = 'localhost';
$dbname = 'script_panel';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['key']) && isset($_POST['device_id'])) {
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
            echo json_encode(["status" => "success", "message" => "Access Granted"]);
            exit();
        } else {
            die(json_encode(["status" => "error", "message" => "Invalid or expired key"]));
        }
    } elseif (isset($_POST['new_key'])) {
        $key = bin2hex(random_bytes(10));
        $expires = date('Y-m-d H:i:s', strtotime('+1 day'));
        $conn->prepare("INSERT INTO keys (key_value, expires_at) VALUES (?, ?)")->execute([$key, $expires]);
        echo json_encode(["status" => "success", "key" => $key, "expires" => $expires]);
    } elseif (isset($_POST['remove_key'])) {
        $conn->prepare("DELETE FROM keys WHERE key_value = ?")->execute([$_POST['remove_key']]);
        echo json_encode(["status" => "success", "message" => "Key Removed"]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view'])) {
    $keys = $conn->query("SELECT * FROM keys")->fetchAll(PDO::FETCH_ASSOC);
    $logs = $conn->query("SELECT * FROM logs ORDER BY access_time DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["keys" => $keys, "logs" => $logs]);
} else {
    echo "<h2>Admin Panel</h2>
    <form method='POST'><button type='submit' name='new_key'>Generate New Key</button></form>
    <h3>Active Keys</h3><ul>";
    foreach ($conn->query("SELECT * FROM keys") as $k) {
        echo "<li>{$k['key_value']} - Expires: {$k['expires_at']} - Device: {$k['device_id']}
              <form method='POST' style='display:inline;'>
              <input type='hidden' name='remove_key' value='{$k['key_value']}'>
              <button type='submit'>Remove</button></form></li>";
    }
    echo "</ul>";
}
?>
