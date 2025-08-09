<?php
require_once __DIR__ . '/constants.php';

function connect_database() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

function close_database($pdo) {
    $pdo = null;
}
/**
 * Mulai transaksi database
 * @param PDO $pdo Objek PDO
 * @return bool Status keberhasilan
 */
function begin_transaction($pdo) {
    try {
        return $pdo->beginTransaction();
    } catch (PDOException $e) {
        error_log("Failed to begin transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Commit transaksi database
 * @param PDO $pdo Objek PDO
 * @return bool Status keberhasilan
 */
function commit_transaction($pdo) {
    try {
        return $pdo->commit();
    } catch (PDOException $e) {
        error_log("Failed to commit transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Rollback transaksi database
 * @param PDO $pdo Objek PDO
 * @return bool Status keberhasilan
 */
function rollback_transaction($pdo) {
    try {
        return $pdo->rollback();
    } catch (PDOException $e) {
        error_log("Failed to rollback transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Mengeksekusi query dan mengembalikan statement
 * @param PDO $pdo Objek PDO
 * @param string $query Query SQL
 * @param array $params Parameter untuk prepared statement
 * @return PDOStatement|false Statement yang sudah dieksekusi atau false jika gagal
 */
function execute_query($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage());
        return false;
    }
}
?>
