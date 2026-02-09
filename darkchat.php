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

// --- AJAX SEARCH ENGINE ---
if (isset($_POST['ajax_query'])) {
    $query = $_POST['ajax_query'];
    
    // Check Cache
    $cached = get_from_vault($query);
    if ($cached) {
        echo json_encode(['type' => 'cache', 'data' => $cached['html']]);
        exit;
    }

    // Intel Fetching (Wiki, HN, CVE, DDG)
    $wiki_url = "https://en.wikipedia.org/api/rest_v1/page/summary/" . urlencode($query);
    $wiki = json_decode(@file_get_contents($wiki_url), true);
    $wikiText = $wiki['extract'] ?? '';

    // Human Subject Detection (Trigger Gold Theme)
    $nodeClass = "node-wiki";
    $bioMarkers = ["born", "died", "politician", "founder", "entrepreneur", "scientist", "actor"];
    foreach($bioMarkers as $m) {
        if (stripos($wikiText, $m) !== false) {
            $nodeClass = "node-bio"; 
            break;
        }
    }

    $hn_url = "https://hn.algolia.com/api/v1/search?query=" . urlencode($query) . "&tags=story";
    $hn = json_decode(@file_get_contents($hn_url), true);

    $cve_url = "https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=" . urlencode($query);
    $cve = json_decode(@file_get_contents($cve_url), true);

    $ddg_url = "https://api.duckduckgo.com/?q=" . urlencode($query) . "&format=json";
    $ddg = json_decode(@file_get_contents($ddg_url), true);

    // --- CONSTRUCT HTML ---
    $html = '<div class="wiki-entry">
             <h1 class="wiki-title-integrated">NODE: '.strtoupper($query).'</h1>';

    if (!empty($wikiText)) {
        $html .= '<div class="node-container '.$nodeClass.'">
                    <p class="ZeroDay">[DATA_EXTRACTED]</p>
                    <h2 class="node-title">'.$wiki['title'].'</h2>
                    <p class="wiki-content">'.$wikiText.'</p>
                  </div>';
    }

    if (!empty($hn['hits'])) {
        $html .= '<div class="node-container node-tech">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HN]</p>';
        foreach (array_slice($hn['hits'], 0, 5) as $hit) {
            $html .= '<p class="wiki-content">▸ <a href="'.$hit['url'].'" target="_blank" class="hn-link">'.$hit['title'].'</a></p>';
        }
        $html .= '</div>';
    }

    if (!empty($cve['vulnerabilities'])) {
        $html .= '<div class="node-container node-cve">
                    <p class="ZeroDay" style="color:#ff3131;">[THREAT_DETECTION // CVE]</p>';
        foreach (array_slice($cve['vulnerabilities'], 0, 5) as $v) {
            $html .= '<p class="wiki-content"><strong>'.$v['cve']['id'].':</strong> '.substr($v['cve']['descriptions'][0]['value'], 0, 100).'...</p>';
        }
        $html .= '</div>';
    }

    if (!empty($ddg['AbstractURL'])) {
        $html .= '<div class="node-container node-ddg">
                    <p class="ZeroDay" style="color:#00d4ff;">[EXTERNAL_VERIFICATION]</p>
                    <a href="'.$ddg['AbstractURL'].'" target="_blank" class="external-establish-btn">ESTABLISH EXTERNAL UPLINK</a>
                  </div>';
    }

    $html .= '</div>';

    save_to_vault($query, $html);
    echo json_encode(['data' => $html, 'count' => get_vault_count()]);
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

<button class="delete-btn" onclick="purgeVault()">DELETE HISTORY</button>

<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: <?php echo get_vault_count(); ?> NODES ARCHIVED</div>
      </div>
      
      <div class="header-center">
          <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
      </div>

      <div class="header-right"></div> </div>

  <div class="status-bar">
    <div>STATUS: <span id="connection-light" class="status-low">[CONNECTED]</span></div> 
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
// Search and UI Logic
document.getElementById('searchForm').onsubmit = async (e) => {
    e.preventDefault();
    const box = document.getElementById('messageBox');
    const display = document.getElementById('savedMessages');
    const query = box.value;

    display.innerHTML = `<div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>`;
    
    const formData = new FormData();
    formData.append('ajax_query', query);

    const response = await fetch('darkchat.php', { method: 'POST', body: formData });
    const result = await response.json();
    
    display.innerHTML = result.data;
    document.getElementById('vault-stats').textContent = `VAULT_ENTRIES: ${result.count} NODES ARCHIVED`;
    box.value = "";
};

function clearDisplay() {
    document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal cleared.</p>`;
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
