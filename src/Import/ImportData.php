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
    public function insertBatch($tableName, $FilePath, $batchSize = 2000)
    {
        try {
            // Open the Excel file
            $spreadsheet = IOFactory::load($FilePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Total rows in the Excel file
            $totalRows = $worksheet->getHighestRow();

            // Prepare insert query
            $insertQuery = "INSERT INTO $tableName (vin, make, nameplate, country) VALUES (?, ?, ?, ?)";

            // Initialize data array for batch insertion
            $batchData = [];

            // Loop through rows and accumulate data for insertion
            for ($row = 2; $row <= $totalRows; $row++) {
                // Fetch data from Excel file
                $vin = $worksheet->getCell('A' . $row)->getValue(); // Assuming VIN is in column A
                $make = $worksheet->getCell('B' . $row)->getValue(); // Assuming Make is in column B
                $nameplate = $worksheet->getCell('C' . $row)->getValue(); // Assuming Nameplate is in column C
                $country = $worksheet->getCell('D' . $row)->getValue(); // Assuming Country is in column D

                // Prepare the data for insertion
                $rowData = [$vin, $make, $nameplate, $country];

                // Accumulate data for batch insertion
                $batchData[] = $rowData;

                // Insert data in batches
                if (count($batchData) == $batchSize || $row === $totalRows) {
                    // Execute the insert query for the current batch
                    $stmt = $this->connection->prepare($insertQuery);
                    if (!$stmt) {
                        throw new \Exception('Prepare failed: ' . htmlspecialchars($this->connection->error));
                    }

                    foreach ($batchData as $rowData) {
                        $stmt->bind_param("ssss", ...$rowData);
                        if (!$stmt->execute()) {
                            throw new \Exception('Execute failed: ' . htmlspecialchars($stmt->error));
                        }
                    }

                    // Reset batch data for the next batch
                    $batchData = [];
                }
            }
        } catch (\Exception $e) {
            // Handle exceptions
            die('Error: ' . $e->getMessage());
        }
    }
}
?>
