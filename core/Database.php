<?php
namespace Core;
/**
 * Database — singleton-обёртка для PDO.
 */
/**
 * Класс Database — централизованное подключение к базе данных
 */
 
 /*
 Как использовать подключение в модуле или контроллере:
 
 use Core\Database;

$pdo = Database::getConnection();
$pdo = new \PDO(...);

// Пример запроса
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([1]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
 
 */
 
class Database
{
    private static ?\PDO $instance = null;
    protected PDO $pdo;

    public function __construct()
    {
        try {

            $this->pdo = new PDO(
                'mysql:host=localhost;dbname=test;charset=utf8mb4',
                'root',
                '',
                [

                    // выбрасывать исключения при ошибках
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                    // получать ассоциативные массивы
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                    // использовать настоящие prepared statements
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

        } catch (PDOException $e) {

            die('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
//------------------------------------------------------
    /**
     * Получение единственного экземпляра PDO
     */
    public static function init(array $config): void
    {
        if (self::$instance !== null) return;

        $host = $config['host'] ?? '127.0.0.1';
        $dbname = $config['dbname'] ?? '';
        $user = $config['user'] ?? 'root';
        $password = $config['password'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        try {
            self::$instance = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            die('Database connection error: ' . $e->getMessage());
        }
    }
//------------------------------------------------------
    /**
     * Получение единственного экземпляра PDO
     */
    public static function getConnection(): \PDO
    {
        if (self::$instance === null) {
		
            $config = require __DIR__ . '/../config/db.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            try {
                self::$instance = new \PDO($dsn, $config['user'], $config['password']);
                self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                die("Ошибка подключения к базе данных: " . $e->getMessage());
            }
        }
/*		*/		
        if (self::$instance === null) {
            throw new \Exception('Database not initialized. Call Database::init($config) first.');
        }

        return self::$instance;
    }
}

