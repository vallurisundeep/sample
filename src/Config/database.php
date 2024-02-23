<?php
namespace App\Config;


class Database {
    private $connection;

    // Constructor to establish the database connection
    public function __construct() {
  

        // Get database credentials from environment variables
        $host = $_ENV['DB_HOST'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];
        $database = $_ENV['DB_NAME'];
       

        // Create a new MySQLi connection with port
        $this->connection = new \mysqli($host, $username, $password, $database);

        // Check connection
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    // Method to get the database connection
    public function getConnection() {
        return $this->connection;
    }
}

?>
