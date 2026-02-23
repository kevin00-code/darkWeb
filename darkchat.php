<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["username"])) {
    $_SESSION["codename"] = $_POST["username"];
}
if (file_exists("auth_check.php")) {
    require_once "auth_check.php";
    verify_access(); 
}

$username = $_SESSION["codename"] ?? "GHOST";
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
<div class="chat-container">
  <div class="header-row">
      <div class="header-left">
          <div id="vault-stats" class="ZeroDay">HISTORY: 0 SAVED</div>
      </div>
      <div class="header-center">
          <h1 id="welcome">USER // <?php echo strtoupper($username); ?></h1>
      </div>
      <div class="header-right">
<div class="side-menu-wrapper">
    <input type="checkbox" id="menu-toggle">
    <label for="menu-toggle" class="kebab-btn">
        <span></span>
        <span></span>
        <span></span>
    </label>

    <div class="side-menu-content">
        <div class="menu-header">[CONTROL_PANEL]</div>
        <div class="menu-items">
            <a href="#" class="menu-link" onclick="clearMessages()">
                <span class="gold-text">></span> DELETE HISTORY</a>
            <a href="logout.php" class="menu-link">
                <span class="gold-text">></span> TERMINATE_SESSION
            </a>
        </div>
    </div>
    <label for="menu-toggle" class="menu-overlay"></label>
</div>
      </div>
  </div>

  <div class="status-bar">
    <div>STATUS: <span id="connection-light">[...]</span></div> 
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
const username = "<?php echo htmlspecialchars(strtoupper($username), ENT_QUOTES); ?>";
let isTyping = false; // The Gatekeeper

//database intialization
let db;
const DB_NAME = "DarkWikiVault";
const STORE_NAME = "intel_cache";
const request = indexedDB.open(DB_NAME, 1);

const savedDiv = document.getElementById("savedMessages");

document.getElementById("messageBox").addEventListener("keydown", function(e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        saveMessage(e);
}
})

function systemSafetyReset() {
    isTyping = false;
    const box = document.getElementById("messageBox");
    if(box) box.placeholder = "SYSTEM_READY // ENTER_QUERY...";
    console.log("SAFETY_TRIGGERED: Lock released.");
}

function getRelevanceLabel(text, query) {
    if (!text || !query) return "";
    const isMatch = text.toLowerCase().includes(query.toLowerCase());
    
    if (!isMatch) {
        return `<div style="margin-top: 10px;">
                    <del style="text-decoration: line-through; color: #ff3131; opacity: 0.6; font-size: 0.8rem;">${query.toUpperCase()}</del>
                </div>`;
    }
    return "";
}

function updateOnlineStatus() {
    const light = document.getElementById("connection-light");
    if (!navigator.onLine) {
        light.textContent = "[OFFLINE]";
        light.className = "status-offline blink";
    } else {
        light.className = "status-low";
        light.textContent = "[ONLINE // STABLE]";
    }
}

updateOnlineStatus();
setInterval(updateOnlineStatus, 2000);

// Audio Engine
    const SoundEngine = {
        ctx: null,
        init() { if (!this.ctx) this.ctx = new (window.AudioContext || window.webkitAudioContext)(); },
        blip(freq = 150, duration = 0.05) {
            this.init();
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = "square";
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
            osc.type = "sine";
            osc.frequency.setValueAtTime(90 + Math.random() * 20, this.ctx.currentTime);
            gain.gain.setValueAtTime(0.015, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + 0.02);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.02);
        }
    };

request.onblocked = function() {
    console.warn("DATABASE_LOCKED: Closing old connections...");
    alert("System recovery required. Refreshing session...");
    location.reload();
};

request.onupgradeneeded = (e) => {
    db = e.target.result;
    if (!db.objectStoreNames.contains(STORE_NAME)) {
        db.createObjectStore(STORE_NAME, { keyPath: "id" });
    }
};

request.onsuccess = (e) => {
    db = e.target.result;
    console.log("SYSTEM_READY: Vault Link Established");
    updateVaultStats();
};

function forceUnlock() {
    isTyping = false;
    const box = document.getElementById("messageBox");
    if(box) box.placeholder = "SEARCH_QUERY_INITIATED...";
    console.log("LOCK_CLEARED: Input restored.");
}

    window.onload = () => { 
        const retry = setInterval(() => {
            if (db) {
                runIntroSequence();
                clearInterval(retry);
            }
        }, 100);
    };

    // Original Intro Sequence
    function runIntroSequence() {
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

async function fetchBreachIntel(target) {
    try {
        const response = await fetch(`BreachNode.php?target=${encodeURIComponent(target)}`);
        if (!response.ok) return { found: false, leaks: [] };
        return await response.json();
    } catch (e) {
        return { found: false, leaks: [] };
    }
}

    // Social Reconnaissance Node
async function fetchSocialIntel(target) {
   const platforms = [
        { name: "GitHub", url: `https://api.github.com/users/${target}` },
        { name: "HN_User", url: `https://hn.algolia.com/api/v1/users/${target}` }
    ];

    const results = await Promise.all(platforms.map(p => 
        fetch(p.url).then(r => r.ok ? r.json() : null).catch(() => null)
    ));

    return results.filter(r => r !== null);
}

    // SEARCH LOGIC
async function saveMessage(e) {
    if (e) e.preventDefault();
    const box = document.getElementById("messageBox");
    const query = box.value.trim();
    if (!query || isTyping) return;
    
    isTyping = true;
    box.value = "";
    box.placeholder = "SEARCHING DATABASE...";

    const scanNode = document.createElement("div");
    scanNode.className = "binary-stream";
    scanNode.innerHTML = `> QUERY: ${query.toUpperCase()}<br>> ANALYZING_NETWORK_NODES...`;
    savedDiv.appendChild(scanNode);
    
    try {
        const onionRegex = /^(http|https):\/\/[a-z0-9]+\.onion(\/.*)?$/i;
        const emailRegex = /^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/;
        let onionDirectResult = null;
        let breachIntelPromise = Promise.resolve({found: false, leaks: []}); // Default empty breach result

        if (onionRegex.test(query)) {
            onionDirectResult = [{ title: "Direct .onion Link", link: query, desc: "" }];
        } else if (emailRegex.test(query)) {
            breachIntelPromise = fetchBreachIntel(query);
        }

        const results = await Promise.allSettled([
            fetch(`https://en.wikipedia.org/api/rest_v1/page/summary/${encodeURIComponent(query)}?origin=*`).then(r => r.json()),
            fetch(`https://hn.algolia.com/api/v1/search?query=${encodeURIComponent(query)}&tags=story`).then(r => r.json()),
            fetch(`https://api.duckduckgo.com/?q=${encodeURIComponent(query)}&format=json&no_redirect=1&origin=*`).then(r => r.json()),
            fetchVulnerabilities(query),
            onionDirectResult ? Promise.resolve(onionDirectResult) : fetchOnionNodes(query), // Use direct result if available
            fetchSocialIntel(query),
            breachIntelPromise // Use the conditional breach intel promise
        ]);

        const wiki = results[0].status === "fulfilled" ? results[0].value : null;
        const hn = results[1].status === "fulfilled" ? results[1].value : { hits: [] };
        const ddg = results[2].status === "fulfilled" ? results[2].value : null;
        const cve = results[3].status === "fulfilled" ? results[3].value : null;
        const onion = results[4].status === "fulfilled" ? results[4].value : [];
        const social = results[5].status === "fulfilled" ? results[5].value : [];
        const breach = results[6].status === "fulfilled" ? results[6].value : {found: false, leaks: []};

        scanNode.remove();
        renderAggressiveResults(query, wiki, hn, ddg, cve, onion, social, breach);
        
    } catch (error) {
        console.error("SYSTEM_FAILURE:", error);
        scanNode.innerHTML = `<span class="system-error">>> ERROR: UPLINK_LOST.</span>`;
        isTyping = false; // RELEASE THE LOCK
        const box = document.getElementById("messageBox");
        if (box) box.placeholder = "SYSTEM_READY...";
    }
}

function typeWriter(container, html, speed = 10) {
    const cleanHtml = DOMPurify.sanitize(html);
    const temp = document.createElement("div");
    temp.innerHTML = cleanHtml;
    const textContent = temp.textContent || temp.innerText || ""; 
    let i = 0;

    const type = () => {
        if (i < textContent.length) {
            container.textContent = textContent.substring(0, i) + "█";
            i++;
            setTimeout(type, speed);
        } else {
            container.innerHTML = cleanHtml;
            isTyping = false; 
            const box = document.getElementById("messageBox");
            if (box) box.placeholder = "SYSTEM_READY...";
            const messages = document.getElementById("savedMessages");
            messages.scrollTop = messages.scrollHeight;
        }
    };
    
    type();
}

function renderAggressiveResults(query, wiki, hn, ddg, cve, onion, social, breach) {
    try {
        let html = `<div class="intel-report">
            <h1 class="report-header">> ANALYSIS_COMPLETE: ${query.toUpperCase()}</h1>`;

        // 1. Breach Intel (Red) - Only display if found
        if (breach && breach.found) {
            const leakData = breach.breaches?.[0] || "UNKNOWN_SOURCE"; // Adjusted to match BreachNode.php output
            html += `
            <div class="node-block" style="border-left:2px solid #ff3131;">
                <div class="node-label" style="color:#ff3131;">[BREACH_NODE // ALERT]</div>
                <p class="wiki-content" style="color:#ff3131;">⚠ IDENTITY_EXPOSURE</p>
                <p class="cve-description">SOURCE: ${leakData.toUpperCase()}</p>
            </div>`;
        }

        // 2. Wikipedia (Gold for human bio, Blue for others)
        if (wiki && wiki.extract) {
            const isHumanBio = (wiki.description && (wiki.description.toLowerCase().includes("person") || wiki.description.toLowerCase().includes("biography"))) ||
                               (wiki.extract.toLowerCase().includes("was born") || wiki.extract.toLowerCase().includes("died in"));
            
            let wikiClass = "wiki-border"; // Default blue
            let wikiLabelClass = "label-wiki"; // Default blue
            let wikiContentStyle = "";

            if (isHumanBio) {
                wikiClass = "golden-node";
                wikiLabelClass = "golden-header";
            } else {
                wikiContentStyle = "color:#00d4ff;"; // Explicitly set blue for content if not human bio
            }

            html += `
            <div class="node-block ${wikiClass}">
                <div class="node-label ${wikiLabelClass}">[WIKI_NODE]</div>
                <p class="wiki-content" style="${wikiContentStyle}">${wiki.extract}</p>
                ${getRelevanceLabel(wiki.extract, query)}
            </div>`;
        }

        // 3. Hacker News (Green, including links)
        if (hn && hn.hits && hn.hits.length > 0) {
            html += `
            <div class="node-block" style="border-left:2px solid #39ff14;">
                <div class="node-label" style="color:#39ff14;">[HN_NODE]</div>`;
            hn.hits.slice(0, 3).forEach(hit => {
                html += `<p class="wiki-content"><a href="${hit.url}" target="_blank" style="color:#39ff14; text-decoration:none;">▸ ${hit.title}</a></p>`;
            });
            html += `</div>`;
        }

        // 4. DuckDuckGo (Purple) - Only display results not in Wiki
        if (ddg) {
            let uniqueSet = new Set();
            let ddgHtml = "";
            const rawResults = [...(ddg.RelatedTopics || []), {Text: ddg.AbstractText}]
                .filter(t => t && t.Text);

            rawResults.forEach(res => {
                const isWikiDuplicate = (wiki && wiki.extract && wiki.extract.toLowerCase().includes(res.Text.toLowerCase()));

                if (!uniqueSet.has(res.Text) && !isWikiDuplicate) {
                    uniqueSet.add(res.Text);
                    ddgHtml += `<p class="wiki-content" style="color:#8a2be2;">▸ ${res.Text}</p>`; // Purple color
                }
            });

            if (ddgHtml) {
                html += `
                <div class="node-block" style="border-left:2px solid #8a2be2;">
                    <div class="node-label" style="color:#8a2be2;">[NET_RECON // DDG]</div>
                    ${ddgHtml}
                </div>`;
            }
        }

        // 5. CVE (Red)
        if (cve && cve.vulnerabilities) {
            cve.vulnerabilities.slice(0, 3).forEach(v => {
                html += renderCVE(v.cve, query);
            });
        }

        // 6. Onion Nodes (Existing Purple/Pink) - Now with clickable links
        if (onion && onion.length > 0) {
            html += `
            <div class="node-block onion-border">
                <div class="node-label label-onion">[TOR_UPLINK // .ONION]</div>
                ${onion.map(s =>
                    `<div class="onion-item">
                        <a href="${s.link}" target="_blank" class="onion-link-text">${s.link}</a>
                        <p class="onion-desc">${s.title}</p>
                    </div>`
                ).join("")}
            </div>`;
        }

        html += `</div>`;

        const resultNode = document.createElement("div");
        savedDiv.appendChild(resultNode);

        typeWriter(resultNode, html, 2);
        dbSave(query, html);

    } catch (err) {
        console.error("Render error:", err);
    } finally {
        isTyping = false;
    }
}

async function fetchVulnerabilities(query) {
    const url = `https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=${encodeURIComponent(query)}`;

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3500);

        const response = await fetch(url, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        const data = await response.json();
        return (data && data.vulnerabilities) ? data : null; 
    } catch (e) {
        console.warn("CVE Timeout or Blocked:", e);
        return null; 
    }
}

async function fetchOnionNodes(query) {
    // This function will now only be called for non-direct .onion URL searches
    // The direct .onion URL handling is done in saveMessage
    try {
        const response = await fetch(`proxy.php?query=${encodeURIComponent(query)}`);
        const data = await response.json();
        if (!data || data.error || !Array.isArray(data)) return []; 
        
        return data.map(site => ({
            title: site.title || "Unknown Service",
            link: site.link || "N/A",
            desc: site.desc || "No metadata available."
        }));
    } catch (e) {
        console.warn("Onion node fetch failed:", e);
        return [];
    }
}

function renderCVE(item, query) {
    const description = item.descriptions.find(d => d.lang === "en")?.value || "Data Redacted.";
    const score = item.metrics?.cvssMetricV31?.[0]?.cvssData?.baseScore || "N/A";
    const relevance = getRelevanceLabel(item.id + description, query);
    return `
    <div class="node-cve">
        <div class="cve-header">[VULNERABILITY_DB]</div>
        <div class="cve-id-row">
            <span class="cve-warning-icon">⚠</span>
            <a href="https://nvd.nist.gov/vuln/detail/${item.id}" target="_blank" style="color:inherit; text-decoration:none;">
                <span>${item.id}</span>
            </a>
            <span style="font-size:0.7rem; margin-left:10px;">(SCORE: ${score})</span>
        </div>
        <div class="cve-description">
            ${description.length > 300 ? description.substring(0, 300) + "..." : description}
        </div>
        ${relevance}
        <div class="cve-divider"></div>
    </div>`;
}

    // Helper Functions
    async function dbSave(q, h) { const tx = db.transaction(STORE_NAME, "readwrite"); tx.objectStore(STORE_NAME).put({ id: q.toLowerCase(), html: h }); updateVaultStats(); }
    async function dbGet(q) { return new Promise(res => { const req = db.transaction(STORE_NAME, "readonly").objectStore(STORE_NAME).get(q.toLowerCase()); req.onsuccess = () => res(req.result); }); }
    function updateVaultStats() { if (!db) return; const req = db.transaction(STORE_NAME, "readonly").objectStore(STORE_NAME).count(); req.onsuccess = () => document.getElementById("vault-stats").textContent = `HISTORY: ${req.result} SAVED`; }
    function clearMessages() { if(confirm("Purge Vault?")) { db.transaction(STORE_NAME, "readwrite").objectStore(STORE_NAME).clear(); location.reload(); } }
    function clearDisplay() { savedDiv.innerHTML = `<p class="NullPointer">> Terminal display purged.</p>`; }
    function systemLog(msg) { const p = document.createElement("p"); p.className = "ZeroDay"; p.textContent = msg; savedDiv.appendChild(p); }
</script>
</body>
</html>
