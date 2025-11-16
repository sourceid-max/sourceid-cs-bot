<?php
$ip = $_GET['ip'] ?? '';
$chatFile = $chat_dir . $ip . '.txt';

if (!file_exists($chatFile)) {
    echo '<div class="alert alert-danger">Chat session not found.</div>';
    exit;
}

$chatData = loadJSONFile($chatFile);
$userData = $chatData['user_data'] ?? [];
$messages = $chatData['chat'] ?? [];
?>

<div class="card">
    <div class="card-header">Chat Details - <?= htmlspecialchars($ip) ?></div>
    <div class="card-body">
        <div class="chat-detail">
            <h4>User Information</h4>
            <p><strong>Name:</strong> <?= htmlspecialchars($userData['name'] ?? 'Unknown') ?></p>
            <p><strong>Email