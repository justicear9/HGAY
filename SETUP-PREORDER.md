# Preorder & Admin Setup

## Database

1. Create the database and tables (XAMPP: start MySQL, then use phpMyAdmin or CLI):
   ```bash
   mysql -u root -p < schema.sql
   ```
   Or in phpMyAdmin: create database `hgay`, then run the contents of `schema.sql`.

2. Edit `config/database.php` if needed (default: host `localhost`, db `hgay`, user `root`, password ``).

## Admin

1. **First time:** Open `admin/setup.php` in the browser and create the first admin user (username + password).
2. **Log in:** Use `admin/login.php` to sign in and view orders at `admin/index.php`.
3. **Security:** Use HTTPS in production. Change default credentials; consider restricting `admin/setup.php` after the first user is created (e.g. delete or protect by IP).

## Paystack

- Secret key is in `paystack_config.php` (server-side only).
- Public key is in `js/main.js`. Use test keys for development, live for production.

## Flow

- Customer fills step 1 (name, email, phone with country, quantity) → step 2 (delivery country, region, address, postcode if applicable) → Pay with Paystack.
- Backend creates a **pending** order, then Paystack popup opens. After payment, `verify.php` verifies with Paystack and marks the order **paid**.
- Admin sees all orders with user and delivery info in `admin/index.php`.
