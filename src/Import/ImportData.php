<?php

namespace App\Import;

class ImportData {
    private $tableName;
    private $historyTableName;
    private $connection;

    public function __construct($tableName, $historyTableName, $connection) {
        $this->tableName = $tableName;
        $this->historyTableName = $historyTableName;
        $this->connection = $connection;
    }

    public function importFromFile($filePath, $batchSize = 2000) {
        $file = fopen($filePath, "r");

        if ($file) {
            // Initialize a counter for total inserted rows
            $totalInserted = 0;

            // Initialize variables to store first and last VIN
            $firstVin = '';
            $lastVin = '';

            // Skip the header row
            fgets($file);

            // Read the file line by line
            $batchCounter = 0;
            while (($line = fgets($file)) !== false) {
                // Increment batch counter
                $batchCounter++;

                // Explode the line to get individual data elements
                $data = explode("|", $line);

                // Trim each data element to remove leading and trailing whitespace
                $data = array_map('trim', $data);

                // Check if the data array has exactly four elements
                if (count($data) === 4) {
                    $vin = $data[0];
                    $make = $data[1];
                    $nameplate = $data[2];
                    $country = $data[3];

                    // Insert data into the employee table
                    $this->insertData($vin, $make, $nameplate, $country);

                    // Increment the counter
                    $totalInserted++;

                    // Update the last VIN
                    $lastVin = $vin;

                    // Store the first VIN if not set
                    if (empty($firstVin)) {
                        $firstVin = $vin;
                    }
                }

                // If batch counter reaches the batch size, output progress and reset counters
                if ($totalInserted % $batchSize === 0) {
                    echo "Inserted $totalInserted rows so far.\n";
                }
            }

            // Close the file
            fclose($file);

            // Insert data into the history_table
            $this->insertHistory($firstVin, $lastVin, $make, $nameplate, $country);

            // Notify user about the progress
            echo "Data imported successfully! Total rows inserted: $totalInserted";

            // Return total inserted rows
            return $totalInserted;
        } else {
            return 0;
        }
    }

    private function insertData($vin, $make, $nameplate, $country) {
        $insertQuery = "INSERT INTO $this->tableName (vin, make, nameplate, country) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($insertQuery);

        if ($stmt === false) {
            echo "Error preparing statement: " . $this->connection->error;
            exit;
        }

        // Bind parameters
        $stmt->bind_param("ssss", $vin, $make, $nameplate, $country);

        // Execute the statement
        if (!$stmt->execute()) {
            echo "Error inserting data: " . $stmt->error;
            exit;
        }

        // Close the statement
        $stmt->close();
    }

    private function insertHistory($firstVin, $lastVin, $make, $nameplate, $country) {
        $insertHistoryQuery = "INSERT INTO $this->historyTableName (first_vin, last_vin, make, nameplate, country, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->connection->prepare($insertHistoryQuery);

        if ($stmt === false) {
            echo "Error preparing statement for history table: " . $this->connection->error;
            exit;
        }

        // Current timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Bind parameters
        $stmt->bind_param("sssssss", $firstVin, $lastVin, $make, $nameplate, $country, $timestamp, $timestamp);

        // Execute the statement
        if (!$stmt->execute()) {
            echo "Error inserting data into history table: " . $stmt->error;
            exit;
        }

        // Close the statement
        $stmt->close();
    }
}
?>
