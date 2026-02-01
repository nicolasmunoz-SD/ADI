<?php
class Database {
    private $host = "localhost";
    private $db_name = "adi_db"; 
    private $username = "root";
    private $password = "";
    private $conn;
    
    
    public function getConexion() {
        $this->conn = null;
        
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8");
            
        } catch(Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
        
        return $this->conn;
    }
    
    
    public function getPDO() {
        try {
            $pdo = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch(PDOException $e) {
            die("Error PDO: " . $e->getMessage());
        }
    }
    
  
    public function prepare($sql) {
        $pdo = $this->getPDO();
        return $pdo->prepare($sql);
    }
}
?>