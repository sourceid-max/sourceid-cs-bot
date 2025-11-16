<?php
$chats = loadChatFiles();
$itemsPerPage = 50;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalPages = ceil(count($chats) / $itemsPerPage);
$pagedChats = array_slice($chats, ($currentPage - 1) * $itemsPerPage, $itemsPerPage);
?>

<div class="card">
    <div class="card-header">Chat Sessions (Last 50)</div>
    <div class="card-body">
        <?php if (empty($pagedChats)): ?>
            <p>No chat sessions found.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>IP Address</th>
                        <th>User Name</th>
                        <th>Email/Phone</th>
                        <th>Order #</th>
                        <th>Messages</th>
                        <th>Ratings (OK/Bad)</th>
                        <th>Satisfaction</th>
                        <th>Status</th>
                        <th>Last Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagedChats as $chat): ?>
                        <tr class="<?= $chat['user_data']['satisfaction'] === 'no' ? 'unsatisfied' : '' ?>">
                            <td><?= htmlspecialchars($chat['ip']) ?></td>
                            <td><?= htmlspecialchars($chat['user_data']['name'] ?: 'Unknown') ?></td>
                            <td>
                                <?php if ($chat['user_data']['email'] ?? ''): ?>
                                    <?= htmlspecialchars($chat['user_data']['email']) ?>
                                <?php elseif ($chat['user_data']['telpn'] ?? ''): ?>
                                    <?= htmlspecialchars($chat['user_data']['telpn']) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($chat['user_data']['invoice'] ?: '-') ?></td>
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
                            <td>
                                <span class="badge <?= ($chat['user_data']['status'] ?? '') === 'sudah' ? 'badge-success' : 'badge-warning' ?>">
                                    <?= $chat['user_data']['status'] ?? 'belum' ?>
                                </span>
                            </td>
                            <td><?= $chat['last_activity'] ?></td>
                            <td>
                                <a href="?action=chat_detail&ip=<?= urlencode($chat['ip']) ?>" class="btn btn-primary btn-sm">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?action=chats&page=<?= $i ?>" 
                           style="padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; 
                                  <?= $i === $currentPage ? 'background: #3498db; color: white;' : 'background: white; color: #333;' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>