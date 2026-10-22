<?php
require 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Inventory Pro</title>
    <style>
        :root {
            --bg1: #eff6ff;
            --bg2: #f8fafc;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --card: rgba(255,255,255,0.9);
            --line: #dbe3f0;
            --text: #0f172a;
            --muted: #64748b;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, var(--bg1) 0%, var(--bg2) 100%);
            color: var(--text);
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 30px;
        }

        .logo {
            text-align: center;
            margin-bottom: 12px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        h2 {
            text-align: center;
            margin: 0 0 16px;
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        form {
            display: grid;
            gap: 12px;
        }

        input, button {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--line);
            font-size: 14px;
        }

        button {
            border: none;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: 700;
            cursor: pointer;
        }

        .link {
            text-align: center;
            margin-top: 16px;
        }

        .link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="logo">Inventory Pro</div>
        <h2>Login</h2>

        <?php if ($error !== ''): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="username" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>

        <div class="link">
            <a href="register.php">Need an account? Register</a>
        </div>
    </div>
</body>
</html>
