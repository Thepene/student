<?php
require 'config.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if ($full_name === '' || $username === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $check->execute([$username, $email]);

        if ($check->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)');
            if ($insert->execute([$full_name, $username, $email, $hashed, $role])) {
                $success = 'Registration successful. You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Inventory Pro</title>
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
            --success-bg: #dcfce7;
            --success-text: #166534;
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
            max-width: 460px;
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

        .message {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .message.error { background: var(--error-bg); color: var(--error-text); }
        .message.success { background: var(--success-bg); color: var(--success-text); }

        form {
            display: grid;
            gap: 12px;
        }

        input, select, button {
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
        <h2>Create account</h2>

        <?php if ($error !== ''): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="staff" selected>Staff</option>
            </select>
            <button type="submit">Register</button>
        </form>

        <div class="link">
            <a href="login.php">Already registered? Sign in</a>
        </div>
    </div>
</body>
</html>
