<?php
require_once 'config/database.php';
checkAuth();

// Handle Delete/Clear
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header("Location: reports.php?success=deleted");
        exit;
    } elseif ($_POST['action'] === 'clear_all') {
        $pdo->exec("DELETE FROM sales");
        header("Location: reports.php?success=cleared");
        exit;
    }
}

try {
    // Fetch sales joined with items
    $sales = $pdo->query("
        SELECT s.id, s.sale_date, s.total_amount, u.username as seller,
        (
            SELECT GROUP_CONCAT(CONCAT(m.name, ' (x', si.quantity, ')') SEPARATOR ', ')
            FROM sale_items si
            JOIN medicines m ON si.medicine_id = m.id
            WHERE si.sale_id = s.id
        ) as items
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        ORDER BY s.sale_date DESC
    ")->fetchAll();
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        header("Location: install.php");
        exit;
    }
    die($e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Sales Reports</h2>
    <div>
        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to clear ALL sales reports? This cannot be undone.');">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" class="btn btn-danger me-2" <?= empty($sales) ? 'disabled' : '' ?>><i class="bi bi-trash"></i> Clear All</button>
        </form>
        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print Report</button>
    </div>
</div>

<?php if(isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Success!</strong> Report deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['success']) && $_GET['success'] === 'cleared'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Success!</strong> All reports cleared successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Invoice ID</th>
                        <th>Date & Time</th>
                        <th>Cashier</th>
                        <th>Items Purchased</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end print-hide">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($sales as $sale): ?>
                    <tr>
                        <td><strong>#INV-<?= str_pad($sale['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('M d, Y h:i A', strtotime($sale['sale_date'])) ?></td>
                        <td><?= htmlspecialchars($sale['seller'] ?? 'N/A') ?></td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars($sale['items']) ?></small>
                        </td>
                        <td class="text-end fw-bold text-success">
                            Rs. <?= number_format($sale['total_amount'], 2) ?>
                        </td>
                        <td class="text-end print-hide">
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this invoice?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $sale['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($sales)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No sales records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .navbar, .btn, .print-hide {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
