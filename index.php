<?php
require_once 'config/database.php';

if(isset($_SESSION['pharma_user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Standard check, since our seeder uses plain text 'password' for testing:
        if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
            $_SESSION['pharma_user_id'] = $user['id'];
            $_SESSION['pharma_username'] = $user['username'];
            $_SESSION['pharma_role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        $error = "Database Error: Table 'users' not found. Please click <a href='install.php'>here</a> to install and setup the database first! Detailed error: " . $e->getMessage();
    }
}
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h2 class="text-primary"><i class="bi bi-capsule"></i></h2>
                    <h4>PharmaCare Login</h4>
                </div>
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Username (admin)</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password (password)</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
