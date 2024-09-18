
<?php
class ConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 5;

    private function __construct() {
        for ($i = 0; $i < $this->maxConnections; $i++) {
            $this->connections[] = new PDO('mysql:host=localhost;dbname=banco_db', 'user', 'password', [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if (count($this->connections) > 0) {
            return array_pop($this->connections);
        } else {
            throw new Exception("No hay conexiones disponibles.");
        }
    }

    public function releaseConnection($connection) {
        $this->connections[] = $connection;
    }
}

class TransactionManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ejecutarTransaccion($func) {
        try {
            $this->db->beginTransaction();
            $this->db->exec("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
            $func();
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error en la transacción: " . $e->getMessage());
        }
    }
}
?>
