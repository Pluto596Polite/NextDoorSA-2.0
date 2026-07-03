# NextDoorSA 2.0

A full-stack PHP marketplace web application built as an ITECA3-12 assignment project, extended into a more complete e-commerce style platform.

## Features

- User registration and login, with a separate admin authentication flow
- Product listings with filtering and sorting
- Shopping cart functionality
- Checkout integration with the PayStack payment gateway
- Order history for logged-in users
- Admin dashboard for managing the platform
- Secure password change flow for accounts

## Tech stack

- PHP (server-side logic)
- MySQL (database, imported via `web/schema.sql`)
- HTML/CSS/JavaScript on the front end

## Project structure

- `database.php` lives in the project root so it is outside the public `web/` directory.
- `web/init_database.php` is CLI-only deployment code used to set up the database.
- `web/schema.sql` contains the schema imported during deployment.

## Deployment

The project is configured to run against a MySQL database using the schema and init script above. Admin accounts are created through the standard registration/admin-provisioning flow rather than shipping with default credentials in source control.

## Security note

Admin credentials are not stored in this repository. If you are setting up your own instance, create an admin account through the app and store credentials securely (e.g. in environment variables or a secrets manager), and change any default passwords immediately after first login.
