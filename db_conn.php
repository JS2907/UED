<?php
/*************************************************
 * DB Lazy Connection (가비아 MySQL 8 대응)
 *************************************************/

function db() {
    static $pdo = null;

    if ($pdo === null) {
        $host = 'my8003.gabiadb.com';
        $db   = 'ksse';
        $user = 'ksse2907';
        $pass = 'ksse2907!!';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $opt);
        } catch (\PDOException $e) {
            die("DB 연결 실패: " . $e->getMessage());
        }
    }

    return $pdo;
}
