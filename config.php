<?php
session_start();
$db_file = 'vault_db.json';

// Initialize the database if missing
if (!file_exists($db_file)) {
    file_put_contents($db_file, json_encode([]), LOCK_EX);
}

function get_user_id() {
    return $_SESSION['username'] ?? 'guest';
}

function read_vault_safe() {
    global $db_file;
    $content = file_get_contents($db_file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function get_vault_count() {
    $data = read_vault_safe();
    $user = get_user_id();
    return (isset($data[$user]) && is_array($data[$user])) ? count($data[$user]) : 0;
}

function save_to_vault($query, $html) {
    global $db_file;
    $data = read_vault_safe();
    $user = get_user_id();
    
    if (!isset($data[$user])) { $data[$user] = []; }
    
    $data[$user][strtolower($query)] = [
        'html' => $html,
        'timestamp' => time()
    ];
    file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function get_from_vault($query) {
    $data = read_vault_safe();
    $user = get_user_id();
    return $data[$user][strtolower($query)] ?? null;
}

function purge_user_vault() {
    global $db_file;
    $data = read_vault_safe();
    $user = get_user_id();
    if (isset($data[$user])) {
        unset($data[$user]);
        file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
?>