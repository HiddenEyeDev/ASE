<?php
require_once 'config.php'; // includes $pdo connection

// ET color code rendering function
function renderETColors($name) {
    $etColors = [
        '0'=>'#000000','1'=>'#ff0000','2'=>'#00ff00','3'=>'#ffff00','4'=>'#0000ff',
        '5'=>'#00ffff','6'=>'#ff00ff','7'=>'#ffffff','8'=>'#ff7f00','9'=>'#7f7f7f',
        ':'=>'#bfbfbf',';'=>'#bfbfbf','<'=>'#007f00','='=>'#7f7f00','>'=>'#00007f',
        '?'=>'#7f0000','@'=>'#7f3f00','!'=>'#ff9919','"'=>'#007f7f','#'=>'#7f007f',
        '$'=>'#007fff','%'=>'#7f00ff','&'=>'#3399cc','‘'=>'#ccffcc','('=>'#006633',
        ')'=>'#ff0033','*'=>'#b21919','+'=>'#993300',','=>'#cc9933','–'=>'#999933',
        '.'=>'#ffffbf','/'=>'#ffff7f','A'=>'#ff9919','B'=>'#007f7f','C'=>'#7f007f',
        'D'=>'#007fff','E'=>'#7f00ff','F'=>'#3399cc','G'=>'#ccffcc','H'=>'#006633',
        'I'=>'#ff0033','J'=>'#b21919','K'=>'#993300','L'=>'#cc9933','M'=>'#999933',
        'N'=>'#ffffbf','O'=>'#ffff7f','P'=>'#000000','Q'=>'#ff0000','R'=>'#00ff00',
        'S'=>'#ffff00','T'=>'#0000ff','U'=>'#00ffff','V'=>'#ff00ff','W'=>'#ffffff',
        'X'=>'#ff7f00','Y'=>'#7f7f7f','Z'=>'#bfbfbf','a'=>'#ff9919','b'=>'#007f7f',
        'c'=>'#7f007f','d'=>'#007fff','e'=>'#7f00ff','f'=>'#3399cc','g'=>'#ccffcc',
        'h'=>'#006633','i'=>'#ff0033','j'=>'#b21919','k'=>'#993300','l'=>'#cc9933',
        'm'=>'#999933','n'=>'#ffffbf','o'=>'#ffff7f','p'=>'#000000','q'=>'#ff0000',
        'r'=>'#00ff00','s'=>'#ffff00','t'=>'#0000ff','u'=>'#00ffff','v'=>'#ff00ff',
        'w'=>'#ffffff','x'=>'#ff7f00','y'=>'#7f7f7f','z'=>'#bfbfbf'
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

// Fetch all servers
$servers = $pdo->query("SELECT * FROM et_server_status")->fetchAll(PDO::FETCH_ASSOC);

// Sort servers by player_count descending
usort($servers, fn($a,$b) => $b['player_count'] <=> $a['player_count']);

// Separate top 3 and other servers
$topServers = array_slice($servers, 0, 3);
$otherServers = array_slice($servers, 3);

// Stats
$totalPlayers = array_sum(array_column($servers,'player_count'));
$totalServers = count($servers);
$activeServers = count(array_filter($servers, fn($s)=>$s['player_count']>0));
$maxCapacity = array_sum(array_column($servers,'sv_maxclients'));

// Helper to render each server row
function renderServerRow($s, $rank = null){
    $statusColor = ($s['player_count'] === 0) ? 'text-gray-500' : (($s['player_count'] >= $s['sv_maxclients']) ? 'text-accent' : 'text-green-400');
    $statusText = ($s['player_count'] === 0) ? 'empty' : (($s['player_count'] >= $s['sv_maxclients']) ? 'full' : 'active');
    $serverIP = $s['host'].':'.$s['port'];

    // Top 3 crown icons
    $prefix = '';
    if ($rank !== null) {
        $icons = ['<i class="fas fa-crown text-yellow-400 mr-1"></i>','<i class="fas fa-crown text-gray-400 mr-1"></i>','<i class="fas fa-crown text-orange-400 mr-1"></i>'];
        $prefix = $icons[$rank-1] ?? '';
    }

    echo "<tr class='hover:bg-military-green hover:bg-opacity-20 transition-colors cursor-pointer'
        data-name='".strtolower($s['hostname'])."'
        data-map='".strtolower($s['mapname'])."'
        data-type='".strtolower($s['g_gametype'])."'
        data-status='$statusText'
        onclick=\"window.location.href='server-details.php?id={$s['id']}'\">
        <td class='px-6 py-4 text-sm font-bold'>{$prefix}".renderETColors($s['hostname'])."</td>
        <td class='px-6 py-4 text-sm text-gray-300'>{$s['mapname']}</td>
        <td class='px-6 py-4 text-sm font-bold' data-playercount='{$s['player_count']}'><span class='$statusColor'>{$s['player_count']} / {$s['sv_maxclients']}</span></td>
        <td class='px-6 py-4 text-sm text-gray-300'>{$s['g_gametype']}</td>
        <td class='px-6 py-4 text-sm'><a href='et://$serverIP' class='text-blue-400 font-bold hover:underline'>$serverIP</a></td>
        <td class='px-6 py-4 text-sm'><span class='px-3 py-1 rounded-full text-xs font-bold $statusColor bg-opacity-30 border border-current'><i class='fas fa-circle text-xs mr-1'></i>$statusText</span></td>
    </tr>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>W:ET Server Browser</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
<script>
tailwind.config = {
    theme: { extend: { colors: { primary: '#7a8450', secondary: '#4a5234', accent: '#c44536', dark: '#1a1a1a', 'dark-lighter': '#2a2a2a', 'dark-border': '#3a3a3a', 'military-green': '#4a5234', 'military-tan': '#9b8b6f' } } }
}
</script>
<style>
body { font-family: 'Roboto Condensed', sans-serif; }
h1,h2,h3 { font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; }
th.sortable { cursor: pointer; }
th.sortable:hover { color: #c44536; }
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
            <a href="index.php" class="px-6 py-3 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors shadow-md flex items-center gap-2"><i class="fas fa-server"></i> SERVERS</a>
            <a href="players.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-users"></i> PLAYERS</a>
            <a href="maps.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-map"></i> MAPS</a>
            <a href="add-server.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-plus-circle"></i> ADD SERVER</a>
        </nav>
    </div>
</header>

<main class="container mx-auto px-6 py-8">

<!-- STAT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-military-green to-secondary rounded-lg p-6 border-l-4 border-accent shadow-lg">
        <div class="flex items-center justify-between">
            <div><div class="text-sm text-military-tan font-bold uppercase mb-2">Total Servers</div><div class="text-4xl font-bold text-white"><?=$totalServers?></div></div>
            <i class="fas fa-server text-5xl text-accent opacity-20"></i>
        </div>
    </div>
    <div class="bg-gradient-to-br from-military-green to-secondary rounded-lg p-6 border-l-4 border-accent shadow-lg">
        <div class="flex items-center justify-between">
            <div><div class="text-sm text-military-tan font-bold uppercase mb-2">Players Online</div><div class="text-4xl font-bold text-white"><?=$totalPlayers?></div></div>
            <i class="fas fa-users text-5xl text-accent opacity-20"></i>
        </div>
    </div>
    <div class="bg-gradient-to-br from-military-green to-secondary rounded-lg p-6 border-l-4 border-accent shadow-lg">
        <div class="flex items-center justify-between">
            <div><div class="text-sm text-military-tan font-bold uppercase mb-2">Active Servers</div><div class="text-4xl font-bold text-white"><?=$activeServers?></div></div>
            <i class="fas fa-circle-check text-5xl text-accent opacity-20"></i>
        </div>
    </div>
    <div class="bg-gradient-to-br from-military-green to-secondary rounded-lg p-6 border-l-4 border-accent shadow-lg">
        <div class="flex items-center justify-between">
            <div><div class="text-sm text-military-tan font-bold uppercase mb-2">Max Capacity</div><div class="text-4xl font-bold text-white"><?=$maxCapacity?></div></div>
            <i class="fas fa-chart-line text-5xl text-accent opacity-20"></i>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-8 mb-8 border-2 border-military-green shadow-xl">
    <h2 class="text-2xl font-bold mb-6 text-military-tan flex items-center gap-3">
        <i class="fas fa-filter"></i> FILTER SERVERS
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <input type="text" id="searchName" placeholder="Search by name..." class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:border-primary">
        <select id="filterMap" class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 focus:outline-none focus:border-primary">
            <option value="">All Maps</option>
            <?php foreach(array_unique(array_column($servers,'mapname')) as $map): ?>
                <option value="<?=$map?>"><?=$map?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterType" class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 focus:outline-none focus:border-primary">
            <option value="">All Types</option>
            <?php foreach(array_unique(array_column($servers,'g_gametype')) as $type): ?>
                <option value="<?=$type?>"><?=$type?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterStatus" class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 focus:outline-none focus:border-primary">
            <option value="">All Servers</option>
            <option value="active">Has Players</option>
            <option value="empty">Empty</option>
            <option value="full">Full</option>
        </select>
    </div>
</div>

<!-- SERVER TABLE -->
<div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg border-2 border-military-green overflow-hidden shadow-xl">
    <div class="overflow-x-auto">
        <table class="w-full" id="serverTable">
            <thead class="bg-dark border-b-2 border-military-green">
                <tr>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">Server Name</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">Map</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide sortable" onclick="sortTable(2)">Players <i class="fas fa-sort ml-1"></i></th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">Game Type</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">Server IP</th>
                    <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($topServers as $i => $s) renderServerRow($s, $i+1);
                foreach($otherServers as $s) renderServerRow($s);
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// FILTERS
const rows = document.querySelectorAll('#serverTable tbody tr');
const search = document.getElementById('searchName');
const map = document.getElementById('filterMap');
const type = document.getElementById('filterType');
const status = document.getElementById('filterStatus');

function filterServers() {
    const n = search.value.toLowerCase();
    const m = map.value.toLowerCase();
    const t = type.value.toLowerCase();
    const s = status.value.toLowerCase();

    rows.forEach(r => {
        const match =
            (!n || r.dataset.name.includes(n)) &&
            (!m || r.dataset.map === m) &&
            (!t || r.dataset.type === t) &&
            (!s || r.dataset.status === s);
        r.style.display = match ? '' : 'none';
    });
}
[search,map,type,status].forEach(el => el.addEventListener('input', filterServers));
map.addEventListener('change', filterServers);
type.addEventListener('change', filterServers);
status.addEventListener('change', filterServers);

// SORTING
let asc = true;
function sortTable(col) {
    const tbody = document.querySelector("#serverTable tbody");
    const rows = Array.from(tbody.rows);
    rows.sort((a,b)=>{
        const av = parseInt(a.cells[col].dataset.playercount||0);
        const bv = parseInt(b.cells[col].dataset.playercount||0);
        return asc ? av - bv : bv - av;
    });
    asc = !asc;
    rows.forEach(r=>tbody.appendChild(r));
}
</script>
</main>
</body>
</html>
