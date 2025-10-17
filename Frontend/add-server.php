<?php
require 'config.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = trim($_POST['ip']);
    $port = trim($_POST['port']);

    if (filter_var($ip, FILTER_VALIDATE_IP) && is_numeric($port)) {
        // Check if server already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM et_server_list WHERE host = :host AND port = :port");
        $stmt->execute([':host' => $ip, ':port' => $port]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $error = "This server is already in the list.";
        } else {
            // Insert into et_server_list
            $stmt = $pdo->prepare("INSERT INTO et_server_list (host, port) VALUES (:host, :port)");
            $stmt->execute([
                ':host' => $ip,
                ':port' => $port
            ]);
            $success = "Server added successfully!";
        }
    } else {
        $error = "Invalid IP address or port.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Server - W:ET Server Browser</title>
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
        <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-8 border-2 border-military-green shadow-xl max-w-lg mx-auto">
            <h2 class="text-2xl font-bold text-military-tan mb-6 flex items-center gap-3">
                <i class="fas fa-plus-circle"></i> ADD SERVER
            </h2>

            <?php if ($success): ?>
                <div class="bg-green-800 text-green-200 p-4 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php elseif ($error): ?>
                <div class="bg-red-800 text-red-200 p-4 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-military-tan font-bold mb-2" for="ip">Server IP</label>
                    <input type="text" id="ip" name="ip" placeholder="Enter server IP" required
                           class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-military-tan font-bold mb-2" for="port">Server Port</label>
                    <input type="number" id="port" name="port" placeholder="Enter server port" required
                           class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100 placeholder-gray-500 focus:outline-none focus:border-primary">
                </div>
                <button type="submit" class="w-full bg-accent text-white font-bold py-3 rounded hover:bg-red-700 transition-colors">
                    <i class="fas fa-plus-circle"></i> ADD SERVER
                </button>
            </form>
        </div>
    </main>
</body>
</html>
