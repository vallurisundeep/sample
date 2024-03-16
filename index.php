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
$filePath = 'data/test.txt';

// Open the text file for reading
$file = fopen($filePath, "r");

// Check if the file opened successfully
if ($file) {
    // Chunk size for data insertion
    $chunkSize = 2000; // Adjust as needed

    // Initialize a counter for inserted rows
    $totalInserted = 0;

    // Read the first line to get the first record
    $firstLine = fgets($file);
    $firstData = explode("|", $firstLine);
    $firstData = array_map('trim', $firstData);

    // Insert the first record into the history table
    $firstVin = $firstData[0];
    $firstMake = $firstData[1]; // Assuming make is the second column
    $firstNameplate = $firstData[2]; // Assuming nameplate is the third column
    $firstCountry = $firstData[3]; // Assuming country is the fourth column
    $historyQuery = "INSERT INTO history_table (first_vin, last_vin, timestamp, make, nameplate, country) VALUES (?, ?, NOW(), ?, ?, ?)";
    $stmt = $connection->prepare($historyQuery);
    $stmt->bind_param("sssss", $firstVin, $firstVin, $firstMake, $firstNameplate, $firstCountry);
    $stmt->execute();
    $stmt->close();

    // Initialize a variable to store the last inserted VIN
    $lastInsertedVin = $firstVin;

    // Read the file line by line, starting from the second line
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
                $lastInsertedVin = $vin; // Update the last inserted VIN
            }

            // Close the statement
            $stmt->close();
            // Update the last VIN in the history table
            $updateLastVinQuery = "UPDATE history_table SET last_vin = ?, make = ?, nameplate = ?, country = ? WHERE first_vin = ?";
            $stmt = $connection->prepare($updateLastVinQuery);
            $stmt->bind_param("sssss", $lastInsertedVin, $make, $nameplate, $country, $firstVin);
            $stmt->execute();
            $stmt->close();
            // Notify user about the progress
            echo "Inserted $totalInserted rows.\n";
            sleep(5);

            
        }
    }

    // Close the file
    fclose($file);

    echo "Data imported successfully!";
} else {
    echo "Error opening the file.";
}
?>
