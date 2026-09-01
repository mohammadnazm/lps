<?php
/**
 * LOZAN Unified System - hosting configuration
 *
 * Change only these values before uploading to your hosting account.
 */

// Production-safe error handling: never print raw PHP errors/warnings to visitors
// (they can leak paths and query details), but always keep a log for the admin.
// Set LOZAN_DEBUG=1 in the environment on a staging server if you need on-screen errors.
if (!defined('LOZAN_ERROR_HANDLING_SET')) {
    define('LOZAN_ERROR_HANDLING_SET', true);
    $lozanDebug = getenv('LOZAN_DEBUG') === '1';
    error_reporting(E_ALL);
    ini_set('display_errors', $lozanDebug ? '1' : '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php-error.log');
    if (!is_dir(__DIR__ . '/logs')) { @mkdir(__DIR__ . '/logs', 0755, true); }
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('LOZAN_DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('LOZAN_DB_USER') ?: 'root');
    define('DB_PASS', getenv('LOZAN_DB_PASS') ?: '12345678');
    define('DB_NAME', getenv('LOZAN_DB_NAME') ?: 'school_sys');
}

// Application name shown in the browser/navbar.
define('APP_NAME', 'Unified School Management System');
define('APP_TIMEZONE', 'Asia/Baghdad');

date_default_timezone_set(APP_TIMEZONE);

// Session hardening. HTTPS is detected automatically on the hosting server.
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
