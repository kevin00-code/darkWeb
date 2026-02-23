<?php
require_once 'auth_check.php';
verify_access();

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$query = urlencode($_GET['query']);
$url = "https://ahmia.fi/search/?q=" . $query;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
$html = curl_exec($ch);
curl_close($ch);

if (!$html) {
    echo json_encode(['error' => 'Failed to reach node']);
    exit;
}

$doc = new DOMDocument();
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);

$results = $xpath->query("//li[contains(@class, 'result')]");

$prunedData = [];
foreach ($results as $i => $node) {
    if ($i >= 5) break; 
    $title = $xpath->evaluate("string(.//h4/a | .//h4)", $node); 
    $link  = $xpath->evaluate("string(.//cite | .//span[@class='url'])", $node);
    $desc  = $xpath->evaluate("string(.//p)", $node); 
    
    if (!$link) {
        $link = $xpath->evaluate("string(.//a/@href)", $node);
        if (strpos($link, 'redirect') !== false) {
            $parts = explode('search_result=', $link);
            $link = isset($parts[1]) ? urldecode(explode('&', $parts[1])[0]) : $link;
        }
    }

    if ($title || $link) {
        $prunedData[] = [
            'title' => trim($title) ?: "Untitled Node",
            'link'  => trim($link) ?: "No Link Found",
            'desc'  => trim($desc) ?: "No metadata available."
        ];
    }
}
echo json_encode($prunedData);
