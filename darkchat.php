<?php
if (file_exists('auth_check.php')) {
    require_once 'auth_check.php';
    verify_access(); 
}
$username = $_SESSION['codename'];
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

<button class="delete-btn" onclick="clearMessages()">DELETE HISTORY</button>

<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: 0 SAVED</div>
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
      <p class="NullPointer">> System handshaking...</p>
  </div>

  <form class="input-area" onsubmit="saveMessage(event)">
    <textarea id="messageBox" rows="1" placeholder="Search database..." required></textarea>
    <input type="submit" value="SEARCH" class="send-btn">
  </form>
</div>

<script>
    // 1. Get username from PHP Session
    const username = "<?php echo $username; ?>";
    let currentActiveNode = null;

    const SoundEngine = {
        ctx: null,
        init() {
            if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        },
        blip(freq = 150, duration = 0.05) {
            this.init();
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
            gain.gain.setValueAtTime(0.05, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + duration);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + duration);
        },
        typeClick() {
            this.init();
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
            gain.gain.setValueAtTime(0.015, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + 0.02);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.02);
        }
    };

    const DB_NAME = "DarkWiki_Vault";
    const STORE_NAME = "archives";
    let db;
    let savedDiv;

    // 2. Open IndexedDB
    const request = indexedDB.open(DB_NAME, 1);
    request.onupgradeneeded = (e) => {
        db = e.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
            db.createObjectStore(STORE_NAME, { keyPath: "id" });
        }
    };
    request.onsuccess = (e) => { 
        db = e.target.result; 
        if(document.readyState === "complete") runIntroSequence(); 
    };

function updateOnlineStatus() {
    const light = document.getElementById("connection-light");
    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    
    if (navigator.onLine) {
        light.className = "status-low";
        if (conn.rtt > 300) 
            { light.className = "status-high"; }
        if (conn && conn.rtt) {
            light.textContent = `[ONLINE // LATENCY: ${conn.rtt}ms]`;
        } else {
            light.textContent = "[ONLINE // STABLE]";
        }
    } else {
        light.textContent = "[OFFLINE]";
        light.className = "status-offline blink";
    }
}
    setInterval(updateOnlineStatus, 2000);

    function runIntroSequence() {
        savedDiv = document.getElementById("savedMessages");
        savedDiv.innerHTML = ""; 
        const logs = [
            { cls: "NullPointer", msg: `> Connection established. Welcome, ${username}.` },
            { cls: "wiki-content", msg: `> Bypassing local firewalls...` },
            { cls: "ZeroDay", msg: `> GLOBAL UPLINK: STABLE` }
        ];
        logs.forEach((log, i) => {
            setTimeout(() => {
                const p = document.createElement("p");
                p.className = log.cls;
                p.innerHTML = log.msg;
                savedDiv.appendChild(p);
                updateVaultStats();
            }, i * 500);
        });
    }

async function saveMessage(e) {
    if (e) e.preventDefault();
    const box = document.getElementById("messageBox");
    const query = box.value.trim();
    if (!query) return;

    const lowerQuery = query.toLowerCase();

    savedDiv.innerHTML = `
        <div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
        <div class="progress-container"><div id="pbar" class="progress-bar"></div></div>
        <div id="matrix" class="matrix-loader"></div>
    `;

    const pbar = document.getElementById("pbar");
    const matrix = document.getElementById("matrix");

    // --- START OF REPLACEMENT ---
    const dataPromise = (async () => {
        const cached = await dbGet(lowerQuery);
        if (cached) return { type: 'cache', data: cached.html };
        
        try {
            // Get Wikipedia title suggestion first
            const suggestRes = await fetch(`https://en.wikipedia.org/w/api.php?action=opensearch&search=${encodeURIComponent(query)}&limit=1&namespace=0&format=json&origin=*`).catch(() => null);
            const suggestData = suggestRes ? await suggestRes.json() : [];
            const searchTitle = (suggestData && suggestData[1] && suggestData[1].length > 0) ? suggestData[1][0] : query;

            // Fetch everything in parallel with individual safety nets
            const [wikiRes, hnData, ddgData, cveData] = await Promise.all([
                fetch(`https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(searchTitle)}?origin=*`)
                    .then(res => res.ok ? res.json() : null).catch(() => null),
                fetch(`https://hn.algolia.com/api/v1/search?query=${encodeURIComponent(query)}&tags=story`)
                    .then(res => res.json()).catch(() => ({ hits: [] })),
                fetch(`https://api.duckduckgo.com/?q=${encodeURIComponent(query)}&format=json&no_redirect=1&origin=*`)
                    .then(res => res.json()).catch(() => ({ RelatedTopics: [] })),
                fetchVulnerabilities(query).catch(() => [])
            ]);

            return {
                type: 'fresh',
                wiki: wikiRes,
                hn: hnData,
                ddg: ddgData,
                cve: cveData
            };
        } catch (err) { 
            return { type: 'error', msg: "UPLINK_TIMEOUT: " + err.message }; 
        }
    })();
    // --- END OF REPLACEMENT ---

    let progress = 0;
    const interval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 10; 
            pbar.style.width = Math.min(progress, 90) + "%";
            matrix.innerText = Math.random().toString(16).substring(2, 60);
            if (Math.random() > 0.7) SoundEngine.blip(80, 0.05);
        }
    }, 100);

    const result = await dataPromise;
    clearInterval(interval);
    pbar.style.width = "100%";
    
    // Final rendering logic...
    if (result.type === 'cache') {
        typeWriter(savedDiv, result.data, 2);
    } else if (result.type === 'error') {
        systemLog("> " + result.msg);
    } else {
        renderAggressiveResults(query, result.wiki, result.hn, result.ddg, result.cve);
    }
    box.value = "";
}

    function renderAggressiveResults(query, wiki, hn, ddg, cve) {
        let html = `<div class="wiki-entry"><h1 class="wiki-title" style="color:#00ff41; border-bottom: 2px solid #00ff41; margin-top: 0;">INTEGRATED_INTELLIGENCE: ${query.toUpperCase()}</h1>`;
        // 1. WIKIPEDIA NODE
        if (wiki && wiki.extract) {
            html += `<div class="node-container node-wiki">
                        <p class="ZeroDay" style="color:#00ff41;">[WIKI_NODE // ARCHIVE]</p>
                        <h2 style="color:#00ff41; font-size:1.1rem; margin: 5px 0;">${wiki.title}</h2>
                        <p class="wiki-content">${wiki.extract}</p>
                     </div>`;
        }
        // 2. DDG
        if (ddg && ddg.RelatedTopics) {
        const onionUrl = "https://duckduckgogg42xjoc72x3sjasowoarfbgcmvfimaftt6twagswzczad.onion/?q=" + encodeURIComponent(query);
        html += `<div class="node-container">
                    <p style="color:#00d4ff;">[NET_RECON // DUCKDUCKGO]</p>
                    <a href="${onionUrl}" target="_blank" style="color:#d400ff; font-weight:bold; text-decoration:none;">>>> ESTABLISH TOR UPLINK (HIDDEN_WIKI_GATEWAY)</a>`;
        
        ddg.RelatedTopics.slice(0, 5).forEach(topic => { // LIMIT: 5
            if (topic.Text) html += `<p class="wiki-content" style="color:#00d4ff;">▸ ${topic.Text.substring(0,100)}...</p>`;
        });
        html += `</div>`;
        }

    // 3. HACKER NEWS NODE 
        if (hn.hits && hn.hits.length > 0) {
        html += `<div class="node-container node-tech"><p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>`;
        hn.hits.slice(0, 5).forEach(hit => {
            html += `<p class="wiki-content" style="margin-bottom:5px;">▸ <a href="${hit.url}" target="_blank" class="hn-link">${hit.title}</a></p>`;
        });
        html += `</div>`;
    }

    // 4. CVE VULNERABILITY NODE
         if (cve && cve.length > 0) {
        html += `<div class="node-container node-cve" style="border-left: 4px solid #ff3e3e; background: rgba(255, 62, 62, 0.05); padding: 10px;">
                    <p class="ZeroDay" style="color:#ff3e3e;">[VULNERABILITY_DB // NIST_NVD]</p>`;
        
        cve.forEach(item => {
            const cveId = item.cve.id;
            const descObj = item.cve.descriptions.find(d => d.lang === 'en');
            const desc = descObj ? descObj.value : "No description available.";
            const nvdUrl = `https://nvd.nist.gov/vuln/detail/${cveId}`;
            
         html += `<div style="margin-bottom: 12px; border-bottom: 1px dashed #ff3e3e; padding-bottom: 5px;">
            <a href="${nvdUrl}" target="_blank" style="color:#ff3e3e; font-weight:bold; text-decoration:none; display:block; cursor:pointer;">
                ⚠ ${cveId}
            </a>
            <p class="wiki-content" style="font-size: 0.8rem; color:#ffa0a0; margin: 2px 0;">${desc.substring(0, 200)}...</p>
         </div>`;
       });
       html += `</div>`;
    }
       html += `</div>`;
       dbSave(query, html);
       typeWriter(savedDiv, html, 2);
   }

async function fetchVulnerabilities(query) {
    try {
        const res = await fetch(`https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=${encodeURIComponent(query)}`);
        const data = await res.json();
        return data.vulnerabilities.slice(0, 5);
    } catch (e) { return null; }
}

    async function dbSave(query, html) {
        if (!db) return;
        const tx = db.transaction(STORE_NAME, "readwrite");
        tx.objectStore(STORE_NAME).put({ id: query.toLowerCase(), html: html, timestamp: Date.now() });
        tx.oncomplete = updateVaultStats;
    }

    async function dbGet(query) {
        return new Promise((resolve) => {
            if (!db) return resolve(null);
            const tx = db.transaction(STORE_NAME, "readonly");
            const req = tx.objectStore(STORE_NAME).get(query.toLowerCase());
            req.onsuccess = () => resolve(req.result);
        });
    }

    function clearMessages() {
        if(confirm("Purge ALL vault storage?")) {
            db.transaction(STORE_NAME, "readwrite").objectStore(STORE_NAME).clear();
            location.reload();
        }
    }

    async function updateVaultStats() {
        if (!db) return;
        const store = db.transaction(STORE_NAME, "readonly").objectStore(STORE_NAME);
        const countReq = store.count();
        countReq.onsuccess = () => {
            document.getElementById("vault-stats").textContent = `VAULT_ENTRIES: ${countReq.result} NODES ARCHIVED`;
        };
    }

function typeWriter(element, html, speed = 10) {
    let i = 0;
    element.innerHTML = "";
    
    // Detect mobile for dynamic speed
    const isMobile = window.innerWidth <= 600;
    const finalSpeed = isMobile ? 2 : speed; // Much faster on mobile

    function type() {
        if (i < html.length) {
            // Handle HTML tags so they don't "leak" into the typewriter effect
            if (html.charAt(i) === '<') {
                i = html.indexOf('>', i) + 1;
            } else {
                i++;
                // Sound only on desktop to prevent mobile audio lag
                if (!isMobile) SoundEngine.typeClick(); 
            }
            element.innerHTML = html.substring(0, i);
            setTimeout(type, finalSpeed);
        }
    }
    type();
}

    function clearDisplay() {
        document.getElementById("savedMessages").innerHTML = `<p class="NullPointer">> Terminal display purged.</p>`;
        SoundEngine.blip(300, 0.1);
    }

    function systemLog(msg) {
        const p = document.createElement("p");
        p.className = "ZeroDay";
        p.textContent = msg;
        document.getElementById("savedMessages").appendChild(p);
    }

    window.onload = () => { if(db) runIntroSequence(); };
</script>
</body>
</html>

