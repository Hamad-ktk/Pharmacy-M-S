<?php
require_once 'config/database.php';
checkAuth();

// Make sure cart exists in session
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $med_id = (int)$_POST['medicine_id'];
    $price = (float)($_POST['price'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
    $stmt->execute([$med_id]);
    $med = $stmt->fetch();

    if($med && $med['quantity'] >= $qty && $price >= 0) {
        $cart_key = $med_id . '_' . $price;
        if(isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'id' => $med_id,
                'name' => $med['name'],
                'price' => $price,
                'qty' => $qty,
                'max' => $med['quantity']
            ];
        }
    }
    header("Location: billing.php");
    exit;
}

// Handle Clear/Checkout
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'clear') {
        $_SESSION['cart'] = [];
    } elseif($_POST['action'] === 'checkout' && !empty($_SESSION['cart'])) {
        $total = 0;
        foreach($_SESSION['cart'] as $item) {
             $total += ($item['price'] * $item['qty']);
        }
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO sales (total_amount, user_id) VALUES (?, ?)");
            $stmt->execute([$total, $_SESSION['pharma_user_id']]);
            $sale_id = $pdo->lastInsertId();

            $item_stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)");
            $update_med = $pdo->prepare("UPDATE medicines SET quantity = quantity - ? WHERE id = ?");

            foreach($_SESSION['cart'] as $cart_key => $item) {
                $item_stmt->execute([$sale_id, $item['id'], $item['qty'], $item['price']]);
                $update_med->execute([$item['qty'], $item['id']]);
            }
            
            $pdo->commit();
            $_SESSION['cart'] = [];
            $success = "Sale completed successfully!";
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Failed to complete sale: " . $e->getMessage();
        }
    }
}

try {
    $medicines = $pdo->query("SELECT * FROM medicines WHERE quantity > 0 AND is_active = 1")->fetchAll();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        header("Location: install.php");
        exit;
    }
    die($e->getMessage());
}
$cart_total = 0;
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-7">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Available Medicines</h4>
            <div class="input-group" style="width: 250px;">
                <input type="text" id="medicineSearch" class="form-control" placeholder="Search...">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
            </div>
        </div>
        
        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <div class="row g-3 mt-2" id="medicineList">
            <?php foreach($medicines as $med): ?>
            <div class="col-md-6 medicine-item" data-name="<?= htmlspecialchars($med['name']) ?>">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($med['name']) ?></h6>
                            <div class="text-muted small">Stock: <?= $med['quantity'] ?></div>
                        </div>
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="add_to_cart" value="1">
                            <input type="hidden" name="medicine_id" value="<?= $med['id'] ?>">
                            <input type="number" name="price" step="0.01" class="form-control form-control-sm me-2" placeholder="Price" style="width: 80px;" required>
                            <input type="number" name="qty" class="form-control form-control-sm me-2" value="1" min="1" max="<?= $med['quantity'] ?>" style="width: 60px;" required>
                            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-cart-plus"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Current Order</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if(empty($_SESSION['cart'])): ?>
                        <li class="list-group-item text-center text-muted py-4">Cart is empty</li>
                    <?php else: ?>
                        <?php foreach($_SESSION['cart'] as $id => $item): 
                            $subtotal = $item['price'] * $item['qty'];
                            $cart_total += $subtotal;
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                                <small class="text-muted"><?= $item['qty'] ?> x Rs. <?= number_format($item['price'], 2) ?></small>
                            </div>
                            <span>Rs. <?= number_format($subtotal, 2) ?></span>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between mb-3">
                    <h5>Total</h5>
                    <h5 class="text-primary">Rs. <?= number_format($cart_total, 2) ?></h5>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <form method="POST">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger w-100" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Clear</button>
                        </form>
                    </div>
                    <div class="col-6">
                        <form method="POST">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-success w-100" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>Checkout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('medicineSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.medicine-item');
    
    items.forEach(item => {
        const name = item.getAttribute('data-name').toLowerCase();
        if(name.includes(term)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
