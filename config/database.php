<?php
// ============================================================
// config/database.php  –  PDO Connection & Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'complaint');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance with hardened security settings:
 *   - ERRMODE_EXCEPTION  → catch all DB errors via try/catch
 *   - FETCH_ASSOC        → cleaner array access
 *   - Emulated prepares  OFF  → real prepared statements (SQL injection protection)
 *   - stringify_fetches  OFF  → correct PHP types for integers/floats
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // ← real prepared statements
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw DB errors to the browser
            error_log('DB Connection Error: ' . $e->getMessage());
            http_response_code(500);
            die('<p style="font-family:sans-serif;color:#b91c1c;padding:2rem;">
                 Database connection failed. Please contact the administrator.</p>');
        }
    }

    return $pdo;
}
