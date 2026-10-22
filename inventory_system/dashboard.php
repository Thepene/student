<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['role'] ?? 'staff';
$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 920px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        h1 { color: #111827; }
        .role {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: bold;
            text-transform: capitalize;
            margin-bottom: 16px;
        }
        .menu {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .menu a {
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            display: inline-block;
        }
        .menu a.logout { background: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
        <div class="role"><?php echo htmlspecialchars($userRole); ?></div>

        <div class="menu">
            <a href="inventory.php">Inventory</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
</body>
</html>
