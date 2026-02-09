<?php
require_once 'config.php';

// Handle Username Session
if (isset($_GET['username'])) { 
    $_SESSION['username'] = htmlspecialchars($_GET['username']); 
}
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); 
    exit; 
}

$username = $_SESSION['username'];

// Handle AJAX Search (The PHP now only acts as a data provider for the JS Fetch)
if (isset($_POST['ajax_query'])) {
    header('Content-Type: application/json');
    $query = $_POST['ajax_query'];
    
    // Check Cache (Server-side)
    $cached = get_from_vault($query);
    if ($cached) {
        echo json_encode(['type' => 'cache', 'data' => $cached['html']]);
        exit;
    }

    // 1. Wikipedia Extraction
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $wiki = json_decode(@file_get_contents($wiki_url), true);
    $wikiText = $wiki['extract'] ?? '';

    // 2. Tech Logs (HN)
    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $hn = json_decode(@file_get_contents($hn_url), true);

    // 3. Threat Detection (CVE)
    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $cve = json_decode(@file_get_contents($cve_url), true);

    // 4. DuckDuckGo
    $ddg_url = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json";
    $ddg = json_decode(@file_get_contents($ddg_url), true);

    echo json_encode([
        'wiki' => $wiki,
        'hn' => $hn,
        'cve' => $cve,
        'ddg' => $ddg,
        'query' => $query
    ]);
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
  <title>DARK_WIKI // TERMINAL</title>
  <link rel="stylesheet" href="chatbox.css">
</head>
<body>

<button class="delete-btn" onclick="purgeVault()">DELETE HISTORY</button>

<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: <?php echo get_vault_count(); ?> NODES ARCHIVED</div>
      </div>
      
      <div class="header-center">
          <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
      </div>
      <div class="header-right"></div>
  </div>

  <div class="status-bar">
    <div>STATUS: <span id="connection-light">[CONNECTED]</span></div> 
    <button class="scrub-btn" onclick="clearDisplay()">CLEAR TERMINAL</button>
  </div>

  <div id="savedMessages" class="messages">
      <p class="NullPointer">> Handshake complete. Private uplink stable.</p>
  </div>

  <form class="input-area" id="searchForm">
    <textarea id="messageBox" rows="1" placeholder="Search database..." required></textarea>
    <input type="submit" value="SEARCH" class="send-btn">
  </form>
</div>

<script>
const SoundEngine = {
    ctx: null,
    init() { if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)(); },
    blip(freq = 150, duration = 0.05) {
        this.init();
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.015, this.ctx.currentTime);
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + duration);
    },
    typeClick() {
        this.init();
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.01, this.ctx.currentTime);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.02);
    }
};

document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const query = box.value.trim();
    const display = document.getElementById('savedMessages');
    if(!query) return;

    // Advanced Scanning UI
    display.innerHTML = `
        <div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
        <div class="progress-container"><div id="pbar" class="progress-bar"></div></div>
        <div id="matrix" class="matrix-loader"></div>
    `;

    const pbar = document.getElementById('pbar');
    const matrix = document.getElementById('matrix');
    
    let progress = 0;
    const interval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 5; 
            pbar.style.width = progress + "%";
            matrix.innerText = Math.random().toString(16).substring(2, 60);
            if (Math.random() > 0.8) SoundEngine.blip(80, 0.05);
        }
    }, 100);

    const formData = new FormData();
    formData.append('ajax_query', query);

    try {
        const response = await fetch('darkchat.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        clearInterval(interval);
        pbar.style.width = "100%";
        SoundEngine.blip(200, 0.2);

        if (result.type === 'cache') {
            typeWriter(display, result.data);
        } else {
            const compiledHTML = buildNodeHTML(result);
            typeWriter(display, compiledHTML);
            // Optionally update server count here if needed
        }
    } catch (err) {
        display.innerHTML = `<p class="ZeroDay">> UPLINK ERROR: CONNECTION REFUSED</p>`;
    }
    box.value = "";
}
    
    function drilldownSearch(term) {
    const box = document.getElementById('messageBox');
    box.value = term;
    // Sound feedback for the "jump"
    SoundEngine.blip(400, 0.1);
    // Trigger the form submission
    document.getElementById('searchForm').requestSubmit();
};

function buildNodeHTML(res) {
    let wikiText = res.wiki?.extract || "";
    
    // Smart Entity Detection
    let nodeClass = "node-wiki";
    const bioMarkers = ["born", "died", "politician", "founder", "entrepreneur", "ceo", "dictator", "criminal" ,"scientist"];
    if (bioMarkers.some(m => wikiText.toLowerCase().includes(m))) nodeClass = "node-bio";

    let html = `<div class="wiki-entry"><h1 class="wiki-title-integrated">NODE: ${res.query.toUpperCase()}</h1>`;

    if (wikiText) {
        html += `<div class="node-container ${nodeClass}">
                    <p class="ZeroDay">[DATA_EXTRACTED]</p>
                    <h2 class="node-title">${res.wiki.title}</h2>
                    <p class="wiki-content">${wikiText}</p>
                 </div>`;
    }

    if (res.hn?.hits?.length > 0) {
        html += `<div class="node-container node-tech">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HN]</p>`;
        res.hn.hits.slice(0, 5).forEach(hit => {
            html += `<p class="wiki-content">▸ <a href="${hit.url}" target="_blank" class="hn-link">${hit.title}</a></p>`;
        });
        html += `</div>`;
    }

    if (res.cve?.vulnerabilities?.length > 0) {
        html += `<div class="node-container node-cve">
                    <p class="ZeroDay" style="color:#ff3131;">[THREAT_DETECTION // CVE]</p>`;
        res.cve.vulnerabilities.slice(0, 3).forEach(v => {
            html += `<p class="wiki-content"><strong>${v.cve.id}:</strong> ${v.cve.descriptions[0].value.substring(0, 100)}...</p>`;
        });
        html += `</div>`;
    }

if (res.wiki && res.query) {
    html += `<div class="drilldown-container" style="margin-top:20px; border-top:1px dashed #00ff41; padding-top:10px;">
                <p class="NullPointer" style="font-size:0.7rem;">> RELATED_NODES_DETECTED:</p>
                <button class="sync-btn" onclick="drilldownSearch('${res.query} history')">SYNC: ${res.query.toUpperCase()}_HISTORY</button>
                <button class="sync-btn" onclick="drilldownSearch('${res.query} technical')">SYNC: ${res.query.toUpperCase()}_TECH</button>
             </div>`;
}

    html += `</div>`;
    return html;
}

function typeWriter(element, html) {
    let i = 0;
    element.innerHTML = "";
    function type() {
        if (i < html.length) {
            if (html.charAt(i) === '<') { i = html.indexOf('>', i) + 1; } 
            else { i++; SoundEngine.typeClick(); }
            element.innerHTML = html.substring(0, i);
            setTimeout(type, 2);
        }
    }
    type();
}

function clearDisplay() {
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal display purged.</p>`;
    SoundEngine.blip(300, 0.1);
}

async function purgeVault() {
    if(confirm("PURGE ALL ARCHIVED NODES?")) {
        const formData = new FormData();
        formData.append('action', 'purge');
        await fetch('darkchat.php', { method: 'POST', body: formData });
        location.reload();
    }
}
</script>
</body>
</html>

