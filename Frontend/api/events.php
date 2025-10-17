<?php
// api/events.php
require_once __DIR__ . '/../config.php'; // adjust path if needed

// ---- ET color rendering helper ----
function renderETColors($name) {
    if (!$name) return '';
    $colors = [
        '0' => '#000000',
        '1' => '#ff0000',
        '2' => '#00ff00',
        '3' => '#ffff00',
        '4' => '#0000ff',
        '5' => '#00ffff',
        '6' => '#ff00ff',
        '7' => '#ffffff',
        '8' => '#ff7f00',
        '9' => '#7f7f7f'
    ];

    $out = '';
    $len = strlen($name);
    $i = 0;
    $currentColor = '#ffffff';
    while ($i < $len) {
        if ($name[$i] === '^' && isset($name[$i + 1]) && isset($colors[$name[$i + 1]])) {
            $currentColor = $colors[$name[$i + 1]];
            $i += 2;
            continue;
        }
        $ch = htmlspecialchars($name[$i], ENT_QUOTES, 'UTF-8');
        $out .= "<span style=\"color:{$currentColor}\">{$ch}</span>";
        $i++;
    }
    return $out;
}

// ---- Query params ----
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 100;
$server_id = isset($_GET['server_id']) ? (int)$_GET['server_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$since = isset($_GET['since']) ? $_GET['since'] : null;

// ---- Build query ----
$sql = "SELECT e.id, e.server_id, e.player_name, e.plain_name, e.event_type, e.recorded_at, s.hostname 
        FROM et_player_events e
        LEFT JOIN et_server_status s ON e.server_id = s.id";
$where = [];
$params = [];

if ($server_id) {
    $where[] = "e.server_id = :server_id";
    $params[':server_id'] = $server_id;
}
if ($type && in_array($type, ['connect', 'disconnect'])) {
    $where[] = "e.event_type = :etype";
    $params[':etype'] = $type;
}
if ($since) {
    $where[] = "e.recorded_at > :since";
    $params[':since'] = $since;
}

if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY e.recorded_at ASC LIMIT :limit";

// ---- Execute ----
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Add colorized name field ----
foreach ($rows as &$row) {
    $row['player_name_html'] = renderETColors($row['player_name']);
}
unset($row);

// ---- Output ----
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
