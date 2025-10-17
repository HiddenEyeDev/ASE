// et-poller.js
import dgram from 'dgram';
import mysql from 'mysql2/promise';
import geoip from 'geoip-lite';

// --- CONFIG ---
const QUERY_INTERVAL = 5 * 60 * 1000; // 5 minutes
const QUERY_TIMEOUT_MS = 5000;

// --- MySQL pool (keep your credentials or use env vars) ---
const db = await mysql.createPool({
  host: 'localhost',
  user: 'ASE',
  password: 'ASE1337',
  database: 'ASE',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// --- Helper: strip ET color codes ---
function stripColorCodes(name) {
  return name.replace(/\^./g, '');
}

// --- Query ET server ---
async function queryETServer(host, port = 27960) {
  return new Promise((resolve, reject) => {
    const client = dgram.createSocket('udp4');
    const message = Buffer.from('\xff\xff\xff\xffgetstatus\n', 'binary');

    const timeout = setTimeout(() => {
      client.close();
      reject(new Error('Timed out'));
    }, QUERY_TIMEOUT_MS);

    client.on('message', (msg) => {
      clearTimeout(timeout);
      client.close();

      const response = msg.toString('utf8');
      const lines = response.split('\n').map(l => l.trim()).filter(Boolean);
      if (lines.length < 2) return reject(new Error('Invalid response'));

      const infoPairs = lines[1].split('\\').filter(Boolean);
      const info = {};
      for (let i = 0; i < infoPairs.length; i += 2)
        info[infoPairs[i]] = infoPairs[i + 1];

      const players = lines.slice(2).map(line => {
        const match = line.match(/^(-?\d+) (\d+) "(.*)"$/);
        return match ? { score: +match[1], ping: +match[2], name: match[3] } : null;
      }).filter(Boolean);

      resolve({ info, players });
    });

    client.send(message, 0, message.length, port, host, (err) => {
      if (err) {
        clearTimeout(timeout);
        client.close();
        reject(err);
      }
    });
  });
}

// --- Upsert server info, players, maps, sessions, and events ---
async function upsertServerInfo(host, port, info, players) {
  const geo = geoip.lookup(host);
  const country = geo ? geo.country : null;
  const now = new Date();

  const wantedKeys = [
    'sv_sac','mod_version','g_balancedteams','g_bluelimbotime','g_redlimbotime',
    'gamename','g_needpass','sv_privateClients','mapname','protocol','g_gametype',
    'timelimit','sv_hostname','g_friendlyFire','g_antilag','omnibot_enable',
    'sv_maxclients','version'
  ];
  const data = {};
  for (const key of wantedKeys) data[key] = info[key] || null;

  console.log(`\n🔹 Upserting server: ${data.sv_hostname || host}:${port}, map=${data.mapname}, players=${players.length}`);

  // Upsert server status (host+port unique)
  await db.execute(
    `INSERT INTO et_server_status (
      host, port, hostname, sv_sac, mod_version, g_balancedteams, g_bluelimbotime,
      g_redlimbotime, gamename, g_needpass, sv_privateClients, mapname, protocol,
      g_gametype, timelimit, g_friendlyFire, g_antilag, omnibot_enable, sv_maxclients,
      version, player_count
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      hostname=VALUES(hostname),
      sv_sac=VALUES(sv_sac),
      mod_version=VALUES(mod_version),
      g_balancedteams=VALUES(g_balancedteams),
      g_bluelimbotime=VALUES(g_bluelimbotime),
      g_redlimbotime=VALUES(g_redlimbotime),
      gamename=VALUES(gamename),
      g_needpass=VALUES(g_needpass),
      sv_privateClients=VALUES(sv_privateClients),
      mapname=VALUES(mapname),
      protocol=VALUES(protocol),
      g_gametype=VALUES(g_gametype),
      timelimit=VALUES(timelimit),
      g_friendlyFire=VALUES(g_friendlyFire),
      g_antilag=VALUES(g_antilag),
      omnibot_enable=VALUES(omnibot_enable),
      sv_maxclients=VALUES(sv_maxclients),
      version=VALUES(version),
      player_count=VALUES(player_count)`,
    [
      host, port, data.sv_hostname, data.sv_sac, data.mod_version, data.g_balancedteams,
      data.g_bluelimbotime, data.g_redlimbotime, data.gamename, data.g_needpass,
      data.sv_privateClients, data.mapname, data.protocol, data.g_gametype,
      data.timelimit, data.g_friendlyFire, data.g_antilag, data.omnibot_enable,
      data.sv_maxclients, data.version, players.length
    ]
  );

  // Get server id
  const [serverRow] = await db.execute(`SELECT id FROM et_server_status WHERE host=? AND port=?`, [host, port]);
  if (!serverRow || serverRow.length === 0) {
    console.warn('Could not fetch server id after upsert for', host, port);
    return;
  }
  const serverId = serverRow[0].id;
  console.log(`   ↪ Server ID: ${serverId}`);

  // Fetch current players in DB before we replace them
  const [currentPlayersRows] = await db.execute(`SELECT name FROM et_server_players WHERE server_id=?`, [serverId]);
  const currentPlayerNames = currentPlayersRows.map(r => r.name);
  const newPlayerNames = players.map(p => p.name);

  // Disconnected players: those in currentPlayerNames but not in newPlayerNames
  const disconnected = currentPlayerNames.filter(n => !newPlayerNames.includes(n));
  for (const name of disconnected) {
    console.log(`   ⚠️ Player disconnected: ${name}`);

    // Update player history totals (existing behavior)
    const [rows] = await db.execute(
      `SELECT id, last_seen, total_time_seconds FROM et_player_history WHERE player_name=? AND server_id=?`,
      [name, serverId]
    );
    if (rows.length) {
      const prev = rows[0];
      const deltaSec = Math.floor((now - new Date(prev.last_seen)) / 1000);
      const totalTime = prev.total_time_seconds + deltaSec;
      await db.execute(
        `UPDATE et_player_history SET last_seen=?, total_time_seconds=? WHERE id=?`,
        [now, totalTime, prev.id]
      );
    }

    // Insert disconnect event
    try {
      const plain = stripColorCodes(name);
      await db.execute(
        `INSERT INTO et_player_events (server_id, player_name, plain_name, event_type, recorded_at)
         VALUES (?, ?, ?, 'disconnect', ?)`,
        [serverId, name, plain, now]
      );
    } catch (err) {
      console.error('Failed to insert disconnect event', err);
    }
  }

  // New connections: players in newPlayerNames but not in currentPlayerNames
  const connected = newPlayerNames.filter(n => !currentPlayerNames.includes(n));
  for (const pname of connected) {
    console.log(`   ➕ Player connected: ${pname}`);
    const plainName = stripColorCodes(pname);

    // Insert connect event
    try {
      await db.execute(
        `INSERT INTO et_player_events (server_id, player_name, plain_name, event_type, recorded_at)
         VALUES (?, ?, ?, 'connect', ?)`,
        [serverId, pname, plainName, now]
      );
    } catch (err) {
      console.error('Failed to insert connect event', err);
    }

    // Make sure history entry exists (or create)
    const [histRows] = await db.execute(
      `SELECT id, last_seen, total_time_seconds FROM et_player_history WHERE player_name=? AND server_id=?`,
      [pname, serverId]
    );
    if (!histRows.length) {
      await db.execute(
        `INSERT INTO et_player_history (player_name, plain_name, server_id, first_seen, last_seen, total_time_seconds)
         VALUES (?, ?, ?, ?, ?, 0)`,
        [pname, plainName, serverId, now, now]
      );
    } else {
      // update last_seen to now (they reconnected)
      const prev = histRows[0];
      await db.execute(
        `UPDATE et_player_history SET last_seen=? WHERE id=?`,
        [now, prev.id]
      );
    }
  }

  // Replace current players table (same behavior as before)
  await db.execute(`DELETE FROM et_server_players WHERE server_id=?`, [serverId]);
  for (const p of players) {
    const plainName = stripColorCodes(p.name);
    await db.execute(
      `INSERT INTO et_server_players (server_id, name, plain_name, score, ping, updated_at)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [serverId, p.name, plainName, p.score, p.ping, now]
    );

    // Score history handling (same behavior)
    if (p.ping === 0) continue;

    if (p.score > 0) {
      const [scoreRows] = await db.execute(
        `SELECT score FROM et_player_score_history WHERE player_name=? AND server_id=? ORDER BY recorded_at DESC LIMIT 1`,
        [p.name, serverId]
      );
      if (!scoreRows.length || scoreRows[0].score !== p.score) {
        await db.execute(
          `INSERT INTO et_player_score_history (player_name, plain_name, server_id, score, recorded_at)
           VALUES (?, ?, ?, ?, ?)`,
          [p.name, plainName, serverId, p.score, now]
        );
      }
    }

    // Update history last_seen / total time for players who remain (handled below)
    const [histRows2] = await db.execute(
      `SELECT id, last_seen, total_time_seconds FROM et_player_history WHERE player_name=? AND server_id=?`,
      [p.name, serverId]
    );
    if (histRows2.length) {
      const prev = histRows2[0];
      const deltaSec = Math.floor((now - new Date(prev.last_seen)) / 1000);
      const totalTime = prev.total_time_seconds + deltaSec;
      await db.execute(
        `UPDATE et_player_history SET last_seen=?, total_time_seconds=? WHERE id=?`,
        [now, totalTime, prev.id]
      );
    } else {
      // If not existed (rare because we inserted on connect above), create
      await db.execute(
        `INSERT INTO et_player_history (player_name, plain_name, server_id, first_seen, last_seen, total_time_seconds)
         VALUES (?, ?, ?, ?, ?, 0)`,
        [p.name, plainName, serverId, now, now]
      );
    }
  }

  // --- Map history & sessions (same as before) ---
  if (data.mapname) {
    const [activeSessionRows] = await db.execute(
      `SELECT id, map_name, start_time FROM et_server_map_sessions WHERE server_id=? AND end_time IS NULL`,
      [serverId]
    );

    if (!activeSessionRows.length) {
      console.log(`   ↪ Starting new map session for map ${data.mapname}`);
      await db.execute(
        `INSERT INTO et_server_map_sessions (server_id, map_name, start_time) VALUES (?, ?, ?)`,
        [serverId, data.mapname, now]
      );
    } else {
      const session = activeSessionRows[0];
      if (session.map_name !== data.mapname) {
        const startTime = new Date(session.start_time);
        const durationSec = Math.floor((now - startTime) / 1000);

        console.log(`   ↪ Ending previous session for map ${session.map_name} (duration ${durationSec}s)`);
        await db.execute(
          `UPDATE et_server_map_sessions SET end_time=?, duration_seconds=? WHERE id=?`,
          [now, durationSec, session.id]
        );

        console.log(`   ↪ Starting new map session for map ${data.mapname}`);
        await db.execute(
          `INSERT INTO et_server_map_sessions (server_id, map_name, start_time) VALUES (?, ?, ?)`,
          [serverId, data.mapname, now]
        );
      }
    }

    // Map history
    const [mapRows] = await db.execute(
      `SELECT id FROM et_server_map_history WHERE server_id=? AND map_name=?`,
      [serverId, data.mapname]
    );
    if (!mapRows.length) {
      console.log(`   ↪ Adding new map history: ${data.mapname}`);
      await db.execute(
        `INSERT INTO et_server_map_history (server_id, map_name, first_seen, last_seen, play_count)
         VALUES (?, ?, ?, ?, 1)`,
        [serverId, data.mapname, now, now]
      );
    } else if (!activeSessionRows.length || activeSessionRows[0].map_name !== data.mapname) {
      await db.execute(
        `UPDATE et_server_map_history SET last_seen=?, play_count=play_count+1 WHERE id=?`,
        [now, mapRows[0].id]
      );
    } else {
      await db.execute(
        `UPDATE et_server_map_history SET last_seen=? WHERE id=?`,
        [now, mapRows[0].id]
      );
    }
  }

  // --- Update server metadata ---
  await db.execute(
    `UPDATE et_server_list SET last_query_success=NOW(), country=? WHERE host=? AND port=?`,
    [country, host, port]
  );

  console.log(`   ✅ Completed server update: ${data.sv_hostname || host}:${port}`);
}

// --- Load servers ---
async function loadServerList() {
  const [rows] = await db.execute(`SELECT host, port FROM et_server_list WHERE active=1`);
  return rows;
}

// --- Main loop ---
async function updateAllServers() {
  console.log(`\n=== Querying all servers @ ${new Date().toLocaleTimeString()} ===`);
  const servers = await loadServerList();
  if (!servers.length) return console.log('⚠️  No active servers found.');

  await Promise.all(servers.map(async server => {
    console.log(`🔹 Querying server: ${server.host}:${server.port}`);
    try {
      const { info, players } = await queryETServer(server.host, server.port);
      await upsertServerInfo(server.host, server.port, info, players);
    } catch (err) {
      console.error(`❌ ${server.host}:${server.port} — ${err.message}`);
      try {
        await db.execute(`UPDATE et_server_list SET last_query_success=NULL WHERE host=? AND port=?`, [server.host, server.port]);
      } catch(e){ console.error('Failed to mark last_query_success NULL', e); }
    }
  }));

  console.log(`=== Completed all servers @ ${new Date().toLocaleTimeString()} ===`);
}

// Start
updateAllServers();
setInterval(updateAllServers, QUERY_INTERVAL);
