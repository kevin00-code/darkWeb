<?php
require_once 'config.php';
if (isset($_GET['username'])) { $_SESSION['username'] = htmlspecialchars($_GET['username']); }
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit; }
$username = $_SESSION['username'];

// --- MASTER SCANNING ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = trim($_POST['ajax_query']);
    
    // Check Vault
    $cached = get_from_vault($query);
    if ($cached) { echo json_encode(['type' => 'cache', 'data' => $cached['html']]); exit; }

    // Multi-Node Fetch
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $hn_url   = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $cve_url  = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $ddg_url  = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json&no_redirect=1";

    $wiki = json_decode(@file_get_contents($wiki_url), true);
    $hn   = json_decode(@file_get_contents($hn_url), true);
    $cve  = json_decode(@file_get_contents($cve_url), true);
    $ddg  = json_decode(@file_get_contents($ddg_url), true);

    $html = '<div class="wiki-entry"><h1 class="wiki-title" style="color:#00ff41; border-bottom: 2px solid #00ff41; margin-top: 0;">DATA_STREAM: '.strtoupper($query).'</h1>';

    $wikiText = "";
    // 1. WIKI & HUMAN DETECTION (GOLD THEME)
    if (isset($wiki['extract'])) {
        $wikiText = $wiki['extract'];
        $t = strtolower($wikiText);
        $bioMarkers = ["born", "died", "aged", "politician", "founder", "entrepreneur", "activist", "author", "scientist", "actor", "musician", "philanthropist", "physicist", "leader", "human", "president", "king"];
        $isBio = false;
        foreach($bioMarkers as $m) { if(strpos($t, $m) !== false) { $isBio = true; break; } }
        
        $nodeClass = $isBio ? "node-bio" : "node-wiki";
        $themeColor = $isBio ? "#ffd700" : "#00ff41";

        $html .= '<div class="node-container '.$nodeClass.'">
                    <p class="ZeroDay" style="color:'.$themeColor.';">['.($isBio ? 'BIO_ARCHIVE' : 'WIKI_NODE').' // '.($isBio ? 'HUMAN_SUBJECT' : 'ENTITY_NODE').']</p>
                    <h2 style="color:'.$themeColor.';">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wikiText.'</p>
                 </div>';
    }

    // 2. DDG LOGIC (LINK ONLY IF REPETITION)
    if (isset($ddg['AbstractText'])) {
        $ddgContent = $ddg['AbstractText'];
        // Compare first 50 chars for repetition
        $isRepetition = (substr($ddgContent, 0, 50) === substr($wikiText, 0, 50));
        
        $html .= '<div class="node-container node-search">
                    <p class="ZeroDay" style="color: #00d4ff;">[SEARCH_NODE // DUCKDUCKGO]</p>';
        
        if ($ddgContent != "" && !$isRepetition) {
            $html .= '<p class="wiki-content">'.$ddgContent.'</p>';
        } else {
            // Repetition detected or no summary: Show Link Only
            $html .= '<p class="wiki-content" style="font-style: italic; color: #555;">> Local repetition detected. Refer to external uplink.</p>';
        }
        
        $html .= '<div class="external-link-container">
                    <a href="https://duckduckgo.com/?q='.urlencode($query).'" target="_blank" class="external-establish-btn" style="color:#00d4ff;">
                        ESTABLISH_UPLINK: '.strtoupper($query).'
                    </a>
                  </div>
                 </div>';
    }

    // 3. TECH LOGS (HN) - 5 RESULTS
    if (isset($hn['hits'])) {
        $html .= '<div class="node-container"><p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>';
        foreach (array_slice($hn['hits'], 0, 5) as $hit) {
            $html .= '<p class="wiki-content">▸ <a href="'.$hit['url'].'" target="_blank" style="color:#ff5f1f; text-decoration:none;">'.$hit['title'].'</a></p>';
        }
        $html .= '</div>';
    }

    // 4. THREAT DETECTION (CVE) - 5 RESULTS
    if (isset($cve['vulnerabilities'])) {
        $html .= '<div class="node-container node-cve" style="border-left: 4px solid #ff3e3e;">
                    <p class="ZeroDay" style="color:#ff3e3e;">[THREAT_DETECTION // NIST_NVD]</p>';
        foreach (array_slice($cve['vulnerabilities'], 0, 5) as $v) {
            $id = $v['cve']['id'];
            $desc = $v['cve']['descriptions'][0]['value'];
            $html .= '<p class="wiki-content" style="font-size:0.85rem;"><strong style="color:#ff3e3e;">'.$id.':</strong> '.substr($desc, 0, 140).'...</p>';
        }
        $html .= '</div>';
    }

    $html .= '</div>';
    save_to_vault($query, $html);
    echo json_encode(['type' => 'fresh', 'data' => $html, 'count' => get_vault_count()]);
    exit;
}
?>
    
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DARK_WIKI // MASTER_TERMINAL</title>
  <link rel="stylesheet" href="chatbox.css">
</head>
<body>

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
    <div class="header-controls">
        <button class="scrub-btn" onclick="clearDisplay()">CLEAR TERMINAL</button>
        <button class="scrub-btn" style="color:#ff3e3e; border-color:#ff3e3e;" onclick="selfDestruct()">PURGE VAULT</button>
    </div>
  </div>

  <div id="savedMessages" class="messages">
      <p class="NullPointer">> Handshake stable. Systems nominal.</p>
  </div>

  <form class="input-area" id="searchForm">
    <textarea id="messageBox" rows="1" placeholder="Search database..." required></textarea>
    <input type="submit" value="SEARCH" class="send-btn">
  </form>
</div>

<script>
// --- SOUND SYSTEM ---
const SoundEngine = {
    ctx: null,
    init() { if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)(); },
    blip(freq = 150, duration = 0.05) {
        this.init();
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.05, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + duration);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + duration);
    },
    typeClick() {
        this.init();
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.015, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + 0.02);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.02);
    }
};

// --- CONNECTIVITY & RESPONSIVE UI ---
function updateOnlineStatus() {
    const light = document.getElementById("connection-light");
    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const rtt = conn ? conn.rtt : null;
    light.className = "";
    if (navigator.onLine) {
        light.textContent = rtt ? `[CONNECTED: ${rtt}ms]` : "[CONNECTED]";
        light.classList.add("status-low");
    } else {
        light.textContent = "[DISCONNECTED]";
        light.classList.add("status-offline", "blink");
    }
}
setInterval(updateOnlineStatus, 2000);

function updateVaultDisplay(count) {
    const stats = document.getElementById("vault-stats");
    stats.innerHTML = (window.innerWidth <= 700) ? `VAULT_ENTRIES: ${count}<br>NODES ARCHIVED` : `VAULT_ENTRIES: ${count} NODES ARCHIVED`;
}
window.addEventListener('resize', () => updateVaultDisplay(<?php echo get_vault_count(); ?>));

// --- ENGINE LOGIC ---
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

document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const display = document.getElementById('savedMessages');
    const query = box.value;

    display.innerHTML = `<div class="scanning-text flicker">> SCANNING GLOBAL NODES: ${query.toUpperCase()}</div>
                         <div class="progress-container"><div id="pbar" class="progress-bar" style="width:0%"></div></div>`;
    
    let p = 0;
    const iv = setInterval(() => { if(p < 90) { p += 10; document.getElementById('pbar').style.width = p + "%"; SoundEngine.blip(100, 0.03); } }, 150);

    const formData = new FormData();
    formData.append('ajax_query', query);

    const response = await fetch('darkchat.php', { method: 'POST', body: formData });
    const result = await response.json();
    
    clearInterval(iv);
    document.getElementById('pbar').style.width = "100%";
    SoundEngine.blip(200, 0.2);
    
    setTimeout(() => {
        typeEffect(display, result.data);
        if(result.count !== undefined) updateVaultDisplay(result.count);
        box.value = "";
    }, 400);
};

function clearDisplay() {
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal display purged. Vault remains intact.</p>`;
    SoundEngine.blip(300, 0.1);
}

async function selfDestruct() {
    if(confirm("PERMANENTLY ERASE VAULT?")) {
        const formData = new FormData();
        formData.append('action', 'purge');
        await fetch('darkchat.php', { method: 'POST', body: formData });
        location.reload();
    }
}
</script>
</body>
</html>
