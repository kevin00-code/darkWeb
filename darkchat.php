<?php
require_once 'config.php';
if (isset($_GET['username'])) { $_SESSION['username'] = htmlspecialchars($_GET['username']); }
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit; }
$username = $_SESSION['username'];

// --- SERVER-SIDE SEARCH ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = trim($_POST['ajax_query']);
    $cached = get_from_vault($query);
    if ($cached) { echo json_encode(['type' => 'cache', 'data' => $cached['html']]); exit; }

    // Multi-Source Fetching (Wiki, HN, CVE)
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);

    $wiki = json_decode(@file_get_contents($wiki_url), true);
    $hn = json_decode(@file_get_contents($hn_url), true);
    $cve = json_decode(@file_get_contents($cve_url), true);

    $html = '<div class="wiki-entry"><h1 class="wiki-title" style="color:#00ff41; border-bottom: 2px solid #00ff41; margin-top: 0;">INTEGRATED_INTELLIGENCE: '.strtoupper($query).'</h1>';

    if (isset($wiki['extract'])) {
        $t = strtolower($wiki['extract']);
        // ENHANCED HUMAN DETECTION (YELLOW GOLD THEME)
        $bioMarkers = ["born", "died", "aged", "politician", "founder", "activist", "author", "scientist", "actor", "musician", "philanthropist", "human", "person", "leader", "physicist"];
        $isBio = false;
        foreach($bioMarkers as $m) { if(strpos($t, $m) !== false) { $isBio = true; break; } }
        
        $nodeClass = $isBio ? "node-bio" : "node-wiki";
        $themeColor = $isBio ? "#ffd700" : "#00ff41";
        $label = $isBio ? "HUMAN_SUBJECT" : "WIKI_NODE";

        $html .= '<div class="node-container '.$nodeClass.'">
                    <p class="ZeroDay" style="color:'.$themeColor.';">['.($isBio ? 'BIO_ARCHIVE' : 'WIKI_NODE').' // '.$label.']</p>
                    <h2 style="color:'.$themeColor.';">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wiki['extract'].'</p>
                 </div>';
    }

    // Hacker News & CVE Aggregation...
    if (isset($hn['hits'])) {
        $html .= '<div class="node-container"><p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>';
        foreach (array_slice($hn['hits'], 0, 3) as $hit) { $html .= '<p class="wiki-content">▸ '.$hit['title'].'</p>'; }
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
  <title>DARK_WIKI // MASTER_PHP</title>
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
        <button class="scrub-btn" style="color: #ff3e3e; border-color: #ff3e3e;" onclick="selfDestruct()">PURGE VAULT</button>
    </div>
  </div>

  <div id="savedMessages" class="messages">
      <p class="NullPointer">> Private uplink stable. Ready for query...</p>
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
        gain.gain.setValueAtTime(0.05, this.ctx.currentTime);
        osc.connect(gain); gain.connect(this.ctx.destination);
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

// CLEAR DISPLAY FUNCTION (Matches your HTML behavior)
function clearDisplay() {
    const display = document.getElementById("savedMessages");
    display.innerHTML = `<p class="NullPointer">> Terminal display purged. Vault remains intact.</p>`;
    SoundEngine.blip(300, 0.1);
}

// PURGE VAULT FUNCTION (Server-side deletion)
async function selfDestruct() {
    if(confirm("PERMANENTLY ERASE ALL VAULT DATA?")) {
        const formData = new FormData();
        formData.append('action', 'purge');
        await fetch('darkchat.php', { method: 'POST', body: formData });
        location.reload();
    }
}

// SEARCH LOGIC
document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const display = document.getElementById('savedMessages');
    const query = box.value;

    display.innerHTML = `<div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
                         <div class="progress-container"><div id="pbar" class="progress-bar" style="width:0%"></div></div>`;
    
    const formData = new FormData();
    formData.append('ajax_query', query);

    const response = await fetch('darkchat.php', { method: 'POST', body: formData });
    const result = await response.json();
    
    SoundEngine.blip(200, 0.2);
    typeEffect(display, result.data);
    box.value = "";
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
</script>
</body>
</html>
