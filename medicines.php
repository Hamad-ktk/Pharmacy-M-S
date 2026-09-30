<?php
require_once 'config/database.php';
checkAuth();

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        $stmt = $pdo->prepare("INSERT INTO medicines (name, category_id, quantity, expiry_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $category_id, $quantity, $expiry_date]);
        header("Location: medicines.php?success=added");
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        
        $stmt = $pdo->prepare("UPDATE medicines SET name = ?, category_id = ?, quantity = ?, expiry_date = ? WHERE id = ?");
        $stmt->execute([$name, $category_id, $quantity, $expiry_date, $_POST['id']]);
        header("Location: medicines.php?success=updated");
        exit;
    } elseif ($_POST['action'] === 'delete') {
        try {
            // Soft delete
            $stmt = $pdo->prepare("UPDATE medicines SET is_active = 0 WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            header("Location: medicines.php?success=deleted");
            exit;
        } catch(PDOException $e) {
            die($e->getMessage());
        }
    }
}

try {
    $medicines = $pdo->query("SELECT m.*, c.name as category FROM medicines m LEFT JOIN categories c ON m.category_id = c.id WHERE m.is_active = 1")->fetchAll();
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        header("Location: install.php");
        exit;
    }
    die($e->getMessage());
}

require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Medicines Inventory</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus"></i> Add Medicine</button>
</div>

<?php if(isset($_GET['error']) && $_GET['error'] === 'cannot_delete'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Error!</strong> Cannot delete this medicine as it has already been included in past sales.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Success!</strong> Medicine added successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Success!</strong> Medicine deleted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Success!</strong> Medicine updated successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Expiry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($medicines as $med): 
                    $isShortExpiry = false;
                    $isExpired = false;
                    $expiryText = 'N/A';
                    $remainingText = '';
                    if (!empty($med['expiry_date'])) {
                        $expiryTime = strtotime($med['expiry_date']);
                        $now = time();
                        $sixMonthsLater = strtotime('+6 months');
                        
                        $expiryDateObj = new DateTime($med['expiry_date']);
                        $nowObj = new DateTime();
                        
                        $interval = $nowObj->diff($expiryDateObj);
                        if ($interval->y > 0) {
                            $remainingText .= $interval->y . 'yr ';
                        }
                        if ($interval->m > 0) {
                            $remainingText .= $interval->m . 'mo ';
                        }
                        if ($interval->d > 0 || $remainingText === '') {
                            $remainingText .= $interval->d . 'd';
                        }
                        $remainingText = trim($remainingText);
                        
                        if ($expiryTime < $now) {
                            $isExpired = true;
                        } elseif ($expiryTime <= $sixMonthsLater) {
                            $isShortExpiry = true;
                        }
                        $expiryText = date('M d, y', $expiryTime);
                    }
                ?>
                <tr>
                    <td><?= $med['id'] ?></td>
                    <td><?= htmlspecialchars($med['name']) ?></td>
                    <td><?= htmlspecialchars($med['category']) ?></td>
                    <td><?= $med['quantity'] ?></td>
                    <td>
                        <?php if ($isExpired): ?>
                            <span class="badge bg-danger">Exp (<?= $remainingText ?> ago)</span><br>
                            <small class="text-muted"><?= htmlspecialchars($expiryText) ?></small>
                        <?php elseif ($isShortExpiry): ?>
                            <span class="badge bg-warning text-dark">In <?= $remainingText ?></span><br>
                            <small class="text-muted"><?= htmlspecialchars($expiryText) ?></small>
                        <?php else: ?>
                            <?= htmlspecialchars($expiryText) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick='editMedicine(<?= json_encode($med) ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this medicine?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $med['id'] ?>">
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Add Medicine</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label>Medicine Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="0" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Medicine</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Edit Medicine</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
                <label>Medicine Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Category</label>
                <select name="category_id" id="edit_category_id" class="form-select" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label>Quantity</label>
                    <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                </div>
            </div>
            <div class="mb-3">
                <label>Expiry Date</label>
                <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Update Medicine</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
function editMedicine(med) {
    document.getElementById('edit_id').value = med.id;
    document.getElementById('edit_name').value = med.name;
    document.getElementById('edit_category_id').value = med.category_id;
    document.getElementById('edit_quantity').value = med.quantity;
    document.getElementById('edit_expiry_date').value = med.expiry_date || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
