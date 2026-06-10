<?php
// config/db.php - Works on XAMPP (MySQL) AND Render (PostgreSQL)

// Set timezone to East African Time
date_default_timezone_set('Africa/Nairobi');

function parseDatabaseUrl($database_url) {
    $url = parse_url($database_url);
    if ($url === false) {
        throw new Exception('Invalid DATABASE_URL');
    }

    $scheme = $url['scheme'] ?? '';
    if ($scheme !== 'postgres' && $scheme !== 'postgresql') {
        throw new Exception('Unsupported database scheme: ' . $scheme);
    }

    $host = $url['host'] ?? '';
    $port = $url['port'] ?? 5432;
    $dbname = ltrim($url['path'] ?? '', '/');
    $username = $url['user'] ?? '';
    $password = $url['pass'] ?? '';
    $queryParams = [];
    if (isset($url['query'])) {
        parse_str($url['query'], $queryParams);
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";
    foreach ($queryParams as $key => $value) {
        $dsn .= "{$key}={$value};";
    }

    if (!array_key_exists('sslmode', $queryParams)) {
        $dsn .= 'sslmode=require;';
    }

    return [
        'dsn' => $dsn,
        'username' => $username,
        'password' => $password,
    ];
}

// Check if running on Render (has DATABASE_URL environment variable)
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // ---------- RENDER (PostgreSQL) ----------
    try {
        $dbConfig = parseDatabaseUrl($database_url);
        $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        define('DB_ENVIRONMENT', 'postgresql');
    } catch(Exception $e) {
        die('Render Database Connection Failed: ' . $e->getMessage());
    }
} else {
    // ---------- XAMPP LOCAL (MySQL) ----------
    $host = 'localhost';
    $dbname = 'together_sacco';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        define('DB_ENVIRONMENT', 'mysql');
    } catch(PDOException $e) {
        die('XAMPP Database Connection Failed: ' . $e->getMessage());
    }
}

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- HELPER FUNCTIONS for cross-database compatibility ----------

// Helper function for LIMIT clause (MySQL vs PostgreSQL difference)
function getLimitClause($limit, $offset = 0) {
    if (DB_ENVIRONMENT === 'postgresql') {
        return "LIMIT $limit OFFSET $offset";
    } else {
        return "LIMIT $offset, $limit";
    }
}

// Helper function for searching (LIKE vs ILIKE)
function getLikeClause($column, $searchTerm) {
    if (DB_ENVIRONMENT === 'postgresql') {
        return "$column ILIKE :search";
    } else {
        return "$column LIKE :search";
    }
}

// Helper function for CONCAT (handles both databases)
function getConcatClause($columns, $separator = ' ') {
    if (DB_ENVIRONMENT === 'postgresql') {
        return "CONCAT(" . implode(", ' ', ", $columns) . ")";
    } else {
        return "CONCAT_WS('$separator', " . implode(", ", $columns) . ")";
    }
}

// Helper function for NOW() (works in both)
function getCurrentTimestamp() {
    return "NOW()";
}

// Helper function for pagination calculations
function paginate($currentPage, $recordsPerPage, $totalRecords) {
    $currentPage = max(1, $currentPage);
    $offset = ($currentPage - 1) * $recordsPerPage;
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    return [
        'offset' => $offset,
        'limit' => $recordsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_records' => $totalRecords
    ];
}
?>