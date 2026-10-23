# Core PHP registration and login practice

A small learning project using Core PHP, MySQL, object-oriented MySQLi, and basic HTML/CSS. The connection uses `new mysqli()`. Login and registration use prepared statements with bound parameters.

This version uses PHP sessions for login. It has no custom cookie-handling code: PHP automatically manages its standard session ID cookie. Login and registration keep input values separate from SQL instructions through parameter binding to prevent SQL injection in their queries. This remains a beginner learning project.

## 1. Requirements

- PHP 8.2 or newer with the `mysqli` extension and `mysqlnd` driver enabled. The `get_result()` method used by login and registration requires mysqlnd; XAMPP includes it.
- MySQL, or the MariaDB bundled with XAMPP.
- A browser.

Run `php -v` and `php -m` to check your PHP installation. `php --ini` shows the active configuration file. If MySQLi is missing, enable `extension=mysqli` in that file and restart PHP/Apache. On this Windows machine, `C:\xampp\php\php.exe` already includes MySQLi.

## 2. Create the database

Start MySQL from the XAMPP control panel or your local database service.

In **phpMyAdmin**, select **Import**, choose `database.sql`, and click **Go**. The file creates the `php_learning` database and the `users` table. Apache must also be running to use phpMyAdmin.

Alternatively, open the MySQL client:

```sh
mysql -u root -p
```

Then run this inside the MySQL prompt, adjusting the path if necessary:

```sql
SOURCE D:/Aptech/Practice/php/db-managment/database.sql;
```

XAMPP's Windows client is usually `C:\xampp\mysql\bin\mysql.exe`. Use your actual credentials; press Enter at the password prompt if your local root account has no password.

| Column | Purpose |
| --- | --- |
| `id` | Automatically generated user ID |
| `name` | User's display name |
| `email` | Unique email address |
| `password` | Password hash |
| `created_at` | Registration time, recorded automatically |

The SQL uses `IF NOT EXISTS`, so importing it again does not erase existing users or upgrade an existing table with a different structure.

## 3. Configure database credentials

Edit the variables at the top of `db.php`:

```php
$servername = 'localhost';
$username = 'root';
$dbPassword = '';
$dbname = 'php_learning';
```

These are common local XAMPP defaults. Set the password to match your installation, and do not publish real credentials. If MySQL uses a different port, pass it as the fifth argument to `new mysqli()`.

`new mysqli($servername, $username, $dbPassword, $dbname)` creates a connection object and selects the existing database. `$dbPassword` is named separately so including this file cannot overwrite the user's submitted `$password`. The object is stored in `$conn`, and `$conn->prepare()` creates prepared SQL statements. `$conn->set_charset('utf8mb4')` enables Unicode text. PHP 8.2 throws database exceptions by default, so a simple `try/catch` handles connection failures without exposing database details. Import `database.sql` first; normal page requests do not create the database.

## 4. Run locally

Open a terminal in this project folder and run:

```sh
php -S localhost:8000
```

If the PHP on your PATH lacks MySQLi, use XAMPP's PHP from PowerShell:

```powershell
& 'C:\xampp\php\php.exe' -S localhost:8000
```

Open `http://localhost:8000`. MySQL must be running. Apache is not needed when using PHP's built-in server. Press Ctrl+C to stop the server.

Alternatively, copy the folder into XAMPP's `htdocs`, start Apache and MySQL, and open `http://localhost/db-managment/`, using your actual folder name.

Keep PHP's `session.auto_start` setting disabled (its normal default). The shared `session.php` file starts the session explicitly before any output.

## 5. Registration → login → dashboard → logout

1. **Register:** `register.php` validates the CSRF token, required fields, email format, input lengths, and password confirmation. A prepared `SELECT` with a bound email checks whether the email exists. `password_hash()` creates a bcrypt hash, and a prepared `INSERT` binds the name, email, and hash to save the account. Registration saves a one-time success message in the session and redirects to `login.php`.
2. **Log in:** `login.php` validates the CSRF token and fetches a matching user with a prepared `SELECT`, binding the email with `bind_param()`. `password_verify()` compares the submitted password with the stored hash. On success, PHP regenerates the session ID, stores the user ID and name in `$_SESSION`, refreshes the CSRF token, and redirects to `dashboard.php`.
3. **Dashboard:** the dashboard checks `$_SESSION['user_id']` before displaying the escaped name. Without a login session, it redirects to login. Refreshing the dashboard or opening it in another tab in the same browser keeps the user logged in while the session remains active. Logged-in users visiting login or registration are redirected to the dashboard.
4. **Log out:** the button posts to `logout.php`. After checking the CSRF token, PHP clears `$_SESSION`, calls `session_destroy()` to delete the server-side session, and redirects to login. The old session can no longer authorize dashboard access.

Sessions store login data on the server between requests. `session_start()` resumes or creates a session, and `session_regenerate_id(true)` replaces its ID after successful login. Only the user ID and name are saved as login data, never the password or hash.

PHP uses its automatic session ID cookie to identify the browser. There are no manual `setcookie()` calls or custom cookie settings. Logout destroys the server-side session; it does not manually delete the browser's session ID cookie. Strict session mode rejects an ID whose server-side session no longer exists. There is no separate remember-me cookie.

Registration, login, and logout each use a hidden CSRF token stored in the session. `hash_equals()` checks that the submitted token matches. This protects forms against cross-site submissions but does not prevent SQL injection.

Password hashing remains enabled. Hashing is one-way: the password is not decrypted when logging in. Bcrypt stores its salt and algorithm settings in the hash, and `password_verify()` uses those settings to check the submitted password.

Passwords must contain 8–72 bytes; bcrypt only uses the first 72 bytes, so longer passwords are rejected. Names are limited to 100 bytes and emails to 254 bytes. Some Unicode characters use multiple bytes. Passwords are not trimmed or redisplayed. Emails are trimmed and lowercased consistently.

## 6. How parameter binding works

```php
$stmt = $conn->prepare('SELECT id, name, password FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
```

- `prepare()` defines the SQL structure. The `?` marks where an email value belongs.
- `bind_param('s', $email)` binds one string variable to the placeholder. Values are supplied separately from the SQL instructions.
- `execute()` runs the prepared statement using the bound value.
- `get_result()` returns the rows from the `SELECT` as a result object.
- `$result->fetch_assoc()` reads one row as an associative array, or returns `null` if no row matches.
- `$stmt->close()` releases the statement after reading its result; it does not close the database connection.

The database treats the bound email as data, even if it contains quotes or SQL-looking text. It cannot change the query's SQL structure. The password is checked separately with `password_verify()`, not compared as plain text in SQL.

Registration uses the same approach for its email check. To create an account, it binds three strings:

```php
$stmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $passwordHash);
$stmt->execute();
$stmt->close();
```

Each `s` represents a string, in placeholder order. Bind the password hash, never the plain-text password. An `INSERT` does not return rows, so it does not need `get_result()`. Names containing apostrophes, such as `O'Connor`, are passed as data without breaking the SQL. HTML escaping with `htmlspecialchars()` is still a separate concern when displaying input in a page.

## 7. Files and CRUD

| File | Purpose |
| --- | --- |
| `database.sql` | Create the database and users table |
| `db.php` | Open the MySQLi connection |
| `session.php` | Start sessions and share CSRF validation |
| `index.php` | Redirect based on login state |
| `register.php` | Validate input, check email and insert user with bound parameters, hash password |
| `login.php` | Bind email in a prepared query, verify password, save login in the session |
| `dashboard.php` | Check the login session and display the name |
| `logout.php` | Destroy the session and return to login |
| `style.css` | Style the forms |

CRUD means Create, Read, Update, and Delete. Registration demonstrates **Create** (`INSERT`), and the email checks and login demonstrate **Read** (`SELECT`). Profile updates and account deletion are outside this project's scope. Logout does not delete an account.

## 8. Try the flow

- Register with a simple name, log in, see your name, and use Log out.
- Open `dashboard.php` without logging in; it should redirect to login.
- After a successful login, refresh the dashboard or open it in another tab; you should remain logged in.
- Visit `login.php` while logged in; it should redirect to the dashboard.
- Log out, then revisit the dashboard; it should redirect to login.
- Try a duplicate email, mismatched passwords, missing fields, and an invalid email.
- Try a wrong password or an unknown email; both show the same login error.
- Use `<b>Alice</b>` as a name; it should be displayed literally rather than as HTML.
- Inspect the password column in phpMyAdmin; it should contain a hash.
- Change or remove a form's hidden CSRF token; the submission should fail.
- Check the browser's cookies; PHP manages the standard session ID cookie automatically.

This deliberately simplified version is not suitable for deployment with real user data.
