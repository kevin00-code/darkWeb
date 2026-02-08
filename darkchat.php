<?php
require_once 'config.php';
if (isset($_GET['username'])) { $_SESSION['username'] = htmlspecialchars($_GET['username']); }
if (!isset($_SESSION['username'])) { header("Location: index.php"); exit; }

$username = $_SESSION['username'];

// --- SERVER-SIDE SCANNING ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = $_POST['ajax_query'];
    
    // Check Cache
    $cached = get_from_vault($query);
    if ($cached) {
        echo json_encode(['type' => 'cache', 'data' => $cached['html']]);
        exit;
    }

    // 1. Wikipedia Fetch
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $wiki_res = @file_get_contents($wiki_url);
    $wiki = json_decode($wiki_res, true);

    // 2. Hacker News Fetch
    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $hn_res = @file_get_contents($hn_url);
    $hn = json_decode($hn_res, true);

    // 3. NIST CVE Fetch (Threat Detection)
    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $cve_res = @file_get_contents($cve_url);
    $cve = json_decode($cve_res, true);

    // --- CONSTRUCT TERMINAL HTML ---
    $html = '<div class="wiki-entry">
             <h1 class="wiki-title-integrated" style="color:#00ff41; border-bottom: 2px solid #00ff41;">INTEGRATED_INTELLIGENCE: '.strtoupper($query).'</h1>';

    // Wikipedia Segment
    if (isset($wiki['extract'])) {
        $html .= '<div class="node-container">
                    <p class="ZeroDay">[WIKI_NODE // DATA_EXTRACTED]</p>
                    <h2 style="color:#00ff41; font-size:1.1rem;">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wiki['extract'].'</p>
                  </div>';
    }

    // Hacker News Segment
    if (isset($hn['hits']) && count($hn['hits']) > 0) {
        $html .= '<div class="node-container" style="border-left: 4px solid #ff5f1f;">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>';
        foreach (array_slice($hn['hits'], 0, 3) as $hit) {
            $html .= '<p class="wiki-content">▸ <a href="'.$hit['url'].'" target="_blank" style="color:#00d4ff;">'.$hit['title'].'</a></p>';
        }
        $html .= '</div>';
    }

    // Threat Detection (CVE) Segment
    if (isset($cve['vulnerabilities']) && count($cve['vulnerabilities']) > 0) {
        $html .= '<div class="node-container" style="border-left: 4px solid #ff003c;">
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

// Handle Purge
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

<div class="chat-container">
  <div class="header-row">
      <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: <?php echo get_vault_count(); ?> NODES ARCHIVED</div>
      <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
      <button class="scrub-btn" onclick="selfDestruct()" style="color:#ff3131; border-color:#ff3131;">PURGE</button>
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
    ctx: new (window.AudioContext || window.webkitAudioContext)(),
    typeClick() {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.type = 'sine'; osc.frequency.setValueAtTime(150, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.02, this.ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, this.ctx.currentTime + 0.05);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.05);
    },
    blip() {
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.frequency.setValueAtTime(800, this.ctx.currentTime);
        gain.gain.setValueAtTime(0.1, this.ctx.currentTime);
        osc.connect(gain); gain.connect(this.ctx.destination);
        osc.start(); osc.stop(this.ctx.currentTime + 0.1);
    }
};

function typeEffect(element, html, speed = 15) {
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

    display.innerHTML = `<div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
                         <div class="progress-container"><div class="progress-bar" style="width: 70%"></div></div>`;
    
    const formData = new FormData();
    formData.append('ajax_query', query);

    const response = await fetch('darkchat.php', { method: 'POST', body: formData });
    const result = await response.json();
    
    SoundEngine.blip();
    typeEffect(display, result.data);
    if(result.count !== undefined) {
        document.getElementById('vault-stats').textContent = `VAULT_ENTRIES: ${result.count} NODES ARCHIVED`;
    }
    box.value = "";
};

async function selfDestruct() {
    if(confirm("PURGE PRIVATE VAULT?")) {
        const formData = new FormData();
        formData.append('action', 'purge');
        await fetch('darkchat.php', { method: 'POST', body: formData });
        window.location.href = 'index.php';
    }
}
</script>
</body>
</html>