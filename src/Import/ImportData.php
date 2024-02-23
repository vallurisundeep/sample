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
    public function insertBatch($tableName, $excelFilePath, $batchSize = 1000)
    {
        // Open the Excel file
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFilePath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Total rows in the Excel file
        $totalRows = $worksheet->getHighestRow();

        // Prepare insert query
        $insertQuery = "INSERT INTO $tableName (create_time, first_name, last_name, country, phone) VALUES ";
        $paramTypes = 'sssss'; // Parameter types for create_time, first_name, last_name, country, and phone columns

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

            //  var_dump(count($batchData) == $batchSize);
            //  var_dump($row === $totalRows);
            // Insert data in batches
            if (count($batchData) == $batchSize || $row === $totalRows) {
                // Prepare placeholders for parameters in the insert query
                $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?),', count($batchData)), ',');

                // Construct the insert query with placeholders
                $insertQuery .= $placeholders;

                // Remove trailing comma and execute the insert query
                $stmt = $this->connection->prepare($insertQuery);

                // Check if prepare was successful
                if (!$stmt) {
                    die('Prepare failed: ' . htmlspecialchars($this->connection->error));
                }

                // // Bind parameters dynamically based on the number of rows in the batch
                // foreach ($batchData as $rowData) {
                //     // Prepare placeholders for parameters in the insert query
                //     $placeholders = rtrim(str_repeat('?,', count($rowData)), ',');

                //     // Construct the insert query with placeholders
                //     $insertQuery .= "($placeholders),";

                //     // Bind parameters dynamically based on the number of columns in the table
                //     $params = array_merge([$paramTypes], $rowData);

                //     $stmt->bind_param('sssss', $create_time, $first_name, $last_name, $country, $phone);

                //     // Execute the statement
                //     $stmt->execute();
                // }

                $params = [];
                foreach ($batchData as $rowData) {
                    $params = array_merge($params, $rowData);
                }
                $stmt->execute($params);

                
                // Reset insert query and batch data for the next batch
                $insertQuery = "INSERT INTO $tableName (create_time, first_name, last_name, country, phone) VALUES ";
                $batchData = [];
            }
        }
    }
}

?>
