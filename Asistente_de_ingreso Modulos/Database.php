<?php
class Database {
    private $host = "localhost";
    private $usuario = "root";  // Usuario por defecto de XAMPP
    private $clave = "";        // Password por defecto de XAMPP (vacío)
    private $bd = "adi_db";
    private $conn;
    
    public function getConexion() {
        $this->conn = new mysqli($this->host, $this->usuario, $this->clave, $this->bd);
        
        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
        
        return $this->conn;
    }
}
?>