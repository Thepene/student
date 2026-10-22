<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['role'] ?? 'staff';
$username = $_SESSION['username'] ?? 'User';

$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM (
    SELECT p.id, COALESCE(SUM(CASE WHEN st.type = 'IN' THEN st.quantity ELSE -st.quantity END), 0) AS stock
    FROM products p
    LEFT JOIN stock_transactions st ON st.product_id = p.id
    GROUP BY p.id
    HAVING stock <= 5
) t")->fetchColumn();

$incoming30 = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM stock_transactions WHERE type = 'IN' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$outgoing30 = (int)$pdo->query("SELECT COALESCE(SUM(quantity), 0) FROM stock_transactions WHERE type = 'OUT' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();

$categoryBreakdown = $pdo->query("SELECT p.category, COALESCE(SUM(CASE WHEN st.type = 'IN' THEN st.quantity ELSE -st.quantity END), 0) AS net_stock
    FROM products p
    LEFT JOIN stock_transactions st ON st.product_id = p.id
    WHERE p.category IS NOT NULL AND p.category <> ''
    GROUP BY p.category
    ORDER BY net_stock DESC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

$recentActivity = $pdo->query("SELECT st.*, p.name AS product_name FROM stock_transactions st JOIN products p ON p.id = st.product_id ORDER BY st.created_at DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$lowStockItems = $pdo->query("SELECT p.id, p.name, p.sku, p.category, COALESCE(SUM(CASE WHEN st.type = 'IN' THEN st.quantity ELSE -st.quantity END), 0) AS stock
    FROM products p
    LEFT JOIN stock_transactions st ON st.product_id = p.id
    GROUP BY p.id, p.name, p.sku, p.category
    HAVING stock <= 5
    ORDER BY stock ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Inventory</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --danger: #ef4444;
            --muted: #6b7280;
            --text: #0f172a;
            --line: #e5e7eb;
            --shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(140deg, #eef5ff 0%, #f8fafc 100%);
            color: var(--text);
        }

        a { text-decoration: none; }

        .topbar {
            background: rgba(255,255,255,0.9);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            backdrop-filter: blur(12px);
        }

        .topbar-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
        }

        .brand {
            font-weight: 700;
            font-size: 1.4rem;
        }

        .nav {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .nav a {
            color: var(--muted);
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
        }

        .nav a.active, .nav a:hover {
            background: #eff6ff;
            color: var(--primary);
        }

        .user-pill {
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 999px;
            padding: 8px 12px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 24px 60px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
        }

        .header h1 {
            font-size: 2rem;
            margin: 0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .blue { background: #dbeafe; color: #1d4ed8; }
        .green { background: #d1fae5; color: #047857; }
        .amber { background: #fef3c7; color: #b45309; }
        .red { background: #fee2e2; color: #b91c1c; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-label {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 24px;
        }

        .card {
            background: var(--card);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 1.2rem;
        }

        .chart-bars {
            display: flex;
            align-items: end;
            gap: 12px;
            height: 220px;
            padding-top: 20px;
        }

        .bar-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: end;
            gap: 8px;
            height: 100%;
        }

        .bar {
            width: 100%;
            max-width: 52px;
            border-radius: 10px 10px 0 0;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 10px;
        }

        .bar-label {
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }

        th {
            color: var(--muted);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.04em;
        }

        .chip {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .chip.in { background: #dcfce7; color: #166534; }
        .chip.out { background: #fee2e2; color: #991b1b; }

        .alert {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 12px 14px;
            color: #9a4d00;
            font-weight: 700;
            margin-bottom: 14px;
        }

        @media (max-width: 980px) {
            .dashboard-grid { grid-template-columns: 1fr 1fr; }
            .content-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">Inventory Pro</div>
            <nav class="nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="inventory.php">Inventory</a>
                <a href="reports.php">Reports</a>
            </nav>
            <div class="user-pill"><?php echo htmlspecialchars($username); ?> • <?php echo htmlspecialchars($userRole); ?></div>
        </div>
    </header>

    <main class="page">
        <div class="header">
            <h1>Inventory Overview</h1>
            <a href="logout.php" style="padding: 10px 14px; border-radius: 10px; background: #fee2e2; color: #991b1b; font-weight: 700;">Logout</a>
        </div>

        <?php if (!empty($lowStockItems)): ?>
            <div class="alert">Low stock notification: <?php echo count($lowStockItems); ?> product(s) require restocking.</div>
        <?php endif; ?>

        <section class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p class="stat-label">Products</p>
                    </div>
                    <div class="stat-icon blue">P</div>
                </div>
                <p class="stat-value"><?php echo $totalProducts; ?></p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p class="stat-label">Low Stock</p>
                    </div>
                    <div class="stat-icon amber">L</div>
                </div>
                <p class="stat-value"><?php echo $lowStockCount; ?></p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p class="stat-label">Incoming (30d)</p>
                    </div>
                    <div class="stat-icon green">I</div>
                </div>
                <p class="stat-value"><?php echo $incoming30; ?></p>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <p class="stat-label">Outgoing (30d)</p>
                    </div>
                    <div class="stat-icon red">O</div>
                </div>
                <p class="stat-value"><?php echo $outgoing30; ?></p>
            </div>
        </section>

        <section class="content-grid">
            <div class="card">
                <h2>Category Snapshot</h2>
                <div class="chart-bars">
                    <?php if (!empty($categoryBreakdown)): ?>
                        <?php
                            $maxChartValue = 1;
                            foreach ($categoryBreakdown as $row) {
                                if ((int)$row['net_stock'] > $maxChartValue) {
                                    $maxChartValue = (int)$row['net_stock'];
                                }
                            }
                        ?>
                        <?php foreach ($categoryBreakdown as $row): ?>
                            <?php $height = $maxChartValue > 0 ? ((int)$row['net_stock'] / $maxChartValue) * 100 : 10; ?>
                            <div class="bar-col">
                                <div class="bar" style="height: <?php echo max(12, $height); ?>%;"></div>
                                <div class="bar-label"><?php echo htmlspecialchars($row['category']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="muted">No category data yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>Notifications</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($lowStockItems)): ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo (int)$item['stock']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2">No low stock alerts.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card" style="margin-top:24px;">
            <h2>Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><span class="chip <?php echo strtolower($row['type']); ?>"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                <td><?php echo (int)$row['quantity']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
