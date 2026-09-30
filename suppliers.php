<?php
require_once 'config/database.php';
checkAuth();

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact, email, address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['contact'], $_POST['email'], $_POST['address']]);
    } elseif ($_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    header("Location: suppliers.php");
    exit;
}

try {
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC")->fetchAll();
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
    <h2>Suppliers Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus"></i> Add Supplier</button>
</div>

<div class="row g-4">
    <?php foreach($suppliers as $sup): ?>
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-primary"><i class="bi bi-building"></i> <?= htmlspecialchars($sup['name']) ?></h5>
                <p class="card-text mb-1"><strong><i class="bi bi-telephone"></i> Contact:</strong> <?= htmlspecialchars($sup['contact']) ?></p>
                <p class="card-text mb-1"><strong><i class="bi bi-envelope"></i> Email:</strong> <?= htmlspecialchars($sup['email']) ?></p>
                <p class="card-text mb-3"><strong><i class="bi bi-geo-alt"></i> Address:</strong> <?= htmlspecialchars($sup['address']) ?></p>
                
                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $sup['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Delete Supplier</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($suppliers)): ?>
        <div class="col-12 text-center text-muted mt-5">
            <i class="bi bi-inbox fs-1"></i>
            <p>No suppliers found. Click "Add Supplier" to create one.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Add Supplier</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label>Supplier Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contact Number</label>
                <input type="text" name="contact" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Physical Address</label>
                <textarea name="address" class="form-control" rows="3" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Supplier</button>
          </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
