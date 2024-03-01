<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Import\ImportData;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create a database connection
$database = new Database();
$connection = $database->getConnection();

// Create an instance of ImportData

// Specify the table name
$tableName = 'employee';

// Specify the path to the text file
$filePath = 'data/data.txt';

// Open the text file for reading
$file = fopen($filePath, "r");

// Check if the file opened successfully
if ($file) {
    // Chunk size for data insertion
    $chunkSize = 2000; // Adjust as needed

    // Initialize a counter for inserted rows
    $totalInserted = 0;

    // Read the file line by line
    while (!feof($file)) {
        // Read lines in chunks
        $chunkData = [];
        $chunkCounter = 0;
        while ($chunkCounter < $chunkSize && ($line = fgets($file)) !== false) {
            // Explode the line to get individual data elements (assuming tab-separated values)
            $data = explode("|", $line);

            // Trim each data element to remove leading and trailing whitespace
            $data = array_map('trim', $data);

            // Check if 'make' field is not empty after trimming
            if (!empty($data[1])) {
                $chunkData[] = $data;
                $chunkCounter++;
            }
        }

        // Insert data into the database if chunk is not empty
        if (!empty($chunkData)) {
            // Insert data into the database
            $insertQuery = "INSERT INTO $tableName (vin, make, nameplate, country) VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($insertQuery);

            // Bind parameters
            $stmt->bind_param("ssss", $vin, $make, $nameplate, $country);

            foreach ($chunkData as $row) {
                list($vin, $make, $nameplate, $country) = $row;
                $stmt->execute();
                $totalInserted++;
            }

            // Close the statement
            $stmt->close();

            // Notify user about the progress
            echo "Inserted $totalInserted rows.\n";
        }
    }

    // Close the file
    fclose($file);

    echo "Data imported successfully!";
} else {
    echo "Error opening the file.";
}
?>
