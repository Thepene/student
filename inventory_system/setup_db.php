<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userRole = $_SESSION['role'] ?? 'staff';
if (!in_array($userRole, ['admin', 'manager'], true)) {
    header('Location: inventory.php');
    exit();
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$summary = $pdo->prepare("SELECT
    SUM(CASE WHEN type = 'IN' THEN quantity ELSE 0 END) AS incoming_total,
    SUM(CASE WHEN type = 'OUT' THEN quantity ELSE 0 END) AS outgoing_total,
    COUNT(*) AS total_transactions
FROM stock_transactions
WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
$summary->execute([$startDate, $endDate]);
$summaryRow = $summary->fetch(PDO::FETCH_ASSOC);

$transactions = $pdo->prepare("SELECT st.*, p.name AS product_name
    FROM stock_transactions st
    JOIN products p ON p.id = st.product_id
    WHERE st.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    ORDER BY st.created_at DESC");
$transactions->execute([$startDate, $endDate]);
$transactions = $transactions->fetchAll(PDO::FETCH_ASSOC);

$categoryTotals = $pdo->prepare("SELECT p.category, SUM(CASE WHEN st.type = 'IN' THEN st.quantity ELSE -st.quantity END) AS net_qty
    FROM products p
    LEFT JOIN stock_transactions st ON st.product_id = p.id
    WHERE st.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY p.category
    ORDER BY net_qty DESC");
$categoryTotals->execute([$startDate, $endDate]);
$categoryTotals = $categoryTotals->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #0f172a;
        }

        .topbar {
            background: rgba(255,255,255,0.9);
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            backdrop-filter: blur(12px);
        }

        .topbar-inner {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
        }

        .brand { font-size: 1.4rem; font-weight: 700; }
        .nav { display: flex; gap: 16px; align-items: center; }
        .nav a {
            color: #6b7280;
            text-decoration: none;
            font-weight: 700;
            padding: 10px 12px;
            border-radius: 10px;
        }
        .nav a.active, .nav a:hover { background: #eff6ff; color: #2563eb; }

        .page {
            max-width: 1300px;
            margin: 0 auto;
            padding: 32px 24px 60px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            padding: 22px;
            border: 1px solid #e5e7eb;
        }

        .stat-label { color: #6b7280; margin: 0 0 12px; }
        .stat-value { font-size: 2rem; font-weight: 700; margin: 0; }

        .filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            align-items: end;
        }

        input, button {
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            color: #6b7280;
            text-transform: uppercase;
            font-size: 12px;
        }

        .chip {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        .chip.in { background: #dcfce7; color: #166534; }
        .chip.out { background: #fee2e2; color: #991b1b; }

        .content-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
        }

        .bar-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }

        .bar-row {
            display: grid;
            grid-template-columns: 120px 1fr 40px;
            align-items: center;
            gap: 12px;
        }

        .bar-bg {
            background: #e5e7eb;
            border-radius: 999px;
            height: 12px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            border-radius: 999px;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">Inventory Pro</div>
            <nav class="nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="inventory.php">Inventory</a>
                <a href="reports.php" class="active">Reports</a>
            </nav>
        </div>
    </header>

    <main class="page">
        <h1>Inventory Reports</h1>

        <form class="filters" method="GET">
            <label>
                Start date
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </label>
            <label>
                End date
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </label>
            <button type="submit">Apply</button>
        </form>

        <section class="summary-grid">
            <div class="card">
                <p class="stat-label">Incoming</p>
                <p class="stat-value"><?php echo (int)($summaryRow['incoming_total'] ?? 0); ?></p>
            </div>
            <div class="card">
                <p class="stat-label">Outgoing</p>
                <p class="stat-value"><?php echo (int)($summaryRow['outgoing_total'] ?? 0); ?></p>
            </div>
            <div class="card">
                <p class="stat-label">Transactions</p>
                <p class="stat-value"><?php echo (int)($summaryRow['total_transactions'] ?? 0); ?></p>
            </div>
        </section>

        <section class="content-grid">
            <div class="card">
                <h2>Transactions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><span class="chip <?php echo strtolower($row['type']); ?>"><?php echo htmlspecialchars($row['type']); ?></span></td>
                                    <td><?php echo (int)$row['quantity']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">No data for selected date range.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2>Category Totals</h2>
                <?php $maxCategory = 1; foreach ($categoryTotals as $row) { if ((int)$row['net_qty'] > $maxCategory) $maxCategory = (int)$row['net_qty']; } ?>
                <div class="bar-list">
                    <?php if (!empty($categoryTotals)): ?>
                        <?php foreach ($categoryTotals as $row): ?>
                            <?php $fill = $maxCategory > 0 ? max(5, ((int)$row['net_qty'] / $maxCategory) * 100) : 5; ?>
                            <div class="bar-row">
                                <span><?php echo htmlspecialchars($row['category']); ?></span>
                                <div class="bar-bg"><div class="bar-fill" style="width: <?php echo $fill; ?>%"></div></div>
                                <strong><?php echo (int)$row['net_qty']; ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No category totals for this range.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
