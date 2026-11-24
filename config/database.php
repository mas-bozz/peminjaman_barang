<?php
/**
 * Database Connection Configuration (MySQL / MariaDB via PDO)
 * Standard SQL for Laragon / XAMPP environments
 */

class Database {
    private static ?PDO $pdo = null;

    // Database configuration credentials
    private static string $host = '127.0.0.1';
    private static string $port = '3306';
    private static string $user = 'root';
    private static string $pass = '';
    private static string $dbname = 'inventaris_db';

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            try {
                // First attempt connection directly to the database
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', self::$host, self::$port, self::$dbname);
                self::$pdo = new PDO($dsn, self::$user, self::$pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                // If database doesn't exist yet (code 1049), auto-create it and import schema
                if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    self::createDatabaseAndImport();
                } else {
                    http_response_code(500);
                    die(json_encode([
                        'status' => 'error',
                        'message' => 'Gagal terhubung ke MySQL: ' . $e->getMessage() . '. Pastikan MySQL di Laragon / XAMPP sudah berjalan.'
                    ]));
                }
            }
        }
        return self::$pdo;
    }

    private static function createDatabaseAndImport(): void {
        try {
            $rootDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', self::$host, self::$port);
            $rootPdo = new PDO($rootDsn, self::$user, self::$pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Read database.sql file
            $sqlFile = __DIR__ . '/../database.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $rootPdo->exec($sql);
            } else {
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . self::$dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            }

            // Now connect to the newly created database
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', self::$host, self::$port, self::$dbname);
            self::$pdo = new PDO($dsn, self::$user, self::$pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            die(json_encode([
                'status' => 'error',
                'message' => 'Gagal inisialisasi database MySQL: ' . $ex->getMessage()
            ]));
        }
    }
}
