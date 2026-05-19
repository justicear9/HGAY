# How Ghanaian Are You?

Preorder landing page for the card game — Paystack payments, multi-step checkout, and admin orders.

## Setup

1. **Database** — Create MySQL database `hgay` and run `schema.sql`.
2. **Config** — Copy example files and fill in credentials:
   - `paystack_config.example.php` → `paystack_config.php`
   - `config/database.example.php` → `config/database.php`
   - `config/mail.example.php` → `config/mail.php`
3. **Paystack public key** — Set in `js/main.js` (`PAYSTACK_PUBLIC_KEY`).
4. **Admin** — Visit `/admin/setup.php` once to create the first admin user, then `/admin/login.php`.

See `SETUP-PREORDER.md` for more detail.

## Stack

- Static front end (HTML, CSS, JS) + PHP (XAMPP)
- Paystack Inline for payments
- MySQL for orders and admin users
