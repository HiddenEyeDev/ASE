<?php
require 'config.php';

// We might want a servers list for filter dropdown (use et_server_status)
$servers = $pdo->query("SELECT id, hostname FROM et_server_status ORDER BY hostname")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recent Events - W:ET Server Browser</title>
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
h1,h2,h3 { font-family: 'Bebas Neue', sans-serif; letter-spacing:1px; }
.event-connect { background: rgba(34,197,94,0.06); } /* green-ish */
.event-disconnect { background: rgba(239,68,68,0.06); } /* red-ish */
.event-time { color: #9b8b6f; font-size: 0.85rem; }
.small-muted { color: #bdbdbd; font-size: 0.9rem; }
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
        <p class="text-sm text-military-tan">Recent Player Connect / Disconnect Events</p>
      </div>
    </div>
    <nav class="flex gap-3">
      <a href="index.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-server"></i> SERVERS</a>
      <a href="players.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-users"></i> PLAYERS</a>
      <a href="maps.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-map"></i> MAPS</a>
      <a href="recent-events.php" class="px-6 py-3 bg-accent text-white rounded font-bold hover:bg-red-700 transition-colors flex items-center gap-2"><i class="fas fa-bolt"></i> EVENTS</a>
      <a href="add-server.php" class="px-6 py-3 bg-dark-lighter text-gray-300 rounded font-bold hover:bg-dark-border transition-colors flex items-center gap-2"><i class="fas fa-plus-circle"></i> ADD SERVER</a>
    </nav>
  </div>
</header>

<main class="container mx-auto px-6 py-8">
  <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-6 border-2 border-military-green shadow-xl">
      <h2 class="text-xl font-bold text-military-tan mb-2"><i class="fas fa-filter"></i> Filters</h2>
      <div class="space-y-3">
        <select id="serverFilter" class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100">
          <option value="">All servers</option>
          <?php foreach($servers as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['hostname']) ?></option>
          <?php endforeach; ?>
        </select>

        <select id="typeFilter" class="w-full px-4 py-3 bg-dark border-2 border-dark-border rounded text-gray-100">
          <option value="">All events</option>
          <option value="connect">Connect</option>
          <option value="disconnect">Disconnect</option>
        </select>

        <div class="flex gap-2">
          <button id="clearBtn" class="flex-1 px-4 py-3 bg-dark-lighter text-gray-200 rounded font-bold hover:bg-dark-border">Clear</button>
          <button id="pauseBtn" class="px-4 py-3 bg-accent text-white rounded font-bold hover:bg-red-700">Pause</button>
        </div>

        <div class="small-muted mt-2">Auto-refresh: <span id="intervalLabel">3s</span></div>
      </div>
    </div>

    <div class="col-span-2 bg-gradient-to-br from-dark-lighter to-dark rounded-lg p-6 border-2 border-military-green shadow-xl">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-white flex items-center gap-2"><i class="fas fa-bolt"></i> Event Feed</h2>
        <div class="text-sm text-military-tan">Live activity — newest first</div>
      </div>

      <div id="feed" class="space-y-3" style="max-height:60vh; overflow:auto; padding-right:6px;">
        <!-- Event items will be injected here -->
        <div class="text-gray-500">Loading events...</div>
      </div>
    </div>
  </div>
</main>

<script>
const feedEl = document.getElementById('feed');
const serverFilter = document.getElementById('serverFilter');
const typeFilter = document.getElementById('typeFilter');
const clearBtn = document.getElementById('clearBtn');
const pauseBtn = document.getElementById('pauseBtn');

let polling = true;
let lastFetched = null; // ISO timestamp of last fetched event
const POLL_INTERVAL_MS = 3000; // 3s

function formatTime(ts) {
  const d = new Date(ts);
  return d.toLocaleString();
}

function renderEventItem(ev) {
  const isConnect = ev.event_type === 'connect';
  const wrapper = document.createElement('div');
  wrapper.className = `p-3 rounded flex items-start gap-4 ${isConnect ? 'event-connect' : 'event-disconnect'}`;

  const icon = document.createElement('div');
  icon.className = 'w-10 h-10 rounded-full flex items-center justify-center';
  icon.innerHTML = isConnect ? '<i class="fas fa-plus text-green-400"></i>' : '<i class="fas fa-minus text-red-400"></i>';

  const body = document.createElement('div');
  body.innerHTML = `
    <div class="flex items-center gap-3">
      <div class="font-bold">${ev.player_name_html}</div>
      <div class="text-xs small-muted">on</div>
      <div class="text-sm text-military-tan font-bold">${escapeHtml(ev.hostname || 'Unknown')}</div>
      <div class="ml-auto event-time">${formatTime(ev.recorded_at)}</div>
    </div>
    <div class="text-sm text-gray-300 mt-1">${isConnect ? 'Connected' : 'Disconnected'} (${escapeHtml(ev.plain_name)})</div>
  `;
  wrapper.appendChild(icon);
  wrapper.appendChild(body);
  return wrapper;
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function fetchEvents() {
  let url = 'api/events.php?limit=200';
  if (serverFilter.value) url += '&server_id=' + encodeURIComponent(serverFilter.value);
  if (typeFilter.value) url += '&type=' + encodeURIComponent(typeFilter.value);
  if (lastFetched) url += '&since=' + encodeURIComponent(lastFetched);

  try {
    const resp = await fetch(url);
    if (!resp.ok) throw new Error('Fetch failed');
    const data = await resp.json();

    // data is newest first because API orders DESC; we will insert items at top
    if (Array.isArray(data) && data.length) {
      // update lastFetched to most recent recorded_at (first element)
      lastFetched = data[0].recorded_at;

      // prepend items (newest first)
      for (const ev of data) {
        // If filtering server/type via JS? not needed because API filters, but keep defensive:
        if (serverFilter.value && String(ev.server_id) !== serverFilter.value) continue;
        if (typeFilter.value && ev.event_type !== typeFilter.value) continue;

        const node = renderEventItem(ev);
        feedEl.insertBefore(node, feedEl.firstChild);
      }

      // trim feed to reasonable size
      while (feedEl.children.length > 500) feedEl.removeChild(feedEl.lastChild);
    }
  } catch (err) {
    console.error('Error fetching events', err);
  }
}

let pollHandle = null;
function startPolling() {
  if (pollHandle) clearInterval(pollHandle);
  pollHandle = setInterval(()=>{ if (polling) fetchEvents(); }, POLL_INTERVAL_MS);
  document.getElementById('intervalLabel').innerText = (POLL_INTERVAL_MS/1000) + 's';
}

function stopPolling() {
  if (pollHandle) clearInterval(pollHandle);
  pollHandle = null;
}

serverFilter.addEventListener('change', () => {
  // clear feed and reset lastFetched so we fetch fresh results for the selected server
  feedEl.innerHTML = '<div class="text-gray-500">Loading events...</div>';
  lastFetched = null;
  fetchEvents();
});

typeFilter.addEventListener('change', () => {
  feedEl.innerHTML = '<div class="text-gray-500">Loading events...</div>';
  lastFetched = null;
  fetchEvents();
});

clearBtn.addEventListener('click', () => {
  feedEl.innerHTML = '<div class="text-gray-500">Cleared.</div>';
  lastFetched = null;
});

pauseBtn.addEventListener('click', () => {
  polling = !polling;
  pauseBtn.innerText = polling ? 'Pause' : 'Resume';
  pauseBtn.classList.toggle('bg-accent', polling);
  pauseBtn.classList.toggle('bg-gray-600', !polling);
});

// initial load
fetchEvents();
startPolling();
</script>
</body>
</html>
