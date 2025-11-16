<?php
session_start();

/** By sourceid */

include('config.php');

// Authentication
function checkAuth() {
    global $admin_password;
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
            $_SESSION['admin_logged_in'] = true;
        } else {
            showLoginForm();
            exit;
        }
    }
}

function showLoginForm() {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Customer Service Bot</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                background: #f5f5f5; 
                display: flex; 
                justify-content: center; 
                align-items: center; 
                height: 100vh; 
                margin: 0; 
            }
            .login-container { 
                background: white; 
                padding: 40px; 
                border-radius: 10px; 
                box-shadow: 0 0 20px rgba(0,0,0,0.1); 
                width: 300px; 
            }
            .login-container h2 { 
                text-align: center; 
                margin-bottom: 30px; 
                color: #2c3e50; 
            }
            .form-group { 
                margin-bottom: 20px; 
            }
            .form-group input { 
                width: 100%; 
                padding: 10px; 
                border: 1px solid #ddd; 
                border-radius: 5px; 
                box-sizing: border-box; 
            }
            .form-group button { 
                width: 100%; 
                padding: 10px; 
                background: #2c3e50; 
                color: white; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer; 
            }
            .form-group button:hover { 
                background: #34495e; 
            }
            .error { 
                color: #e74c3c; 
                text-align: center; 
                margin-top: 10px; 
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>Admin Login</h2>
            <form method="post">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Enter admin password" required>
                </div>
                <div class="form-group">
                    <button type="submit">Login</button>
                </div>';
                
            if ($_POST['password'] ?? '' && $_POST['password'] !== $admin_password) {
                echo '<div class="error">Invalid password!</div>';
            }
                
            echo '
            </form>
        </div>
    </body>
    </html>';
}

// Check authentication
checkAuth();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Load data functions
function loadJSONFile($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    $content = file_get_contents($filename);
    return json_decode($content, true) ?: [];
}

function saveJSONFile($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

function loadProducts() {
    global $data_dir;
    $products = [];
    if (file_exists($data_dir . 'product.txt')) {
        $lines = file($data_dir . 'product.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $data = explode('|', $line);
            if (count($data) >= 6) {
                $products[] = [
                    'id' => trim($data[0]),
                    'nama' => trim($data[1]),
                    'harga' => trim($data[2]),
                    'kategori' => trim($data[3]),
                    'tag' => trim($data[4]),
                    'keterangan' => trim($data[5])
                ];
            }
        }
    }
    return $products;
}

function loadChatFiles() {
    global $chat_dir;
    $chats = [];
    $files = glob($chat_dir . '*.txt');
    
    foreach ($files as $file) {
        $ip = basename($file, '.txt');
        $data = loadJSONFile($file);
        
        if (isset($data['user_data']) && isset($data['chat'])) {
            $userData = $data['user_data'];
            $chatMessages = $data['chat'];
            
            // Calculate ratings
            $ratings = [0 => 0, 1 => 0, 2 => 0];
            foreach ($chatMessages as $message) {
                if ($message['sender'] === 'bot') {
                    $ratings[$message['rate']]++;
                }
            }
            
            $lastMessage = end($chatMessages);
            $lastActivity = date('Y-m-d H:i:s', filemtime($file));
            
            $chats[] = [
                'ip' => $ip,
                'user_data' => $userData,
                'message_count' => count($chatMessages),
                'ratings' => $ratings,
                'last_activity' => $lastActivity,
                'last_message' => $lastMessage['message'] ?? '',
                'file' => $file
            ];
        }
    }
    
    // Sort by last activity (newest first)
    usort($chats, function($a, $b) {
        return strtotime($b['last_activity']) - strtotime($a['last_activity']);
    });
    
    return $chats;
}

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['form_type'] ?? '') {
        case 'edit_company':
            $companyData = [
                'name' => $_POST['name'] ?? '',
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'wa' => $_POST['wa'] ?? '',
                'telegram' => $_POST['telegram'] ?? '',
                'cara_beli' => $_POST['cara_beli'] ?? '',
                'security_policy' => $_POST['security_policy'] ?? '',
                'warranty_policy' => $_POST['warranty_policy'] ?? ''
            ];
            saveJSONFile($data_dir . 'company.txt', $companyData);
            $message = 'Company information updated successfully!';
            break;
            
        case 'add_pattern':
            $patterns = loadJSONFile($data_dir . 'pattern.txt');
            $newPattern = [
                $_POST['triggers'] ? array_map('trim', explode(',', $_POST['triggers'])) : [],
                $_POST['responses'] ? array_map('trim', explode(',', $_POST['responses'])) : []
            ];
            $patterns[] = $newPattern;
            saveJSONFile($data_dir . 'pattern.txt', $patterns);
            $message = 'Pattern added successfully!';
            break;
            
        case 'edit_pattern':
            $patterns = loadJSONFile($data_dir . 'pattern.txt');
            $index = $_POST['pattern_index'];
            if (isset($patterns[$index])) {
                $patterns[$index] = [
                    $_POST['triggers'] ? array_map('trim', explode(',', $_POST['triggers'])) : [],
                    $_POST['responses'] ? array_map('trim', explode(',', $_POST['responses'])) : []
                ];
                saveJSONFile($data_dir . 'pattern.txt', $patterns);
                $message = 'Pattern updated successfully!';
            }
            break;
            
        case 'move_pattern':
            $patterns = loadJSONFile($data_dir . 'pattern.txt');
            $index = $_POST['pattern_index'];
            $direction = $_POST['direction'];
            
            if (isset($patterns[$index]) && (
                ($direction === 'up' && $index > 0) || 
                ($direction === 'down' && $index < count($patterns) - 1)
            )) {
                $newIndex = $direction === 'up' ? $index - 1 : $index + 1;
                $temp = $patterns[$index];
                $patterns[$index] = $patterns[$newIndex];
                $patterns[$newIndex] = $temp;
                saveJSONFile($data_dir . 'pattern.txt', $patterns);
                $message = 'Pattern moved successfully!';
            }
            break;
            
        case 'delete_pattern':
            $patterns = loadJSONFile($data_dir . 'pattern.txt');
            $index = $_POST['pattern_index'];
            if (isset($patterns[$index])) {
                array_splice($patterns, $index, 1);
                saveJSONFile($data_dir . 'pattern.txt', $patterns);
                $message = 'Pattern deleted successfully!';
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Customer Service Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        .sidebar nav ul {
            list-style: none;
        }
        .sidebar nav li {
            margin-bottom: 5px;
        }
        .sidebar nav a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background: #34495e;
            border-left: 4px solid #3498db;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: #34495e;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .card-body {
            padding: 20px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 2px;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 12px; }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .chat-detail {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .chat-message {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
        }
        .chat-user {
            background: #e3f2fd;
            margin-left: 20%;
        }
        .chat-bot {
            background: #f5f5f5;
            margin-right: 20%;
        }
        .message-time {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .unsatisfied {
            background: #f8d7da !important;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <nav>
                <ul>
                    <li><a href="?action=dashboard" class="<?= $action === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="?action=chats" class="<?= $action === 'chats' ? 'active' : '' ?>">Chat Sessions</a></li>
                    <li><a href="?action=company" class="<?= $action === 'company' ? 'active' : '' ?>">Company Info</a></li>
                    <li><a href="?action=patterns" class="<?= $action === 'patterns' ? 'active' : '' ?>">ELIZA Patterns</a></li>
                    <li><a href="?action=intents" class="<?= $action === 'intents' ? 'active' : '' ?>">NLP Intents</a></li>
                    <li><a href="?action=nlptest" class="<?= $action === 'nlptest' ? 'active' : '' ?>">NLP Test</a></li>
                    <li><a href="?action=products" class="<?= $action === 'products' ? 'active' : '' ?>">Products</a></li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header" style="display: flex; justify-content: space-between;">
                <h1><?= ucfirst($action) ?> Management</h1> 
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <?php
            switch ($action) {
                case 'dashboard':
                    include 'admin_dashboard.php';
                    break;
                case 'chats':
                    include 'admin_chats.php';
                    break;
                case 'company':
                    include 'admin_company.php';
                    break;
                case 'patterns':
                    include 'admin_patterns.php';
                    break;
                case 'intents':
                    echo "Not in this version - pls move to Pro Version";
                    break;
                case 'nlptest':
                    include 'nlp_test.php';
                    break;
                case 'products':
                    include 'admin_products.php';
                    break;
                case 'chat_detail':
                    include 'admin_chat_detail.php';
                    break;
                default:
                    include 'admin_dashboard.php';
            }
            ?>
        </div>
    </div>

    <script>
        function confirmDelete(message) {
            return confirm(message || 'Are you sure you want to delete this item?');
        }
        
        function movePattern(index, direction) {
            document.getElementById('pattern_index').value = index;
            document.getElementById('direction').value = direction;
            document.getElementById('move_pattern_form').submit();
        }
    </script>
</body>
</html>