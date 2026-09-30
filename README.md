# Pharmacy Management System (PHP + MySQL Version)

This is the traditional PHP and MySQL setup for your Pharmacy Management System that you can easily run using XAMPP.

## Installation Instructions (for XAMPP)

1. Open your XAMPP installation folder (usually `C:\xampp\`).
2. Go to the `htdocs` folder.
3. Move this **entire folder** (`pharma-php-mysql`) into the `htdocs` folder.
4. Start **Apache** and **MySQL** from your XAMPP Control Panel.
5. In your browser, go to: `http://localhost/phpMyAdmin`
6. Click **Import** at the top.
7. Choose the `pharmacy_db.sql` file located inside this folder and click **Import** / **Go**. This will create your database and tables automatically.
8. Now, open a new tab and go to: `http://localhost/pharma-php-mysql/`

## Login Credentials
- **Username:** `admin`
- **Password:** `password`

## Note
This is a standard PHP application using PDO for secure, prepared SQL statements, preventing SQL injection, and uses standard Bootstrap 5 via CDN for styling.
