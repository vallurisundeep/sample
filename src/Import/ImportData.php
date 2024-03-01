<?php
namespace App\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportData
{
    private $connection;

    // Constructor to initialize the database connection
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    // Method to perform batch data insertion
    public function insertBatch($tableName, $filePath, $batchSize = 2000)
    {
        try {
        } catch (\Exception $e) {
            // Rollback transaction on error
            $this->connection->rollback();
            // Handle exceptions
            die('Error: ' . $e->getMessage());
        }
    }
}
?>
