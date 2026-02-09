<?php
require_once 'config.php';
if (isset($_GET['username'])) { $_SESSION['username'] = htmlspecialchars($_GET['username']); }
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit; }

$username = $_SESSION['username'];

// --- SERVER-SIDE SCANNING ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = $_POST['ajax_query'];
    
    $cached = get_from_vault($query);
    if ($cached) {
        echo json_encode(['type' => 'cache', 'data' => $cached['html']]);
        exit;
    }

    // Wikipedia Fetch
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $wiki_res = @file_get_contents($wiki_url);
    $wiki = json_decode($wiki_res, true);

    // Hacker News Fetch
    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $hn_res = @file_get_contents($hn_url);
    $hn = json_decode($hn_res, true);

    // NIST CVE Fetch
    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $cve_res = @file_get_contents($cve_url);
    $cve = json_decode($cve_res, true);

    // --- CONSTRUCT TERMINAL HTML ---
    $html = '<div class="wiki-entry">
             <h1 class="wiki-title-integrated">INTEGRATED_INTELLIGENCE: '.strtoupper($query).'</h1>';

    if (isset($wiki['extract'])) {
        $html .= '<div class="node-container node-wiki">
                    <p class="ZeroDay">[WIKI_NODE // DATA_EXTRACTED]</p>
                    <h2 style="color:#00ff41; font-size:1.1rem;">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wiki['extract'].'</p>
                  </div>';
    }

    if (isset($hn['hits']) && count($hn['hits']) > 0) {
        $html .= '<div class="node-container node-tech">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>';
        foreach (array_slice($hn['hits'], 0, 3) as $hit) {
            $html .= '<p class="wiki-content">▸ <a href="'.$hit['url'].'" target="_blank" class="hn-link">'.$hit['title'].'</a></p>';
        }
        $html .= '</div>';
    }

    if (isset($cve['vulnerabilities']) && count($cve['vulnerabilities']) > 0) {
        $html .= '<div class="node-container node-cve">
                    <p class="ZeroDay" style="color:#ff003c;">[THREAT_DETECTION // NIST_NVD]</p>';
        foreach (array_slice($cve['vulnerabilities'], 0, 2) as $item) {
            $id = $item['cve']['id'];
            $desc = $item['cve']['descriptions'][0]['value'];
            $html .= '<p class="wiki-content" style="font-size:0.85rem;"><strong style="color:#ff003c;">'.$id.':</strong> '.substr($desc, 0, 150).'...</p>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';

    save_to_vault($query, $html);
    echo json_encode(['type' => 'fresh', 'data' => $html, 'count' => get_vault_count()]);
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'purge') {
    purge_user_vault();
    echo json_encode(['status' => 'success']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DARK_WIKI // TERMINAL</title>
  <link rel="stylesheet" href="chatbox.css">
</head>
<body>

<button class="delete-btn" onclick="selfDestruct()">DELETE HISTORY</button>

<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: <?php echo get_vault_count(); ?> <br>NODES ARCHIVED</div>
      </div>
      
      <div class="header-center">
          <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
      </div>

      <div class="header-right">
          <button class="scrub-btn" onclick="selfDestruct()" style="color:#ff3131; border-color:#ff3131;">PURGE</button>
      </div>
  </div>

  <div class="status-bar">
    <div>STATUS: <span id="connection-light">[CONNECTED]</span></div> 
    <button class="scrub-btn" onclick="clearDisplay()">CLEAR TERMINAL</button>
  </div>

  <div id="savedMessages" class="messages">
      </div>

  <form class="input-area" id="searchForm">
    <textarea id="messageBox" rows="1" placeholder="Search database..." required></textarea>
    <input type="submit" value="SEARCH" class="send-btn">
  </form>
</div>

<script>
// Latency and Connection Logic from HTML version
function updateOnlineStatus() {
    const light = document.getElementById("connection-light");
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const rtt = connection ? connection.rtt : null;
    light.className = "";
    if (navigator.onLine) {
        if (rtt !== null) {
            const statusClass = rtt <= 100 ? "status-low" : "status-high";
            light.textContent = `[CONNECTED: ${rtt <= 100 ? 'LOW' : 'HIGH'}_LATENCY (${rtt}ms)]`;
            light.classList.add(statusClass);
        } else {
            light.textContent = "[CONNECTED]";
            light.classList.add("status-low");
        }
    } else {
        light.textContent = "[DISCONNECTED]";
        light.classList.add("status-offline", "blink");
    }
}
setInterval(updateOnlineStatus, 2000);

const SoundEngine = {
    ctx: new (window.AudioContext || window.webkitAudioContext)(),
    typeClick() {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'sine'; osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.015, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + 0.02);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.02);
    },
    blip(freq = 150) {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.05, this.ctx.currentTime);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.1);
    }
};

function typeEffect(element, html, speed = 10) {
    let i = 0; element.innerHTML = "";
    function type() {
        if (i < html.length) {
            if (html.charAt(i) === '<') i = html.indexOf('>', i) + 1;
            else { i++; if (html.charAt(i-1) !== " ") SoundEngine.typeClick(); }
            element.innerHTML = html.substring(0, i);
            setTimeout(type, speed);
        }
    }
    type();
}

// Intro sequence matching the Right terminal
function runIntro() {
    const display = document.getElementById("savedMessages");
    const logs = [
        { cls: "NullPointer", msg: `> Connection established. Welcome, <?php echo $username; ?>.` },
        { cls: "wiki-content", msg: `> Bypassing local firewalls...` },
        { cls: "ZeroDay", msg: `> GLOBAL UPLINK: STABLE` }
    ];
    logs.forEach((log, i) => {
        setTimeout(() => {
            const p = document.createElement("p");
            p.className = log.cls;
            p.innerHTML = log.msg;
            display.appendChild(p);
        }, i * 500);
    });
}

document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const display = document.getElementById('savedMessages');
    const query = box.value;

    display.innerHTML = `<div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
                         <div class="progress-container"><div class="progress-bar" id="pbar" style="width: 0%"></div></div>
                         <div id="matrix" class="matrix-loader"></div>`;
    
    // Fake progress animation
    let prog = 0;
    const iv = setInterval(() => {
        if(prog < 90) {
            prog += 5;
            document.getElementById('pbar').style.width = prog + "%";
            document.getElementById('matrix').innerText = Math.random().toString(16);
        }
    }, 100);

    const formData = new FormData();
    formData.append('ajax_query', query);

    const response = await fetch('darkchat.php', { method: 'POST', body: formData });
    const result = await response.json();
    
    clearInterval(iv);
    document.getElementById('pbar').style.width = "100%";
    SoundEngine.blip(200);
    typeEffect(display, result.data);
    
    if(result.count !== undefined) {
        document.getElementById('vault-stats').innerHTML = `VAULT_ENTRIES: ${result.count} <br>NODES ARCHIVED`;
    }
    box.value = "";
};

function clearDisplay() {
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal display purged. Vault remains intact.</p>`;
    SoundEngine.blip(300);
}

async function selfDestruct() {
    if(confirm("PURGE PRIVATE VAULT?")) {
        const formData = new FormData();
        formData.append('action', 'purge');
        await fetch('darkchat.php', { method: 'POST', body: formData });
        window.location.href = 'index.php';
    }
}

window.onload = runIntro;
</script>
</body>
</html>
