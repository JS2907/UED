<?php
function db() {
    static $pdo = null;
    if ($pdo === null) {
        // 실제 운영 시에는 이 정보를 .env 파일이나 웹 루트 밖의 파일에서 불러오도록 수정하세요.
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
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $opt);
        } catch (\PDOException $e) {
            // 보안상 상세 에러 메시지는 로그로 남기고 사용자에게는 일반 메시지 출력
            error_log($e->getMessage());
            die("DB 연결 오류가 발생했습니다. 잠시 후 다시 시도해주세요.");
        }
    }
    return $pdo;
}