<?php
require_once 'config.php';

// Session Security
if (isset($_GET['username'])) { $_SESSION['username'] = htmlspecialchars($_GET['username']); }
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit; }
$username = $_SESSION['username'];

// --- MASTER SEARCH ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = $_POST['ajax_query'];
    
    // Check Vault Cache first
    $cached = get_from_vault($query);
    if ($cached) {
        echo json_encode(['type' => 'cache', 'data' => $cached['html']]);
        exit;
    }

    // 1. Wikipedia Intel
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $wiki = json_decode(@file_get_contents($wiki_url), true);

    // 2. Hacker News (Strictly 5)
    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $hn = json_decode(@file_get_contents($hn_url), true);

    // 3. NIST CVE (Strictly 5)
    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $cve = json_decode(@file_get_contents($cve_url), true);

    // 4. DuckDuckGo (Verification & External Uplink)
    $ddg_url = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json";
    $ddg = json_decode(@file_get_contents($ddg_url), true);

    // --- ENTITY DETECTION (HUMAN SUBJECT) ---
    $wikiText = $wiki['extract'] ?? '';
    $nodeClass = "node-wiki"; 
    $themeColor = "#00ff41"; // Default Matrix Green
    $isHuman = false;

    // Advanced Human Detection Markers
    $bioMarkers = ["born", "died", "politician", "founder", "entrepreneur", "activist", "author", "scientist", "actor", "musician", "leader", "president", "human", "subject"];
    foreach($bioMarkers as $m) {
        if (stripos($wikiText, $m) !== false) {
            $isHuman = true;
            $nodeClass = "node-bio"; // Yellow Gold in CSS
            $themeColor = "#ffd700"; 
            break;
        }
    }

    // --- CONSTRUCT HTML OUTPUT ---
    $html = '<div class="wiki-entry">';
    $html .= '<h1 class="wiki-title-integrated" style="border-bottom-color:'.$themeColor.'">NODE: '.strtoupper($query).'</h1>';

    // Wikipedia Segment
    if (!empty($wikiText)) {
        $html .= '<div class="node-container '.$nodeClass.'">
                    <p class="ZeroDay" style="color:'.$themeColor.';">[DATA_EXTRACTED // '.($isHuman ? "HUMAN_SUBJECT" : "OBJECT_NODE").']</p>
                    <h2 style="color:'.$themeColor.';">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wikiText.'</p>
                  </div>';
    }

    // Hacker News Segment (Strictly 5)
    if (!empty($hn['hits'])) {
        $html .= '<div class="node-container node-tech">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HN_INTEL]</p>';
        foreach (array_slice($hn['hits'], 0, 5) as $hit) {
            $html .= '<p class="wiki-content">▸ <a href="'.$hit['url'].'" target="_blank" class="hn-link">'.$hit['title'].'</a></p>';
        }
        $html .= '</div>';
    }

    // CVE Segment (Strictly 5)
    if (!empty($cve['vulnerabilities'])) {
        $html .= '<div class="node-container node-cve">
                    <p class="ZeroDay" style="color:#ff3131;">[THREAT_DETECTION // CVE_5]</p>';
        foreach (array_slice($cve['vulnerabilities'], 0, 5) as $v) {
            $html .= '<p class="wiki-content"><strong>'.$v['cve']['id'].':</strong> '.substr($v['cve']['descriptions'][0]['value'],0,100).'...</p>';
        }
        $html .= '</div>';
    }

    // DDG Repetition Check & External Link
    $ddgLink = $ddg['AbstractURL'] ?? '';
    if (!empty($ddgLink)) {
        // If content is already in Wiki, just show the link to avoid repetition
        $html .= '<div class="node-container external-link-container">
                    <p class="ZeroDay" style="color:#00d4ff;">[EXTERNAL_VERIFICATION // DDG]</p>
                    <p class="wiki-content">Redundant data suppressed. Source verified.</p>
                    <a href="'.$ddgLink.'" target="_blank" class="scrub-btn external-establish-btn" style="color:#00d4ff; border-color:#00d4ff;">ESTABLISH EXTERNAL UPLINK</a>
                  </div>';
    }

    $html .= '</div>';

    save_to_vault($query, $html);
    echo json_encode(['data' => $html, 'count' => get_vault_count()]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DARK_WIKI // MASTER</title>
  <link rel="stylesheet" href="chatbox.css">
</head>
<body>

<div class="chat-container">
    <div class="header-row">
        <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: <?php echo get_vault_count(); ?> NODES</div>
        <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
        <button class="scrub-btn" onclick="location.href='index.php'" style="color:#ff3131; border-color:#ff3131;">EXIT</button>
    </div>

    <div class="status-bar">
        <div>STATUS: <span id="connection-light" class="status-low">[CONNECTED]</span></div> 
        <button class="scrub-btn" onclick="clearDisplay()">CLEAR TERMINAL</button>
    </div>

    <div id="savedMessages" class="messages"></div>

    <form class="input-area" id="searchForm">
        <textarea id="messageBox" rows="1" placeholder="Search global nodes..." required></textarea>
        <input type="submit" value="SEARCH" class="send-btn">
    </form>
</div>

<script>
// --- SOUND ENGINE ---
const SoundEngine = {
    ctx: null,
    init() { if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)(); },
    blip(freq = 150, dur = 0.05) {
        this.init();
        const osc = this.ctx.createOscillator();
        const g = this.ctx.createGain();
        osc.type = 'square'; osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        g.gain.setValueAtTime(0.05, this.ctx.currentTime);
        osc.connect(g); g.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + dur);
    },
    type() {
        this.init();
        const osc = this.ctx.createOscillator();
        const g = this.ctx.createGain();
        osc.type = 'sine'; osc.frequency.setValueAtTime(90 + Math.random()*20, this.ctx.currentTime);
        g.gain.setValueAtTime(0.01, this.ctx.currentTime);
        osc.connect(g); g.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.02);
    }
};

// --- HANDSHAKE & LATENCY ---
function updateLatency() {
    const light = document.getElementById("connection-light");
    const rtt = navigator.connection ? navigator.connection.rtt : 50;
    light.className = "";
    if (!navigator.onLine) {
        light.textContent = "[DISCONNECTED]"; light.classList.add("status-offline", "blink");
        SoundEngine.blip(60, 0.1);
    } else if (rtt > 200) {
        light.textContent = `[LATENCY_HIGH: ${rtt}ms]`; light.classList.add("status-high");
    } else {
        light.textContent = `[LATENCY_LOW: ${rtt}ms]`; light.classList.add("status-low");
    }
}
setInterval(updateLatency, 3000);

// --- DYNAMIC VAULT RESIZING ---
function checkLayout() {
    const v = document.getElementById("vault-stats");
    const count = v.textContent.match(/\d+/) || [0];
    v.innerHTML = (window.innerWidth < 700) ? `VAULT: ${count[0]}<br>NODES` : `VAULT_ENTRIES: ${count[0]} NODES ARCHIVED`;
}
window.onresize = checkLayout;

// --- SEARCH LOGIC ---
document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const display = document.getElementById('savedMessages');
    const query = box.value;

    display.innerHTML = `<div class="scanning-text flicker">> SCANNING_NODE: ${query.toUpperCase()}</div>
                         <div class="progress-container"><div id="pbar" class="progress-bar"></div></div>`;
    
    const formData = new FormData();
    formData.append('ajax_query', query);

    const res = await fetch('darkchat.php', { method: 'POST', body: formData });
    const json = await res.json();
    
    SoundEngine.blip(200, 0.2);
    display.innerHTML = json.data;
    document.getElementById('vault-stats').textContent = `VAULT_ENTRIES: ${json.count} NODES`;
    checkLayout();
    box.value = "";
};

function clearDisplay() {
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal display cleared.</p>`;
    SoundEngine.blip(100, 0.1);
}

window.onload = () => {
    updateLatency();
    checkLayout();
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> System handshake successful. Uplink ready.</p>`;
};
</script>
</body>
</html>
