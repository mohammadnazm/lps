# One-time setup notes

## Multi-school bootstrap (schools / school_grades tables + first GeneralAdmin)

`setup_multischool.php` has been removed from this build for security — it had
no login check at all, so anyone who found the URL could re-run it and it
would (re)create a `generaladmin` account with a hardcoded password. Run the
equivalent manually instead, once, via phpMyAdmin or the mysql CLI:

```sql
-- 1. Make sure school_scope exists on the legacy tables (skip any that already have it)
ALTER TABLE students        ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE users            ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE teachers         ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE lozanstaff       ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE givenclass       ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE staffclasscon    ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE subjects         ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE mark_con         ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';
ALTER TABLE acc_con          ADD COLUMN school_scope VARCHAR(30) NOT NULL DEFAULT 'LOZAN';

-- 2. Schools + grades tables — NOT created automatically; run this if they don't
--    already exist (they almost certainly already do on your live database, since
--    the admin dashboard, Add Student, etc. all depend on them and are already
--    working — this is here only for a genuinely fresh database)
CREATE TABLE IF NOT EXISTS schools (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_code VARCHAR(30) NOT NULL,
    school_name VARCHAR(150) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
    UNIQUE KEY uq_school_code(school_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS school_grades (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    school_id INT UNSIGNED NOT NULL,
    grade_name VARCHAR(50) NOT NULL,
    grade_order INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY(id),
    UNIQUE KEY uq_school_grade(school_id, grade_name),
    CONSTRAINT fk_school_grade_school FOREIGN KEY(school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO schools (school_code, school_name, status) VALUES
    ('LOZAN', 'LOZAN', 'active'),
    ('MAHWI', 'MAHWI', 'active')
ON DUPLICATE KEY UPDATE school_name = VALUES(school_name);
```

## Creating the first GeneralAdmin account

Never insert a plaintext password directly — this build hashes passwords with
bcrypt. Generate a hash locally (PHP CLI, or any online bcrypt generator you
trust) and insert that instead:

```php
<?php echo password_hash('choose-a-strong-password-here', PASSWORD_DEFAULT);
```

Then, with that hash:

```sql
INSERT INTO users (u_name, u_pass, u_role, u_access, school_scope)
VALUES ('generaladmin', '<paste the bcrypt hash here>', 'GeneralAdmin', 'ALL', 'ALL');
```

Log in once with that account and change the password immediately from the
in-app profile settings if one is available, or by generating and pasting a
new hash the same way.

## Removed for security before hosting

Two developer-only scripts were pulled out of this build entirely — both accepted
requests with **no login check**, which is not safe to leave on a live, public server:

- `setup_multischool.php` — see the bootstrap steps above for the manual equivalent.
- `migration_merge.php` — a one-time tool for merging two older separate databases
  (`lozan_tomar` + `mahwi_lozan`) into this one. If you still need it, keep your own
  copy of it **off the live server** (run it locally against a local copy of the
  databases, or temporarily upload → run → delete it immediately, over a connection
  only you can reach) — never leave it sitting on a public host.

## Before you upload this to a live host

- **Most likely first error:** `Database connection failed. Please check config.php.`
  This means the database credentials in `config.php` (`DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME`)
  don't match a real database on your host — the shipped defaults (`localhost` /
  `root` / a placeholder password) are for local development only and will not
  work on shared hosting. Set them via environment variables
  (`LOZAN_DB_HOST`, `LOZAN_DB_USER`, `LOZAN_DB_PASS`, `LOZAN_DB_NAME`) if your
  host supports that, otherwise edit the fallback values directly in `config.php`,
  to the database name/user/password your host gave you.
- Confirm `logs/`, `config.php`, and any `.sql`/`.md` file are not reachable from
  a browser (already blocked by the included `.htaccess` files — just don't
  remove them). Also confirm `st_image/`, `id_data/`, `teachers_img/`,
  `school_logos/`, and `images/` each still have their own `.htaccess` — these
  block any uploaded file in those folders from ever being executed as a script,
  even though uploads are now validated before being saved.
- If `activity_log` doesn't start recording activity after your first login,
  run `database/migration_activity_log.sql` once — see the Activity Log
  page's built-in "Run Diagnostic Test" button for details. Same idea for
  `database/migration_login_attempts.sql` if login lockouts don't seem to
  kick in after repeated failed logins (lower priority — login itself
  still works fine either way).
- Make sure `logs/` and the five upload folders above are writable by PHP
  (typical shared-hosting permissions of 755, owned by the same user PHP
  runs as, are usually already correct — only worth checking if uploads or
  logging start failing).
- **PHP version and the Archive (Import/Export Excel) page:** the bundled
  spreadsheet library (phpoffice/phpspreadsheet 5.6, in `vendor/`) officially
  requires PHP 8.3+. Many hosting accounts default to an older version.
  `vendor/composer/platform_check.php` has been edited to only require PHP
  8.1+ instead, since a full scan of the library found no PHP-8.3-only syntax
  actually in use — but this hasn't been verified against every PHP version.
  **Best fix, if your host offers it:** switch your site to PHP 8.3 or newer
  in your hosting control panel (cPanel: MultiPHP Manager / Select PHP
  Version) — no file changes needed, and it removes any doubt. If you can't
  get PHP 8.3 on your host, keep the lowered check, but specifically test the
  Archive page's Import and Export buttons after going live — if either
  produces an error, that's the sign your PHP version is missing something
  this library needs, and you'd need someone with a normal internet
  connection to run `composer require phpoffice/phpspreadsheet:^2.0` (or an
  even older `^1.29` for maximum compatibility) locally and re-upload the
  resulting `vendor/` folder.
