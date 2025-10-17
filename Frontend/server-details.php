<?php
// server-details.php
// Full server details page using your template and config.php (PDO $pdo)

require_once 'config.php'; // must define $pdo (PDO)

$server_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($server_id <= 0) {
    die('Invalid server ID.');
}

// Fetch server info from et_server_status
$stmt = $pdo->prepare("SELECT * FROM et_server_status WHERE id = ?");
$stmt->execute([$server_id]);
$server = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$server) {
    die('Server not found.');
}

// Fetch players from et_server_players (name, score, ping)
$stmt = $pdo->prepare("SELECT name, score, ping FROM et_server_players WHERE server_id = ? ORDER BY score DESC, name ASC");
$stmt->execute([$server_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Try to fetch map rotation from et_server_map_history (if table exists)
$mapRotation = [];
try {
    $stmt = $pdo->prepare("SELECT mapname, recorded_at FROM et_server_map_history WHERE server_id = ? ORDER BY recorded_at DESC LIMIT 12");
    $stmt->execute([$server_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        // newest first -> convert into rotation order (current first)
        foreach ($rows as $r) $mapRotation[] = $r['mapname'];
        $mapRotation = array_values(array_unique($mapRotation));
    }
} catch (\PDOException $e) {
    // table doesn't exist or other error — fallback to current map only
    $mapRotation = [];
}
if (empty($mapRotation)) {
    $mapRotation = [$server['mapname']];
}

// ET color rendering function (full color map you provided)
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
    $length = mb_strlen($name, 'UTF-8');
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($name, $i, 1, 'UTF-8');
        if ($char === '^') {
            // lookahead char
            $next = ($i + 1 < $length) ? mb_substr($name, $i + 1, 1, 'UTF-8') : null;
            if ($next !== null) {
                $code = $next;
                $currentColor = $etColors[$code] ?? $currentColor;
                $i++; // skip next char (the code)
                continue;
            }
        }
        $s = htmlspecialchars($char, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $output .= "<span style=\"color:{$currentColor}\">{$s}</span>";
    }
    return $output;
}

// Display mapping for server settings
function displaySettingItem($label, $value, $isBool = false) {
    $display = $isBool ? ($value ? 'ENABLED' : 'DISABLED') : htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $colorClass = $isBool ? ($value ? 'text-green-400' : 'text-gray-400') : 'text-gray-300';
    return <<<HTML
    <div class="flex justify-between items-center p-3 bg-dark rounded">
        <span class="text-sm text-gray-400 flex items-center gap-2"><i class="fas fa-cog text-military-tan"></i>{$label}</span>
        <span class="text-sm font-bold {$colorClass}">{$display}</span>
    </div>
    HTML;
}

// Count bots (ping = 0)
$botCount = 0;
foreach ($players as $p) {
    if ((int)$p['ping'] === 0) $botCount++;
}
$humanPlayersCount = count($players) - $botCount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Server Details - <?= htmlspecialchars($server['hostname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>

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
            <a href="index.php" class="px-6 py-3 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-server"></i> SERVERS
            </a>
            <a href="players.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2">
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

    <main class="container mx-auto px-6 py-8">
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center gap-2 text-military-tan hover:text-primary transition-colors font-bold">
                <i class="fas fa-arrow-left"></i> BACK TO SERVER LIST
            </a>
        </div>

        <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-8 mb-8 border-2 border-military-green shadow-xl">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-4xl font-bold text-white mb-3"><?= renderETColors($server['hostname']); ?></h2>
                    <div class="flex items-center gap-6 text-sm text-gray-400">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-network-wired text-military-tan"></i>
                            <a href="et://<?= htmlspecialchars($server['host'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:<?= (int)$server['port'] ?>" class="text-blue-400 font-bold hover:underline"><?= htmlspecialchars($server['host'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>:<?= (int)$server['port'] ?></a>
                        </span>
                        <span class="flex items-center gap-2">
                            <i class="fas fa-globe text-military-tan"></i>
                            <?= htmlspecialchars($server['country'] ?? 'Unknown', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                        <span class="flex items-center gap-2">
                            <i class="fas fa-users text-military-tan"></i>
                            <?= (int)$server['player_count'] ?> / <?= (int)$server['sv_maxclients'] ?> <?= $botCount ? "<span class='text-red-400 font-bold'>+{$botCount}</span>" : "" ?>
                        </span>
                    </div>
                </div>

                <div class="text-right bg-gradient-to-br from-military-green to-secondary p-6 rounded-lg border-2 border-accent shadow-lg">
                    <div class="text-5xl font-bold text-white mb-2"><?= (int)$server['player_count'] ?>/<?= (int)$server['sv_maxclients'] ?></div>
                    <div class="text-sm text-military-tan font-bold uppercase">Players Online</div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8">
                <div class="bg-dark rounded-lg p-5 border-l-4 border-accent">
                    <div class="text-sm text-military-tan font-bold uppercase mb-2 flex items-center gap-2"><i class="fas fa-map-marked-alt"></i>Current Map</div>
                    <div class="font-bold text-white text-lg"><?= htmlspecialchars($server['mapname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                </div>

                <div class="bg-dark rounded-lg p-5 border-l-4 border-accent">
                    <div class="text-sm text-military-tan font-bold uppercase mb-2 flex items-center gap-2"><i class="fas fa-trophy"></i>Game Type</div>
                    <div class="font-bold text-white text-lg"><?= htmlspecialchars($server['g_gametype'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                </div>

                <div class="bg-dark rounded-lg p-5 border-l-4 border-accent">
                    <div class="text-sm text-military-tan font-bold uppercase mb-2 flex items-center gap-2"><i class="fas fa-code-branch"></i>Protocol</div>
                    <div class="font-bold text-white text-lg"><?= htmlspecialchars($server['protocol'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                </div>

                <div class="bg-dark rounded-lg p-5 border-l-4 border-accent">
                    <div class="text-sm text-military-tan font-bold uppercase mb-2 flex items-center gap-2"><i class="fas fa-puzzle-piece"></i>Mod</div>
                    <div class="font-bold text-white text-lg"><?= htmlspecialchars(($server['gamename'] ?? '') . ' ' . ($server['mod_version'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                </div>
            </div>

            <div class="mt-6 p-5 bg-dark rounded-lg border-l-4 border-military-green">
                <div class="text-xs text-military-tan font-bold uppercase mb-2 flex items-center gap-2"><i class="fas fa-info-circle"></i>Server Version</div>
                <div class="text-sm text-gray-300"><?= htmlspecialchars($server['version'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Players -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg border-2 border-military-green overflow-hidden shadow-xl">
                    <div class="p-6 bg-gradient-to-r from-military-green to-secondary border-b-2 border-accent flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white flex items-center gap-3"><i class="fas fa-user-shield"></i>ACTIVE PLAYERS</h3>
                        <span class="text-sm text-military-tan font-bold bg-dark px-4 py-2 rounded-full"><?= count($players) ?> PLAYERS</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-dark border-b-2 border-military-green">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide"><i class="fas fa-user mr-2"></i>Player</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide"><i class="fas fa-medal mr-2"></i>Score</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide"><i class="fas fa-wifi mr-2"></i>Ping</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-dark-border">
                                <?php if (count($players) === 0): ?>
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No players online</td></tr>
                                <?php else: ?>
                                    <?php foreach ($players as $p): ?>
                                        <tr class="hover:bg-military-green hover:bg-opacity-20 transition-colors">
                                            <td class="px-6 py-4 text-sm font-bold"><?= renderETColors($p['name']) ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-300"><?= (int)$p['score'] ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-300"><?= (int)$p['ping'] ?>ms</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Settings & Map Rotation -->
            <div class="space-y-8">
                <!-- Server Settings -->
                <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg border-2 border-military-green overflow-hidden shadow-xl">
                    <div class="p-5 bg-gradient-to-r from-military-green to-secondary border-b-2 border-accent">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-cog"></i>SERVER SETTINGS</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <?= displaySettingItem('SAC Enabled', (int)($server['sv_sac'] ?? 0), true) ?>
                        <?= displaySettingItem('Balanced Teams', (int)($server['g_balancedteams'] ?? 0), true) ?>
                        <?= displaySettingItem('Allied Respawn (bluelimbotime)', ($server['g_bluelimbotime'] ?? 'N/A')) ?>
                        <?= displaySettingItem('Axis Respawn (redlimbotime)', ($server['g_redlimbotime'] ?? 'N/A')) ?>
                        <?= displaySettingItem('Password Required', (int)($server['g_needpass'] ?? 0), true) ?>
                        <?= displaySettingItem('Private Slots', ($server['sv_privateClients'] ?? '0')) ?>
                        <?= displaySettingItem('Time Limit', ($server['timelimit'] ?? '0')) ?>
                        <?= displaySettingItem('Friendly Fire', (int)($server['g_friendlyFire'] ?? 0), true) ?>
                        <?= displaySettingItem('Anti-Lag', (int)($server['g_antilag'] ?? 0), true) ?>
                        <?= displaySettingItem('Omni-Bots', (int)($server['omnibot_enable'] ?? 0), true) ?>
                    </div>
                </div>

                <!-- Map Rotation -->
                <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg border-2 border-military-green overflow-hidden shadow-xl">
                    <div class="p-5 bg-gradient-to-r from-military-green to-secondary border-b-2 border-accent">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2"><i class="fas fa-rotate"></i>MAP ROTATION</h3>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <?php foreach ($mapRotation as $i => $m): ?>
                                <?php $isCurrent = ($i === 0); ?>
                                <li class="flex items-center gap-3 text-sm p-3 <?= $isCurrent ? 'bg-accent bg-opacity-20 rounded border-l-4 border-accent' : 'bg-dark rounded' ?>">
                                    <span class="w-8 h-8 rounded-full <?= $isCurrent ? 'bg-accent text-white' : 'bg-dark-border text-gray-400' ?> flex items-center justify-center text-xs font-bold shadow-md"><?= $i+1 ?></span>
                                    <span class="<?= $isCurrent ? 'text-white font-bold' : 'text-gray-300' ?>"><?= htmlspecialchars($m, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
