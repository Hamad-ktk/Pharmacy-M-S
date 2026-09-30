<?php
$host = 'localhost';
$username = 'root';
$password = '';

try {
    // Connect without DB first to create it
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create DB
    $pdo->exec("CREATE DATABASE IF NOT EXISTS pharmacy_db");
    
    // Connect to specific DB
    $pdo->exec("USE pharmacy_db");
    
    // Read SQL file
    $sql = file_get_contents('pharmacy_db.sql');
    
    if($sql !== false) {
        // Execute the entire SQL script directly. PDO handles multiple statements natively.
        $pdo->exec($sql);
        
        echo "<h3 style='color:green;'>Success! Database tables created and data inserted successfully!</h3>";
        echo "<br><a href='index.php'>Click here to go to Login Page</a>";
    } else {
        echo "<h3 style='color:red;'>Error: pharmacy_db.sql file not found!</h3>";
    }
    
} catch(PDOException $e) {
    die("<h3 style='color:red;'>Database setup failed: " . htmlspecialchars($e->getMessage()) . "</h3>");
}
?>
