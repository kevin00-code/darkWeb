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
        return `<div class="relevance-strike">${query.toUpperCase()}</div>`;
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
        if (!response.ok) return { found: false, breaches: [] };
        return await response.json();
    } catch (e) {
        return { found: false, breaches: [] };
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

    savedDiv.innerHTML = ""; // Clear previous content, including intro sequence

    const scanNode = document.createElement("div");
    scanNode.className = "binary-stream";
    scanNode.innerHTML = `> QUERY: ${query.toUpperCase()}<br>> ANALYZING_NETWORK_NODES...`;
    savedDiv.appendChild(scanNode);
    
    try {
        const onionRegex = /^(http|https):\/\/[a-z0-9]+\.onion(\/.*)?$/i;
        const emailRegex = /^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/;
        let onionDirectResult = null;
        let breachIntelPromise = Promise.resolve({found: false, breaches: []}); // Default empty breach result

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
        const breach = results[6].status === "fulfilled" ? results[6].value : {found: false, breaches: []};

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
    let charIndex = 0;
    container.innerHTML = ""; // Clear container initially

    const type = () => {
        if (charIndex < cleanHtml.length) {
            let nextContent = "";
            let char = cleanHtml[charIndex];

            if (char === '<') {
                // It's an HTML tag, find its end
                const tagEndIndex = cleanHtml.indexOf('>', charIndex);
                if (tagEndIndex !== -1) {
                    nextContent = cleanHtml.substring(charIndex, tagEndIndex + 1);
                    charIndex = tagEndIndex + 1;
                } else {
                    // Malformed tag, treat as regular character
                    nextContent = char;
                    charIndex++;
                }
            } else {
                // It's a regular character
                nextContent = char;
                charIndex++;
            }

            // Append the next content and the blinking cursor
            container.innerHTML = cleanHtml.substring(0, charIndex) + "█";
            setTimeout(type, speed);
        } else {
            // All content typed, remove cursor
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

        // Store all Wikipedia extract text for DDG filtering
        let wikiExtractText = wiki && wiki.extract ? wiki.extract.toLowerCase() : "";

        // 1. Breach Intel (Red) - Only display if found
        if (breach && breach.found) {
            const leakData = breach.breaches?.[0] || "UNKNOWN_SOURCE"; 
            html += `
            <div class="node-block breach-node">
                <div class="node-label label-breach">[BREACH_NODE // ALERT]</div>
                <p class="wiki-content breach-content">⚠ IDENTITY_EXPOSURE</p>
                <p class="cve-description">SOURCE: ${leakData.toUpperCase()}</p>
            </div>`;
        }

        // 2. Wikipedia (Gold for human bio with white text, Blue for others)
        if (wiki && wiki.extract) {
            const humanKeywords = ["person", "biography", "politician", "actor", "writer", "scientist", "president", "ceo", "businessman", "musician", "artist", "founder", "activist",
                                   "chairperson", "executive", "entrepreneur", "singer", "painter", "actress", "founding", "activist",
                                   "presidents", "ceos", "businessmen", "musicians", "artists", "actors", "politicians", "founders", "activists"];
            const humanKeywordsRegex = new RegExp(`\\b(${humanKeywords.join("|")})\\b`, "i");

            const isHumanBio = (wiki.description && humanKeywordsRegex.test(wiki.description)) ||
                               (wiki.extract.toLowerCase().includes("was born") || wiki.extract.toLowerCase().includes("died in")) ||
                               (wiki.birthdate || wiki.deathdate);
            
            let wikiClass = "wiki-border"; // Default blue
            let wikiLabelClass = "label-wiki"; // Default blue
            let wikiContentClass = "wiki-content-blue";

            if (isHumanBio) {
                wikiClass = "golden-node";
                wikiLabelClass = "golden-header";
                wikiContentClass = "wiki-content-white"; // White text for human bios
            }

            html += `
            <div class="node-block ${wikiClass}">
                <div class="node-label ${wikiLabelClass}">[WIKI_NODE]</div>
                <p class="wiki-content ${wikiContentClass}">${wiki.extract}</p>
                ${getRelevanceLabel(wiki.extract, query)}
            </div>`;
        }

        // 3. Hacker News (Green, including links)
        if (hn && hn.hits && hn.hits.length > 0) {
            html += `
            <div class="node-block hn-node">
                <div class="node-label label-hn">[HN_NODE]</div>`;
            hn.hits.slice(0, 3).forEach(hit => {
                html += `<a href="${hit.url}" target="_blank" class="hn-link">▸ ${hit.title}</a>`;
            });
            html += `</div>`;
        }

        // 4. DuckDuckGo (Purple) - Filtered and Suggestions separated
        if (ddg) {
            let uniqueSet = new Set();
            let ddgMainHtml = "";
            let ddgSuggestionsHtml = "";
            const rawResults = [...(ddg.RelatedTopics || []), {Text: ddg.AbstractText}]
                .filter(t => t && t.Text);

            rawResults.forEach(res => {
                const resTextLower = res.Text.toLowerCase();
                const isWikiDuplicate = wikiExtractText.includes(resTextLower);

                if (!uniqueSet.has(resTextLower) && !isWikiDuplicate) {
                    uniqueSet.add(resTextLower);
                    // Simple heuristic for suggestions: if it contains a dash and multiple words
                    if (res.Text.includes("-") && res.Text.split(" ").length > 2) {
                        ddgSuggestionsHtml += `<p class="wiki-content ddg-content">▸ ${res.Text}</p>`;
                    } else {
                        ddgMainHtml += `<p class="wiki-content ddg-content">▸ ${res.Text}</p>`;
                    }
                }
            });

            if (ddgMainHtml || ddgSuggestionsHtml) {
                html += `
                <div class="node-block ddg-node">
                    <div class="node-label label-ddg">[NET_RECON // DDG]</div>`;
                if (ddgMainHtml) {
                    html += ddgMainHtml;
                }
                if (ddgSuggestionsHtml) {
                    html += `<h3 class="ddg-suggestions-heading">[SUGGESTIONS]</h3>` + ddgSuggestionsHtml;
                }
                html += `</div>`;
            }
        }

        // 5. CVE (Red)
        if (cve && cve.vulnerabilities) {
            cve.vulnerabilities.slice(0, 3).forEach(v => {
                html += renderCVE(v.cve, query);
            });
        }

        // 6. Onion Nodes (Existing Purple/Pink) - Now with clickable links and proxy option
        if (onion && onion.length > 0) {
            html += `
            <div class="node-block onion-border">
                <div class="node-label label-onion">[TOR_UPLINK // .ONION]</div>
                <p class="system-message">NOTE: .onion links require a Tor Browser or a Tor-to-Web proxy.</p>
                ${onion.map(s => {
                    const tor2webLink = s.link.replace(".onion", ".onion.ly"); // Example proxy
                    return `<div class="onion-item">
                        <a href="${s.link}" target="_blank" class="onion-link-text">${s.link}</a> 
                        <span class="system-message">(<a href="${tor2webLink}" target="_blank" class="onion-proxy-link">Open via Proxy</a>)</span>
                        <p class="onion-desc">${s.title}</p>
                    </div>`;
                }).join("")}
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
    return `
    <div class="node-cve">
        <div class="cve-header">[VULNERABILITY_DB]</div>
        <div class="cve-id-row">
            <span class="cve-warning-icon">⚠</span>
            <a href="https://nvd.nist.gov/vuln/detail/${item.id}" target="_blank" class="cve-link">
                <span>${item.id}</span>
            </a>
            <span class="cve-score">(SCORE: ${score})</span>
        </div>
        <div class="cve-description">
            ${description.length > 300 ? description.substring(0, 300) + "..." : description}
        </div>
        ${getRelevanceLabel(item.id + description, query)}
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
