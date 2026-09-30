<?php
require_once 'config/database.php';
checkAuth();

try {
    // Fetch statistics
    $totalMedicines = $pdo->query("SELECT COUNT(*) FROM medicines WHERE is_active = 1")->fetchColumn();
    $lowStock = $pdo->query("SELECT COUNT(*) FROM medicines WHERE quantity < 30 AND is_active = 1")->fetchColumn();
    $totalSales = $pdo->query("SELECT SUM(total_amount) FROM sales")->fetchColumn() ?? 0;
    $todaySales = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE()")->fetchColumn() ?? 0;

    $expiringMedicines = $pdo->query("SELECT * FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) AND is_active = 1 ORDER BY expiry_date ASC")->fetchAll();

    $search = $_GET['search'] ?? '';
    
    if ($search) {
        $stmt = $pdo->prepare("SELECT m.*, c.name as category FROM medicines m LEFT JOIN categories c ON m.category_id = c.id WHERE m.is_active = 1 AND m.name LIKE ? ORDER BY m.id DESC");
        $stmt->execute(['%' . $search . '%']);
        $recentMedicines = $stmt->fetchAll();
    } else {
        $recentMedicines = $pdo->query("SELECT m.*, c.name as category FROM medicines m LEFT JOIN categories c ON m.category_id = c.id WHERE m.is_active = 1 ORDER BY m.id DESC LIMIT 5")->fetchAll();
    }
} catch (PDOException $e) {
    // If tables are missing, redirect to install.php to set them up
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        header("Location: install.php");
        exit;
    } else {
        die("Database Error: " . $e->getMessage());
    }
}

require_once 'includes/header.php';
?>

<h2 class="mb-4">Dashboard</h2>

<?php if (count($expiringMedicines) > 0): ?>
    <div class="alert alert-warning shadow-sm">
        <h5 class="alert-heading text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Short Expiry Warning!</h5>
        <p class="mb-0">The following medicines are expiring within 6 months or have already expired:</p>
        <ul class="mb-0 mt-2">
            <?php foreach($expiringMedicines as $expMed): 
                $expiryDate = new DateTime($expMed['expiry_date']);
                $now = new DateTime();
                $isExpired = $expiryDate < $now;
                
                $interval = $now->diff($expiryDate);
                $remainingText = '';
                if ($interval->y > 0) {
                    $remainingText .= $interval->y . ' year' . ($interval->y > 1 ? 's ' : ' ');
                }
                if ($interval->m > 0) {
                    $remainingText .= $interval->m . ' month' . ($interval->m > 1 ? 's ' : ' ');
                }
                if ($interval->d > 0 || $remainingText === '') {
                    $remainingText .= $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                }
                $remainingText = trim($remainingText);
            ?>
                <li>
                    <strong><?= htmlspecialchars($expMed['name']) ?></strong> 
                    - <?= $isExpired ? '<span class="text-danger fw-bold">EXPIRED ' . $remainingText . ' ago</span>' : 'Expiring in <span class="fw-bold">' . $remainingText . '</span> (' . date('M d, Y', strtotime($expMed['expiry_date'])) . ')' ?>
                    <a href="medicines.php" class="text-decoration-none ms-2">Manage</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h5>Total Medicines</h5>
                <h2><?= $totalMedicines ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <h5>Low Stock Alerts</h5>
                <h2><?= $lowStock ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <h5>Today's Sales</h5>
                <h2>Rs. <?= number_format($todaySales, 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white shadow-sm">
            <div class="card-body">
                <h5>Total Revenue</h5>
                <h2>Rs. <?= number_format($totalSales, 2) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= $search ? 'Search Results' : 'Recently Added Medicines' ?></h5>
        <form method="GET" class="d-flex" style="width: 300px;">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search medicines..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                <?php if($search): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentMedicines as $med): ?>
                <tr>
                    <td><?= htmlspecialchars($med['name']) ?></td>
                    <td><?= htmlspecialchars($med['category'] ?? 'N/A') ?></td>
                    <td><span class="<?= $med['quantity'] < 30 ? 'text-danger fw-bold' : '' ?>"><?= $med['quantity'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($recentMedicines)): ?>
                    <tr><td colspan="3" class="text-center text-muted">No medicines found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
