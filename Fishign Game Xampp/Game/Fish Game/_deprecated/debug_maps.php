<?php
require 'db.php';

echo "<h2>Map Name Debugger</h2>";

// 1. Get DB Map Names
echo "<h3>Database Maps:</h3>";
$maps = $pdo->query("SELECT id, name FROM maps")->fetchAll(PDO::FETCH_ASSOC);
foreach ($maps as $map) {
    echo "ID: {$map['id']} | Name: '{$map['name']}'<br>";
}

// 2. Get API Keys
echo "<h3>API Map Keys:</h3>";
$api_url = "http://localhost:8080/api/fish/all";
$response = @file_get_contents($api_url);

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        foreach (array_keys($data) as $key) {
            echo "Key: '$key'<br>";
        }
    } else {
        echo "Failed to decode API JSON.<br>";
        var_dump($response);
    }
} else {
    echo "Failed to contact API.<br>";
}
?>
