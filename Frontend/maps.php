<?php
require 'config.php';

// Pagination settings
$limit = 20; 
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Fetch aggregated map data
$stmt = $pdo->prepare("
    SELECT 
        map_name, 
        MIN(first_seen) AS first_seen,
        MAX(last_seen) AS last_seen,
        SUM(play_count) AS total_play_count
    FROM et_server_map_history
    WHERE map_name LIKE :search
    GROUP BY map_name
    ORDER BY total_play_count DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':search', $search, PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$maps = $stmt->fetchAll();

// Fetch total map count for pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT map_name) AS total
    FROM et_server_map_history
    WHERE map_name LIKE :search
");
$countStmt->execute(['search' => $search]);
$totalMaps = $countStmt->fetchColumn();
$totalPages = ceil($totalMaps / $limit);

// Fetch top 5 maps for chart
$topMapsStmt = $pdo->prepare("
    SELECT map_name, SUM(play_count) AS total_play_count
    FROM et_server_map_history
    GROUP BY map_name
    ORDER BY total_play_count DESC
    LIMIT 5
");
$topMapsStmt->execute();
$topMaps = $topMapsStmt->fetchAll();

$chartLabels = json_encode(array_column($topMaps, 'map_name'));
$chartData = json_encode(array_column($topMaps, 'total_play_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Maps - W:ET Server Browser</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
h1,h2,h3 { font-family: 'Bebas Neue', sans-serif; letter-spacing: 1px; }
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
    <!-- Search -->
    <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-8 mb-8 border-2 border-military-green shadow-xl">
        <h2 class="text-2xl font-bold mb-6 text-military-tan flex items-center gap-3">
            <i class="fas fa-search"></i> SEARCH MAPS
        </h2>
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" placeholder="Search map name..."
                value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                class="w-full px-6 py-4 bg-dark border-2 border-dark-border rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:border-primary text-lg">
            <button type="submit" class="px-8 py-4 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors shadow-md flex items-center gap-2">
                <i class="fas fa-search"></i> SEARCH
            </button>
        </form>
    </div>

    <!-- Top Maps Chart -->
    <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-6 mb-8 border-2 border-military-green shadow-xl">
        <h2 class="text-2xl font-bold text-military-tan mb-4 flex items-center gap-3">
            <i class="fas fa-chart-bar"></i> Top 5 Most Played Maps
        </h2>
        <canvas id="topMapsChart"></canvas>
    </div>

    <!-- Maps Table -->
    <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg border-2 border-military-green overflow-hidden shadow-xl">
        <div class="p-6 bg-gradient-to-r from-military-green to-secondary border-b-2 border-accent">
            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-map"></i> MAP HISTORY
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-dark border-b-2 border-military-green">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">
                            <i class="fas fa-map mr-2"></i>Map Name
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">
                            <i class="fas fa-clock mr-2"></i>First Seen
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">
                            <i class="fas fa-clock mr-2"></i>Last Seen
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-military-tan uppercase tracking-wide">
                            <i class="fas fa-play mr-2"></i>Play Count
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    <?php foreach ($maps as $map): ?>
                    <tr class="hover:bg-military-green hover:bg-opacity-20 transition-colors">
                        <td class="px-6 py-4 text-sm font-bold text-white"><?= htmlspecialchars($map['map_name']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-300"><?= htmlspecialchars($map['first_seen']) ?></td>
                        <td class="px-6 py-4 text-sm text-gray-300"><?= htmlspecialchars($map['last_seen']) ?></td>
                        <td class="px-6 py-4 text-sm text-military-tan font-bold"><?= htmlspecialchars($map['total_play_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center gap-3 p-6">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= isset($_GET['search']) ? urlencode($_GET['search']) : '' ?>"
                   class="px-4 py-2 rounded font-bold <?= $i == $page ? 'bg-accent text-white' : 'bg-dark border-2 border-dark-border text-gray-300 hover:bg-dark-border' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</main>

<script>
const ctx = document.getElementById('topMapsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Play Count',
            data: <?= $chartData ?>,
            backgroundColor: '#7a8450',
            borderColor: '#c44536',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        },
        scales: {
            x: { ticks: { color: '#ffffff' }, grid: { color: '#3a3a3a' } },
            y: { ticks: { color: '#ffffff' }, grid: { color: '#3a3a3a' } }
        }
    }
});
</script>

</body>
</html>
