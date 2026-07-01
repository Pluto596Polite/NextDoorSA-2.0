# NextdoorSA -2.0

## PHP deployment notes

- `database.php` lives in the project root so it is outside the public `web/` directory.
- `web/init_database.php` is CLI-only deployment code.
- `web/schema.sql` contains the schema imported during deployment.

Here is the hosting link: http://nextdoorsa.site.je/

Admin credentials: 
Username: admin@nextdoor.com
Password: admin123

Payment Gateway used: PayStack