<?php
require 'config.php';

// Function to render ET color codes
function renderETColors($name) {
    $etColors = [
        '0'=>'#000000','1'=>'#ff0000','2'=>'#00ff00','3'=>'#ffff00','4'=>'#0000ff',
        '5'=>'#00ffff','6'=>'#ff00ff','7'=>'#ffffff','8'=>'#ff7f00','9'=>'#7f7f7f',
        ':'=>'#bfbfbf',';'=>'#bfbfbf','<'=>'#007f00','='=>'#7f7f00','>'=>'#00007f',
        '?'=>'#7f0000','@'=>'#7f3f00','!'=>'#ff9919','"'=>'#007f7f','#'=>'#7f007f',
        '$'=>'#007fff','%'=>'#7f00ff','&'=>'#3399cc','‘'=>'#ccffcc','('=>'#006633',
        ')'=>'#ff0033','*'=>'#b21919','+'=>'#993300',','=>'#cc9933','–'=>'#999933',
        '.'=>'#ffffbf','/'=>'#ffff7f',
        'A'=>'#ff9919','B'=>'#007f7f','C'=>'#7f007f','D'=>'#007fff','E'=>'#7f00ff',
        'F'=>'#3399cc','G'=>'#ccffcc','H'=>'#006633','I'=>'#ff0033','J'=>'#b21919',
        'K'=>'#993300','L'=>'#cc9933','M'=>'#999933','N'=>'#ffffbf','O'=>'#ffff7f',
        'P'=>'#000000','Q'=>'#ff0000','R'=>'#00ff00','S'=>'#ffff00','T'=>'#0000ff',
        'U'=>'#00ffff','V'=>'#ff00ff','W'=>'#ffffff','X'=>'#ff7f00','Y'=>'#7f7f7f',
        'Z'=>'#bfbfbf','a'=>'#ff9919','b'=>'#007f7f','c'=>'#7f007f','d'=>'#007fff',
        'e'=>'#7f00ff','f'=>'#3399cc','g'=>'#ccffcc','h'=>'#006633','i'=>'#ff0033',
        'j'=>'#b21919','k'=>'#993300','l'=>'#cc9933','m'=>'#999933','n'=>'#ffffbf',
        'o'=>'#ffff7f','p'=>'#000000','q'=>'#ff0000','r'=>'#00ff00','s'=>'#ffff00',
        't'=>'#0000ff','u'=>'#00ffff','v'=>'#ff00ff','w'=>'#ffffff','x'=>'#ff7f00',
        'y'=>'#7f7f7f','z'=>'#bfbfbf'
    ];

    $output = '';
    $currentColor = '#ffffff';
    $length = strlen($name);
    for ($i = 0; $i < $length; $i++) {
        if ($name[$i] === '^' && isset($name[$i + 1])) {
            $code = $name[$i + 1];
            $currentColor = $etColors[$code] ?? '#ffffff';
            $i++;
            continue;
        }
        $char = htmlspecialchars($name[$i]);
        $output .= "<span style=\"color:$currentColor\">$char</span>";
    }
    return $output;
}

// Pagination and search
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Total players
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM et_server_players WHERE ping > 0 AND (name LIKE :search OR plain_name LIKE :search)");
$totalStmt->execute(['search' => "%$search%"]);
$totalPlayers = $totalStmt->fetchColumn();
$totalPages = ceil($totalPlayers / $limit);

// Fetch players with pagination
$stmt = $pdo->prepare("
    SELECT p.*, s.hostname AS server_name
    FROM et_server_players p
    JOIN et_server_status s ON p.server_id = s.id
    WHERE p.ping > 0
    AND (p.name LIKE :search OR p.plain_name LIKE :search)
    ORDER BY p.score DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$players = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Players - W:ET Server Browser</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#7a8450',
                secondary: '#4a5234',
                accent: '#c44536',
                dark: '#1a1a1a',
                'dark-lighter': '#2a2a2a',
                'dark-border': '#3a3a3a',
                'military-green': '#4a5234',
                'military-tan': '#9b8b6f',
            }
        }
    }
}
</script>
<style>
body { font-family: 'Roboto Condensed', sans-serif; }
h1,h2,h3 { font-family: 'Bebas Neue', sans-serif; letter-spacing:1px; }
</style>
</head>
<body class="bg-dark text-gray-100 min-h-screen">

<header class="bg-gradient-to-r from-military-green to-secondary border-b-4 border-accent">
    <div class="container mx-auto px-6 py-5 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-accent rounded flex items-center justify-center font-bold text-white shadow-lg">
                <i class="fas fa-crosshairs text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-white">WOLFENSTEIN: ENEMY TERRITORY</h1>
                <p class="text-sm text-military-tan">Server Command Center</p>
            </div>
        </div>
        <nav class="flex gap-3">

            <a href="index.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2">
                <i class="fas fa-server"></i> SERVERS
            </a>
                        <a href="players.php" class="px-6 py-3 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-users"></i> PLAYERS
            </a>
            <a href="maps.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2">
                <i class="fas fa-map"></i> MAPS
            </a>
            <a href="add-server.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2">
                <i class="fas fa-plus-circle"></i> ADD SERVER
            </a>
        </nav>
    </div>
</header>

<main class="container mx-auto px-6 py-6">

<!-- Search Box -->
<div class="mb-6 bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-6 border-2 border-military-green shadow-xl">
    <form method="get" class="flex gap-4">
        <input type="text" name="search" placeholder="Search by player or plain name..." value="<?= htmlspecialchars($search) ?>" 
            class="flex-1 px-4 py-3 bg-dark border border-dark-border rounded text-gray-100 focus:outline-none">
        <button type="submit" class="px-6 py-3 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors">
            <i class="fas fa-search"></i> Search
        </button>
    </form>
</div>

<!-- Players Table -->
<div class="overflow-x-auto bg-dark rounded-lg shadow-xl">
    <table class="w-full">
        <thead class="bg-dark border-b-2 border-military-green">
            <tr>
                <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase">Player Name</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase">Server</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase">Score</th>
                <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase">Ping</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-dark-border">
            <?php foreach($players as $player): ?>
                <tr class="hover:bg-military-green hover:bg-opacity-20 transition-colors">
                    <td class="px-6 py-4 text-sm font-bold"><?= renderETColors($player['name']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300">
                        <a href="server-details.php?id=<?= urlencode($player['server_id']) ?>" class="hover:underline">
                            <?= renderETColors($player['server_name']) ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm font-bold text-military-tan"><?= $player['score'] ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300"><?= $player['ping'] ?>ms</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="mt-6 flex justify-center gap-2">
    <?php for($i=1; $i<=$totalPages; $i++): ?>
        <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" class="px-4 py-2 rounded border border-gray-700 <?= $i==$page?'bg-accent text-white':'text-gray-300' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

</main>
</body>
</html>
