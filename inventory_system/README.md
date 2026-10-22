# Inventory System

This project contains a PHP inventory management system using HTML, PHP, and XAMPP/MySQL.

Files:
- `setup_db.php` — creates the tables
- `config.php` — database connection
- `register.php` — registration page
- `login.php` — login page
- `dashboard.php` — dashboard
- `inventory.php` — products and stock operations

How to run:
1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/inventory_system/setup_db.php` in the browser.
3. Create the database tables.
4. Open `http://localhost/inventory_system/register.php` to create a user.
5. Log in and access the inventory system.

Features:
- Add product items
- Record incoming stock
- Record outgoing stock
- Dynamic live inventory calculation
- Role-based login: admin, staff, manager
