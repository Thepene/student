<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$rows = $pdo->query("SELECT p.name, p.sku, p.category, s.name AS supplier_name, p.price,
    COALESCE(SUM(CASE WHEN st.type = 'IN' THEN st.quantity ELSE -st.quantity END), 0) AS stock
    FROM products p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN stock_transactions st ON st.product_id = p.id
    GROUP BY p.id, p.name, p.sku, p.category, s.name, p.price
    ORDER BY p.name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory PDF Export</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; margin: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f1f5f9; }
        h1 { margin-bottom: 0; }
        .meta { color: #64748b; margin-bottom: 18px; }
        @media print {
            body { margin: 0; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <h1>Inventory Report</h1>
    <div class="meta">Generated on <?php echo date('Y-m-d H:i:s'); ?></div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['sku']); ?></td>
                        <td><?php echo htmlspecialchars($row['category'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['supplier_name'] ?: '-'); ?></td>
                        <td>$<?php echo number_format((float)$row['price'], 2); ?></td>
                        <td><?php echo (int)$row['stock']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No products available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <button onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>
</html>
