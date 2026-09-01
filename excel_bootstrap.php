<?php
// Shared bootstrap for the only two pages that use PhpSpreadsheet: arch.php
// (Archive / Import-Export) and export_excel.php.
//
// Why this file exists: when the host does not meet the library's platform
// requirements, Composer's own check (vendor/composer/platform_check.php) aborts
// with a bare 500 and a message that deliberately strips the running PHP version,
// so the page shows only "Composer detected issues in your platform..." with no
// way to tell what is actually wrong. Check the same requirements here first and
// report them in full instead.
//
// Requirements come from composer.lock: PHP 8.1+ (phpoffice/phpspreadsheet and
// maennchen/zipstream-php), plus the extensions PhpSpreadsheet lists. "zip" is
// only needed for reading an uploaded .xlsx, but a host missing it would break
// the import half of arch.php, so it is checked up front either way.

$excelPlatformIssues = [];

if (PHP_VERSION_ID < 80100) {
    $excelPlatformIssues[] = 'PHP 8.1 or newer is required &mdash; this server runs PHP ' . PHP_VERSION . '.';
}

foreach (['zip', 'gd', 'mbstring', 'iconv', 'ctype', 'dom', 'libxml', 'simplexml', 'xml', 'xmlreader', 'xmlwriter', 'fileinfo', 'zlib'] as $requiredExtension) {
    if (!extension_loaded($requiredExtension)) {
        $excelPlatformIssues[] = 'The PHP extension "' . $requiredExtension . '" is required but is not enabled on this server.';
    }
}

if ($excelPlatformIssues) {
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<div style="font-family:system-ui,Segoe UI,Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:28px;border-radius:14px;max-width:760px;margin:40px auto;line-height:1.6">';
    echo '<h2 style="color:#fca5a5;margin-top:0">The Excel tools cannot run on this server</h2>';
    echo '<ul style="color:#fee2e2">';
    foreach ($excelPlatformIssues as $issue) {
        echo '<li>' . $issue . '</li>';
    }
    echo '</ul>';
    echo '<p style="font-size:14px;color:#cbd5e1">Fix this in your hosting control panel (cPanel: <em>MultiPHP Manager</em> to change the PHP version, <em>Select PHP Version &rarr; Extensions</em> to enable an extension), then reload this page. '
       . 'Every other page of the site keeps working meanwhile &mdash; only Import/Export Excel needs these.</p>';
    echo '<p><a href="admin.php" style="color:#38bdf8">&larr; Back to the dashboard</a></p>';
    echo '</div>';
    exit;
}

require __DIR__ . '/vendor/autoload.php';
