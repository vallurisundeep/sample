<?php
namespace App\Import;

class ImportData
{
    private $connection;

    // Constructor to initialize the database connection
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    // Method to perform batch data insertion
    public function insertBatch($tableName, $excelFilePath, $batchSize = 2000)
    {
        // Open the Excel file
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Total rows in the Excel file
        $totalRows = $worksheet->getHighestRow();

        // Prepare insert query
        $insertQuery = "INSERT INTO $tableName (create_time, first_name, last_name, country, phone) VALUES (?, ?, ?, ?, ?)";
        
        // Initialize data array for batch insertion
        $batchData = [];

        // Loop through rows and accumulate data for insertion
        for ($row = 2; $row <= $totalRows; $row++) {
            // Start from the second row
            // Generate current timestamp for create_time
            $create_time = date('Y-m-d H:i:s');

            // Fetch data from Excel file
            $first_name = $worksheet->getCell('A' . $row)->getValue(); // Assuming first_name is in column A
            $last_name = $worksheet->getCell('B' . $row)->getValue(); // Assuming last_name is in column B
            $country = $worksheet->getCell('C' . $row)->getValue(); // Assuming country is in column C
            $phone = $worksheet->getCell('D' . $row)->getValue(); // Assuming phone is in column D

            // Prepare the data for insertion
            $rowData = [$create_time, $first_name, $last_name, $country, $phone];

            // Accumulate data for batch insertion
            $batchData[] = $rowData;

            // Insert data in batches
            if (count($batchData) == $batchSize || $row === $totalRows) {
                // Execute the insert query for the current batch
                $stmt = $this->connection->prepare($insertQuery);
                if (!$stmt) {
                    die('Prepare failed: ' . htmlspecialchars($this->connection->error));
                }
                
                foreach ($batchData as $rowData) {
                    $stmt->bind_param("sssss", ...$rowData);
                    $stmt->execute();
                }

                // Reset batch data for the next batch
                $batchData = [];
            }
        }
    }
}
?>
