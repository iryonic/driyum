<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'snack_store');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function get_db_connection() {
    static $connection = null;
    
    if ($connection === null) {
        $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$connection) {
            die("Connection failed: " . mysqli_connect_error());
        }
        
        mysqli_set_charset($connection, DB_CHARSET);
    }
    
    return $connection;
}

// Close database connection
function close_db_connection() {
    $connection = get_db_connection();
    if ($connection) {
        mysqli_close($connection);
    }
}

// Execute query with prepared statements
function db_query($sql, $params = [], $types = '') {
    $connection = get_db_connection();

    try {
        $stmt = mysqli_prepare($connection, $sql);

        if (!$stmt) {
            return false;
        }

        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);

        // Attempt to get a result set (works for SELECT and SHOW queries)
        $result = mysqli_stmt_get_result($stmt);
        if ($result !== false && $result instanceof mysqli_result) {
            mysqli_stmt_close($stmt);
            return $result;
        }

        // Otherwise return number of affected rows for write queries
        $affected_rows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected_rows;
    } catch (mysqli_sql_exception $e) {
        // Log the error for debugging but don't expose sensitive info to users
        error_log("DB Query Error: " . $e->getMessage() . " -- SQL: " . $sql);
        return false;
    }
}

// Fetch single row
function db_fetch_single($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);

    // Ensure we have a mysqli_result before calling mysqli_num_rows
    if ($result instanceof mysqli_result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return null;
}

// Fetch all rows
function db_fetch_all($sql, $params = [], $types = '') {
    $result = db_query($sql, $params, $types);
    $rows = [];
    
    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

// Get last insert ID
function db_last_insert_id() {
    $connection = get_db_connection();
    return mysqli_insert_id($connection);
}

// Escape string
function db_escape($string) {
    $connection = get_db_connection();
    return mysqli_real_escape_string($connection, $string);
}
?>