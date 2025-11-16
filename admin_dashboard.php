<?php
$chats = loadChatFiles();
$recentChats = array_slice($chats, 0, 10);
$totalChats = count($chats);
$satisfiedUsers = 0;
$totalRatings = [0 => 0, 1 => 0, 2 => 0];

foreach ($chats as $chat) {
    if ($chat['user_data']['satisfaction'] === 'helped') {
        $satisfiedUsers++;
    }
    $totalRatings[0] += $chat['ratings'][0];
    $totalRatings[1] += $chat['ratings'][1];
    $totalRatings[2] += $chat['ratings'][2];
}

$satisfactionRate = $totalChats > 0 ? round(($satisfiedUsers / $totalChats) * 100, 1) : 0;
?>

Mix ELIZA and Simple NLP Costumer Servis Chat Bot - by Sourceid<br>
No server Application Install, No expensive VPN, No Pyton, <br>
No Ai API, No subscription, No monthly fee, Easy to Install.... Total Freedom.<br><br>
Other my chat script https://github.com/sourceid-max/lochat2file/
<br><br>

<div class="card">
    <div class="card-header">Statistics Overview</div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center; padding: 20px; background: #e8f4fd; border-radius: 8px;">
                <h3 style="color: #3498db; margin-bottom: 10px;"><?= $totalChats ?></h3>
                <p>Total Chat Sessions</p>
            </div>
            <div style="text-align: center; padding: 20px; background: #e8f6f3; border-radius: 8px;">
                <h3 style="color: #27ae60; margin-bottom: 10px;"><?= $satisfactionRate ?>%</h3>
                <p>Satisfaction Rate</p>
            </div>
            <div style="text-align: center; padding: 20px; background: #fef9e7; border-radius: 8px;">
                <h3 style="color: #f39c12; margin-bottom: 10px;"><?= $totalRatings[2] ?></h3>
                <p>Positive Ratings (OK)</p>
            </div>
            <div style="text-align: center; padding: 20px; background: #fdedec; border-radius: 8px;">
                <h3 style="color: #e74c3c; margin-bottom: 10px;"><?= $totalRatings[0] ?></h3>
                <p>Negative Ratings (Bad)</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Recent Chat Sessions</div>
    <div class="card-body">
        <?php if (empty($recentChats)): ?>
            <p>No chat sessions found.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>User Name</th>
                        <th>Messages</th>
                        <th>Ratings (OK/Bad)</th>
                        <th>Satisfaction</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentChats as $chat): ?>
                        <tr class="<?= $chat['user_data']['satisfaction'] === 'no' ? 'unsatisfied' : '' ?>">
                            <td><?= htmlspecialchars($chat['ip']) ?></td>
                            <td><?= htmlspecialchars($chat['user_data']['name'] ?: 'Unknown') ?></td>
                            <td><?= $chat['message_count'] ?></td>
                            <td>
                                <span class="badge badge-success"><?= $chat['ratings'][2] ?> OK</span>
                                <span class="badge badge-danger"><?= $chat['ratings'][0] ?> Bad</span>
                            </td>
                            <td>
                                <?php if ($chat['user_data']['satisfaction']): ?>
                                    <span class="badge <?= $chat['user_data']['satisfaction'] === 'helped' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $chat['user_data']['satisfaction'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not Rated</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $chat['last_activity'] ?></td>
                            <td>
                                <a href="?action=chat_detail&ip=<?= urlencode($chat['ip']) ?>" class="btn btn-primary btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>