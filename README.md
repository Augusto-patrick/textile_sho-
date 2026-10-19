# Textile Shop Management System

A PHP and MySQL web application for managing a small textile shop. It combines an in-store point-of-sale (POS) workflow with inventory management and a customer-facing online storefront.

## Features

- **Dashboard:** overview of shop activity, sales, low-stock products, customers, suppliers, and orders.
- **Inventory:** manage products, SKUs, categories, prices, stock quantities, reorder levels, and online listing status.
- **Suppliers and customers:** maintain supplier details and customer records.
- **Point of sale:** create in-store bills with line items, discounts, taxes, payment methods, and stock movements.
- **Online storefront:** customer registration/login, product browsing, persistent cart, checkout, and order history.
- **Order administration:** review online orders and update order/payment statuses.
- **Access and security helpers:** staff/customer authentication, password hashing, prepared SQL statements, and CSRF helpers are used in parts of the application.

## Technology stack

- PHP
- MySQL
- HTML, CSS, and JavaScript
- Apache (the project is structured for a local XAMPP setup)

## Project structure

```text
.
├── config/                 # Database connection
├── database/
│   └── schema.sql          # Database and initial seed data
├── includes/               # Authentication, CSRF, and shared layout
├── modules/
│   ├── admin/              # Staff/admin management
│   ├── billing/            # POS bills and online-order administration
│   ├── customers/          # Customer management
│   ├── inventory/          # Products, categories, and stock
│   └── suppliers/          # Supplier management
├── storefront/             # Customer-facing shop and checkout
├── assets/
│   ├── css/
│   └── img/
├── dashboard.php
├── login.php
└── logout.php
```

## Requirements

- XAMPP (or another PHP + Apache + MySQL/MariaDB environment)
- A PHP version supported by your installed XAMPP release
- PHP MySQLi support enabled
- A modern web browser

## Local setup (XAMPP)

1. **Place the project in the web root.** Copy/clone this repository into your XAMPP `htdocs` directory. Some pages currently use the absolute URL prefix `/textile_shop/`. For the simplest local setup, use the folder name `textile_shop`. If you keep a different folder name, update those paths consistently.
2. **Start the services.** Open the XAMPP Control Panel and start Apache and MySQL.
3. **Create the database.** Open phpMyAdmin at `http://localhost/phpmyadmin`, select the SQL import option, and import `database/schema.sql`. The script creates and selects the `textile_shop` database and inserts initial category/admin seed data.
4. **Check database configuration.** Review `config/db.php` and set the host, database name, username, and password for your local MySQL setup.
5. **Open the application.** Visit `http://localhost/textile_shop/` and follow the login/storefront links provided by the application.

> **Important:** The schema seeds an administrator account with a known development password. Change or replace that credential before using the application, and never expose a deployment with default credentials. The repository's default database configuration is intended for local development, not production.

## Database overview

The schema includes tables for:

- Staff users and storefront customers
- Suppliers, product categories, and products
- Stock movements
- POS bills and bill items
- Online orders and order items
- Persistent customer cart items

Foreign keys connect related records, and product stock movements provide a basic inventory audit trail.

## Security and deployment notes

This project should be treated as a development/educational application until it has been reviewed and tested for your intended environment.

Before deploying publicly:

- Replace seeded/default administrator credentials and use strong unique passwords.
- Use a dedicated MySQL account with limited privileges; do not use a privileged root account.
- Keep database credentials outside publicly served files where possible.
- Enable HTTPS and secure session cookies.
- Verify role-based authorization for every sensitive action.
- Ensure every state-changing endpoint validates CSRF tokens and accepts only the intended HTTP methods.
- Validate all submitted prices, quantities, identifiers, and payment/status values on the server.
- Test concurrent checkout and order cancellation so stock cannot be oversold or returned more than once.
- Disable public error display and log detailed errors privately.
- Back up the database and test restore procedures.

Do not use real customer information or process real payments until the application has been appropriately secured and tested. The payment-method fields in the schema do not, by themselves, indicate that a payment gateway is integrated.

## Troubleshooting

- **Database connection error:** confirm MySQL is running and that `config/db.php` matches your local database credentials.
- **Page or CSS links fail after renaming the folder:** search for hardcoded `/textile_shop/` paths and update them to match the installation path.
- **Missing tables:** re-import `database/schema.sql` into a fresh development database.
- **Access denied or login problems:** verify the account exists in the appropriate table and that its password is stored as a PHP password hash.

## Development status

This README documents the current structure and intended workflows. It is not a guarantee that every feature has been fully tested. Review the source code and run functional/security tests before using the system in a real shop.

## License

No license is specified in this repository yet. Add a `LICENSE` file if you intend to grant others permission to use, modify, or distribute the project.
