<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['new_key'])) {
        $key = bin2hex(random_bytes(10));
        $expires = date('Y-m-d H:i:s', strtotime('+1 day'));
        $conn->prepare("INSERT INTO keys (key_value, expires_at) VALUES (?, ?)")->execute([$key, $expires]);
        echo "New Key: $key (Expires: $expires)";
    } elseif (isset($_POST['remove_key'])) {
        $conn->prepare("DELETE FROM keys WHERE key_value = ?")->execute([$_POST['remove_key']]);
        echo "Key Removed.";
    }
}

$keys = $conn->query("SELECT * FROM keys")->fetchAll(PDO::FETCH_ASSOC);
$logs = $conn->query("SELECT * FROM logs ORDER BY access_time DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Admin Panel</h2>
<form method="POST">
    <button type="submit" name="new_key">Generate New Key</button>
</form>

<h3>Active Keys</h3>
<ul>
<?php foreach ($keys as $k) {
    echo "<li>{$k['key_value']} - Expires: {$k['expires_at']} - Device: {$k['device_id']}
          <form method='POST' style='display:inline;'>
          <input type='hidden' name='remove_key' value='{$k['key_value']}'>
          <button type='submit'>Remove</button></form></li>";
} ?>
</ul>

<h3>Access Logs</h3>
<ul>
<?php foreach ($logs as $log) {
    echo "<li>Key: {$log['key_value']} - Device: {$log['device_id']} - Time: {$log['access_time']}</li>";
} ?>
</ul>
