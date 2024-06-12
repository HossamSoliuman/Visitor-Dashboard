<?php
require 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'key.php';
    $key = $_POST['key'] ?? '';
    $visit_time = $_POST['visit_time'] ?? date('Y-m-d H:i:s');

    if ($key === $apiKey) {
        if (isset($_POST['visit_time'])) {
            $visit_time = date('Y-m-d H:i:s', strtotime($visit_time));
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO visits (visit_time) VALUES (:visit_time)");
            $stmt->bindValue(':visit_time', $visit_time, PDO::PARAM_STR);
            $stmt->execute();

            echo json_encode(["status" => "success", "visit_time" => $visit_time]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid key']);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
