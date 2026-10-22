<?php
require 'config.php';
session_start();

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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, ?)');
            if ($stmt->execute([$full_name, $username, $email, $hashedPassword, $role])) {
                $success = 'Registration successful. You can now log in.';
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
    <title>Inventory Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .form-box {
            background: #fff;
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        h2 { text-align: center; color: #111827; }
        input, select, button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border-radius: 8px;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }
        button {
            background: #2563eb;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .error { background: #fee2e2; color: #991b1b; }
        .success { background: #dcfce7; color: #166534; }
        a {
            display: block;
            margin-top: 16px;
            text-align: center;
            text-decoration: none;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Register</h2>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="staff" selected>Staff</option>
                <option value="manager">Manager</option>
            </select>
            <button type="submit">Create Account</button>
        </form>

        <a href="login.php">Already have an account? Login</a>
    </div>
</body>
</html>
