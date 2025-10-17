<?php
require 'config.php';

$search = isset($_GET['q']) ? strtolower(trim($_GET['q'])) : '';

// Fetch matching players excluding bots
$stmt = $pdo->prepare("
    SELECT p.*, s.hostname AS server_name
    FROM et_server_players p
    JOIN et_server_status s ON p.server_id = s.id
    WHERE p.ping > 0
      AND (
          LOWER(p.name) LIKE :search
          OR LOWER(p.plain_name) LIKE :search
          OR LOWER(s.hostname) LIKE :search
      )
    ORDER BY p.score DESC
");
$stmt->execute([':search' => "%$search%"]);
$players = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($players);
