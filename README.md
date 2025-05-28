# Export Pricing Calculator & Admin Panel

## Description

This project I have created for my company, it is a beta version and a web-based application designed to calculate export pricing based on various cost factors and manage these pricing entries through an admin panel. It allows users (likely admin staff) to input raw cost data, and the system calculates different price points, including FOB, net price, and final USD price per KG and total. It also provides a user-facing page to view the calculated pricing details based on selected filters.

## Technologies Used

*   **Backend:** PHP (procedural style)
*   **Database:** MySQL (interacted via PDO)
*   **Frontend:** HTML, CSS, JavaScript (vanilla)
*   **Data Export:** CSV
*   **Excel Handling (Optional):** `PhpSpreadsheet` library (for `import_excel.php`)

## Key Features

*   **Admin Panel:**
    *   Secure login for administrators.
    *   CRUD (Create, Read, Update, Delete) operations for export pricing entries.
    *   Dynamic form for adding new entries with various cost components (Ex Mill Price, Freight, Commission, etc.).
    *   Ability to add new product types and specifications directly through the form.
    *   Display of existing entries with calculated pricing details.
    *   Export all pricing entries to a CSV file.
    *   Functionality to change the admin password.
*   **Pricing Calculation:**
    *   Automatic calculation of commission, local freight per kg, L/C costs, margin, FOB value, drawback, Focus Market Scheme benefits, net price (INR), ocean freight (INR/kg), final price (INR/kg), final price (USD/kg), and final price (USD total).
*   **User View (`index.php`):**
    *   Filterable view of export pricing details based on date, product type, and specification.
    *   Displays the most recent or a specifically selected pricing entry.
*   **Database Interaction:**
    *   Uses PDO for database connections and queries.
    *   Stores pricing inputs and calculated values.
*   **Configuration:**
    *   Centralized configuration for database credentials and admin details.

## Setup and Installation

1.  **Prerequisites:**
    *   A web server (e.g., Apache, Nginx) with PHP installed (version 7.x or higher recommended).
    *   MySQL database server.
    *   Access to create a database and user in MySQL, or use existing credentials.

2.  **Clone the Repository (or copy files):**
    Place the project files in your web server's document root (e.g., `htdocs` for Apache, `www` or `html` for Nginx).

3.  **Database Setup:**
    *   Create a MySQL database (e.g., `cost1` as suggested in `includes/config.php`).
    *   **Schema:** You might need to create the `export_entries` table and `admin_settings` table.
        *   The `scheme.sql` file (if present and up-to-date) can be used to set up the necessary table structure.
        *   Alternatively, the table structure can be inferred from `saveExportEntry()` in `includes/functions.php` and `admin_settings` from `verifyAdminPassword()` in `includes/config.php`.
        *   Key table: `export_entries` (stores all pricing data).
        *   Key table: `admin_settings` (can store hashed admin password).

4.  **Configuration (`includes/config.php`):**
    *   Open `includes/config.php` and update the following constants:
        *   `DB_HOST`: Your database host (e.g., 'localhost').
        *   `DB_USER`: Your database username.
        *   `DB_PASS`: Your database password.
        *   `DB_NAME`: The name of the database you created.
        *   `DB_PORT`: The port your MySQL server is running on (e.g., '3306' or '3307').
        *   `ADMIN_USERNAME`: The desired username for the admin panel.
        *   `ADMIN_PASSWORD`: The initial plain-text password for the admin.
            *   **Note:** The system attempts to use a hashed password from the `admin_settings` table first. If not found, it falls back to this `ADMIN_PASSWORD` constant. It's recommended to use the "Change Admin Password" feature in the admin panel after the first login to store a hashed password in the database.

5.  **Permissions (if applicable):**
    *   Ensure your web server has write permissions for error logging if PHP is configured to log to a file.

6.  **Composer (Optional - for Excel Import):**
    *   If you plan to use `import_excel.php`, you'll need the `PhpSpreadsheet` library.
    *   Navigate to the project root in your terminal and run `composer require phpoffice/phpspreadsheet` if you have Composer installed. This will create a `vendor` directory. Ensure `require 'vendor/autoload.php';` is present in `import_excel.php`.

7.  **Access the Application:**
    *   Open your web browser and navigate to `admin.php` (e.g., `http://localhost/your_project_folder/admin.php`).
    *   Navigate to `index.php` to see the user-facing view.

## File Structure Overview

```
.
├── admin.php               # Main admin panel for managing entries
├── index.php               # User-facing page to view pricing details
├── includes/
│   ├── config.php          # Database credentials, admin settings, core functions (getDB, redirect, etc.)
│   └── functions.php       # Business logic, pricing calculations, data fetching, CSV export
├── edit.php                # Page/logic for editing an existing entry
├── delete.php              # Logic for deleting an entry
├── db_test.php             # (Likely a utility for testing DB connection)
├── import_excel.php        # (If used) Script to import data from an Excel file
├── scheme.sql              # (If present) SQL script to set up database tables
├── css/                    # (If you decide to move CSS to separate files)
└── js/                     # (If you decide to move JS to separate files)
└── README.md               # This file
```

## Usage

1.  **Admin Panel (`admin.php`):**
    *   Navigate to `admin.php` in your browser.
    *   Log in using the `ADMIN_USERNAME` and `ADMIN_PASSWORD` configured in `includes/config.php` (or the updated password if changed via the panel).
    *   **Create New Entry:** Fill the form and save. New product types/specifications can be added by selecting "+ Add new..." and filling the new name.
    *   **View Entries:** Existing entries are listed at the bottom.
    *   **Edit/Delete Entries:** Use the "Edit" or "Delete" buttons next to each entry.
    *   **Export to CSV:** Click the "Export to CSV" button.
    *   **Change Password:** Use the "Change Admin Password" form.

2.  **User View (`index.php`):**
    *   Navigate to `index.php`.
    *   Use the filters (Date, Product Type, Specification) and click "Search" to view specific pricing data.
    *   If no specific date is found, it usually shows the latest entry for the selected type/specification.

## Troubleshooting

*   **Blank Page / "Too Many Redirects":**
    *   Ensure `session_start()` is at the very beginning of `admin.php` and other session-dependent files.
    *   Check for any accidental output (even whitespace) before `header()` calls (often in `admin.php` or included files).
    *   The redirect loop in `admin.php` (if `!isAdmin() redirect('admin.php');`) has been addressed, but double-check if similar logic exists elsewhere.
*   **Database Connection Failed:**
    *   Verify credentials in `includes/config.php` are correct.
    *   Ensure your MySQL server is running and accessible.
    *   Check PHP error logs for detailed PDO connection errors. The `getDB()` function in `config.php` uses `error_log()` and `die()`.
*   **Login Issues:**
    *   Confirm `ADMIN_USERNAME` in `config.php`.
    *   If using the database password, ensure the `admin_settings` table exists and has a correctly hashed password, or that the fallback `ADMIN_PASSWORD` in `config.php` is correct.
*   **"Headers already sent" warnings:**
    *   Caused by outputting HTML, echo statements, or even whitespace before `header()` (used in `redirect()`) or `session_start()` is called. Check server error logs.
