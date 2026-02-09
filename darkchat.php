<?php
require_once 'auth_check.php';
verify_access(); // Kick out unauthorized intruders
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

<button class="delete-btn" onclick="terminateSession()">TERMINATE SESSION</button>

<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">VAULT_ENTRIES: 0 NODES ARCHIVED</div>
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
    // Inject PHP username into JS engine
    const username = "<?php echo $username; ?>";
    
    function terminateSession() {
        if(confirm("BURN SESSION AND PURGE CACHE?")) {
            window.location.href = "logout.php";
        }
    }

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

    // ONLY ONE VERSION: Subdued, low-profile typing sound
    typeClick() {
        this.init();
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        
        osc.type = 'sine'; // Soft round wave
        osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
        
        gain.gain.setValueAtTime(0.015, this.ctx.currentTime); // Very low volume
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

const params = new URLSearchParams(window.location.search);
const username = params.get("username") || "Ghost_User";

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
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const rtt = connection ? connection.rtt : null;

    light.className = "";

    if (navigator.onLine) {
        if (rtt !== null && rtt <= 100) {
            light.textContent = `[CONNECTED: LOW_LATENCY (${rtt}ms)]`;
            light.classList.add("status-low");
        } else if (rtt !== null && rtt > 100) {
            light.textContent = `[CONNECTED: HIGH_LATENCY (${rtt}ms)]`;
            light.classList.add("status-high");
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

function runIntroSequence() {
    savedDiv = document.getElementById("savedMessages");
    document.getElementById("welcome").textContent = "USER // " + username.toUpperCase();
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

    if (lowerQuery.startsWith("/google ")) {
        renderExternalRedirect("GOOGLE", query.split("/google ")[1], `https://www.google.com/search?q=${encodeURIComponent(query.split("/google ")[1])}`);
        box.value = ""; return;
    }

    // 1. Setup Scanning UI
    savedDiv.innerHTML = `
        <div class="scanning-text flicker">> ACCESSING GLOBAL NODE: ${query.toUpperCase()}</div>
        <div class="progress-container"><div id="pbar" class="progress-bar"></div></div>
        <div id="matrix" class="matrix-loader"></div>
    `;

    const pbar = document.getElementById("pbar");
    const matrix = document.getElementById("matrix");
    savedDiv = document.getElementById("savedMessages");

    // 2. Start Data Fetching
const dataPromise = (async () => {
    const cached = await dbGet(lowerQuery);
    if (cached) return { type: 'cache', data: cached.html };

    try {
        const suggestRes = await fetch(`https://en.wikipedia.org/w/api.php?action=opensearch&search=${encodeURIComponent(query)}&limit=1&namespace=0&format=json&origin=*`);
        const suggestData = await suggestRes.json();
        const searchTitle = (suggestData[1] && suggestData[1].length > 0) ? suggestData[1][0] : query;
        const [wikiRes, hnRes, ddgRes, cveData] = await Promise.all([
            fetch(`https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(searchTitle)}?origin=*`),
            fetch(`https://hn.algolia.com/api/v1/search?query=${encodeURIComponent(query)}&tags=story`),
            fetch(`https://api.duckduckgo.com/?q=${encodeURIComponent(query)}&format=json&no_redirect=1&origin=*`),
            fetchVulnerabilities(query)
        ]);

        return {
            type: 'fresh',
            wiki: wikiRes.ok ? await wikiRes.json() : null,
            hn: await hnRes.json(),
            ddg: await ddgRes.json(),
            cve: cveData // Pass the CVE data through
        };
    } catch (err) {
        return { type: 'error', msg: err.message };
    }
})();

    // 3. Fake "Progress" while waiting for the dataPromise
    let progress = 0;
    const interval = setInterval(() => {
        if (progress < 90) {
            progress += Math.random() * 5; 
            pbar.style.width = Math.min(progress, 90) + "%";
            matrix.innerText = Math.random().toString(16).substring(2, 60);
            if (Math.random() > 0.7) SoundEngine.blip(80, 0.05); // Scanning blips
        }
    }, 100);

    // 4. Wait for data to arrive
    const result = await dataPromise;
    clearInterval(interval);

    // 5. Finalize animation
    pbar.style.width = "100%";
    SoundEngine.blip(200, 0.2); // Success blip
    await new Promise(r => setTimeout(r, 300));

    // 6. Display
    box.value = "";
if (result.type === 'cache') {
    typeWriter(savedDiv, result.data, 2);
} else if (result.type === 'error') {
    systemLog("> UPLINK ERROR: " + result.msg);
} else {
    if (!result.wiki && !result.ddg && (!result.hn.hits || result.hn.hits.length === 0)) {
        systemLog("> NODE NOT FOUND IN GLOBAL ARCHIVES.");
    } else {
        renderAggressiveResults(query, result.wiki, result.hn, result.ddg, result.cve);
    }
} 
}

function renderAggressiveResults(query, wiki, hn, ddg, cve) {
    currentActiveNode = query.toLowerCase();

        let html = `<div class="wiki-entry">
            <h1 class="wiki-title" style="color:#00ff41; border-bottom: 2px solid #00ff41; margin-top: 0;">INTEGRATED_INTELLIGENCE: ${query.toUpperCase()}</h1>`;

    // 1. Process Wikipedia
    let wikiText = "";
    if (wiki && wiki.extract) {
        wikiText = wiki.extract;
        const entityType = detectEntityType(wikiText);
        const isBio = entityType === "HUMAN_SUBJECT";
        const nodeClass = isBio ? "node-bio" : "node-wiki";
        const themeColor = isBio ? "#ffd700" : "#00ff41";

        html += `<div class="node-container ${nodeClass}">
                    <p class="ZeroDay" style="color:${themeColor};">[${isBio ? 'BIO_ARCHIVE' : 'WIKI_NODE'} // ${entityType}]</p>
                    <h2 style="color:${themeColor}; font-size:1.1rem; margin: 5px 0;">${wiki.title}</h2>
                    <p class="wiki-content">${wikiText}</p>
                 </div>`;
    }

    // 2. Process DuckDuckGo
    if (ddg) {
        let ddgContent = ddg.AbstractText || (ddg.RelatedTopics && ddg.RelatedTopics[0] ? ddg.RelatedTopics[0].Text : "");
        const isDuplicate = wikiText && ddgContent.substring(0, 50) === wikiText.substring(0, 50);

        if (ddgContent && !isDuplicate) {
            html += `<div class="node-container node-search">
                        <p class="ZeroDay" style="color: #00d4ff;">[SEARCH_NODE // DUCKDUCKGO]</p>
                        <p class="wiki-content">${ddgContent}</p>
                     </div>`;
        } else if (isDuplicate && ddg.AbstractURL) {
            html += `<div class="node-container node-search" style="padding: 5px 10px;">
                        <p class="ZeroDay" style="color: #00d4ff; font-size: 0.8rem;">[DDG_UPLINK: MATCH_CONFIRMED]</p>
                        <a href="${ddg.AbstractURL}" target="_blank" class="hn-link">VIEW EXTERNAL SOURCE</a>
                     </div>`;
        }
    }

    // 3. Process Hacker News
    if (hn.hits && hn.hits.length > 0) {
        html += `<div class="node-container node-tech">
                    <p class="ZeroDay" style="color:#ff5f1f;">[TECH_LOGS // HACKER_NEWS]</p>`;
        hn.hits.slice(0, 5).forEach(hit => {
            html += `<p class="wiki-content" style="margin-bottom:5px;">▸ <a href="${hit.url}" target="_blank" class="hn-link">${hit.title}</a></p>`;
        });
        html += `</div>`;
    }

   // 4.Process CVE.
    if (cve && cve.length > 0) {
        html += `<div class="node-container node-cve" style="border-left: 4px solid #ff003c;">
                    <p class="ZeroDay" style="color:#ff003c;">[THREAT_DETECTION // NIST_NVD]</p>`;
        cve.forEach(item => {
            const id = item.cve.id;
            const desc = item.cve.descriptions[0].value;
            html += `<p class="wiki-content" style="font-size:0.85rem; margin-bottom:8px;">
                        <strong style="color:#ff003c;">${id}:</strong> ${desc.substring(0, 150)}...
                     </p>`;
        });
        html += `</div>`;
    }

    html += `</div>`;
    dbSave(query, html);
    typeWriter(savedDiv, html, 2);
    if (wiki && wiki.title) displayRelated(wiki.title);
}

function renderExternalRedirect(platform, query, url) {
    savedDiv.innerHTML = `
        <div class="wiki-entry" style="border-left: 4px solid #00ff41; padding-left: 15px;">
            <h2 class="wiki-title">${platform}: ${query.toUpperCase()}</h2>
            <p class="wiki-content">> Direct extraction blocked by platform firewall.</p>
            <p class="wiki-content">> Manual handshake required to bypass security protocols.</p>
            <br>
            <a href="${url}" target="_blank" class="send-btn" style="text-decoration:none; display:inline-block; padding-top:10px;">
                ESTABLISH CONNECTION
            </a>
        </div>`;
}

function detectEntityType(text) {
  const t = text.toLowerCase();
  const techMarkers = ["operating system", "kernel", "software", "programming", "framework", "distribution"];
  if (techMarkers.some(marker => t.includes(marker))) return "SOFTWARE";
  const bioMarkers = ["born", "died", "aged", "politician", "founder", "entrepreneur", "activist", "author", "scientist", "actor", "musician"];
  if (bioMarkers.some(marker => t.includes(marker))) return "HUMAN";
  if (t.includes("country") || t.includes("republic")) return "NATION_STATE";
  return "OBJECT";
}

async function fetchVulnerabilities(query) {
    try {
        // Using a public API like OpenCVE or similar (Simplified for demo)
        const res = await fetch(`https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=${encodeURIComponent(query)}`);
        const data = await res.json();
        return data.vulnerabilities.slice(0, 3); // Return top 3 threats
    } catch (e) { return null; }
}

async function displayRelated(title) {
    try {
        const [relatedRes, searchRes] = await Promise.all([
            fetch(`https://en.wikipedia.org/api/rest_v1/page/related/${encodeURIComponent(title)}`),
            fetch(`https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=${encodeURIComponent(title)}&format=json&origin=*`)
        ]);
        const relatedData = await relatedRes.json();
        const div = document.createElement("div");
        div.className = "drilldown-area";
        div.innerHTML = `<p class="ZeroDay" style="color: #00ff41;">> CONTEXT_DRILLDOWN:</p>`;
        let nodes = [];
        if (relatedData.pages) relatedData.pages.slice(0, 5).forEach(p => nodes.push(p.displaytitle));
        [...new Set(nodes)].slice(0, 8).forEach(nodeName => {
            div.innerHTML += `<button class="send-btn sync-btn" onclick="autoSearch('${nodeName}')">SYNC: ${nodeName.toUpperCase()}</button>`;
        });
        savedDiv.appendChild(div);
    } catch(e) {}
}

function autoSearch(val) {
    document.getElementById("messageBox").value = val;
    saveMessage({ preventDefault: () => {} });
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
        const count = countReq.result;
        const statsElement = document.getElementById("vault-stats");
        
        // Use a 700px threshold to be safe
        if (window.innerWidth <= 700) {
            // Forces "NODES ARCHIVED" to a new line
            statsElement.innerHTML = `VAULT_ENTRIES: ${count}<br>NODES ARCHIVED`;
        } else {
            // Keeps it on one line for large screens
            statsElement.textContent = `VAULT_ENTRIES: ${count} NODES ARCHIVED`;
        }
    };
}

// Crucial: Call this on resize so it snaps instantly when you shrink the window
window.addEventListener('resize', updateVaultStats);
function typeWriter(element, html, speed = 10) {
    let i = 0;
    element.innerHTML = "";
    
    function type() {
        if (i < html.length) {
            if (html.charAt(i) === '<') {
                i = html.indexOf('>', i) + 1;
            } else {
                i++;
                if (html.charAt(i-1) !== " ") { 
                    SoundEngine.typeClick(); 
                }
            }
            element.innerHTML = html.substring(0, i);
            
            // Auto-scroll logic removed
            setTimeout(type, speed);
        }
    }
    type();
}
// This only clears the visual terminal, not the IndexedDB vault
function clearDisplay()
{
    const savedDiv = document.getElementById("savedMessages");
    savedDiv.innerHTML = `<p class="NullPointer">> Terminal display purged. Vault remains intact.</p>`;
    SoundEngine.blip(300, 0.1); // Optional: add a feedback sound
}

async function deleteEntry(query) {
    if(confirm(`Erase archived node: [${query}]?`)) {
        const tx = db.transaction(STORE_NAME, "readwrite");
        tx.objectStore(STORE_NAME).delete(query);
        tx.oncomplete = () => {
            updateVaultStats();
            savedDiv.innerHTML = `<p class="ZeroDay">> NODE [${query}] SUCCESSFULLY SCRUBBED.</p>`;
        }
    }
}

function systemLog(msg) {
    const p = document.createElement("p");
    p.className = "ZeroDay";
    p.textContent = msg;
    savedDiv.appendChild(p);
}

window.onload = () => { if(db) runIntroSequence(); };
</script>
</body>
</html>
