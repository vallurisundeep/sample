<?php

namespace App\Import;

class ImportDatas {
    private $tableName;
    private $historyTableName;
    private $connection;

    public function __construct($tableName, $historyTableName, $connection) {
        $this->tableName = $tableName;
        $this->historyTableName = $historyTableName;
        $this->connection = $connection;
    }

    public function importFromFile($filePath) {
        // Open the text file for reading
        $file = fopen($filePath, "r");
    
        // Check if the file opened successfully
        if ($file) {
            // Chunk size for data insertion
            $chunkSize = 2000; // Adjust as needed
    
            // Initialize a counter for inserted rows
            $totalInserted = 0;
    
            // Initialize variables to store first and last records
            $firstRecord = null;
            $lastRecord = null;
    
            // Start a transaction
            $this->connection->begin_transaction();
    
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
    
                // Insert data into the employee table if chunk is not empty
                if (!empty($chunkData)) {
                    // Insert data into the employee table
                    $insertEmployeeQuery = "INSERT INTO {$this->tableName} (vin, make, nameplate, country) VALUES (?, ?, ?, ?)";
                    $stmtEmployee = $this->connection->prepare($insertEmployeeQuery);
    
                    // Bind parameters for employee table
                    $stmtEmployee->bind_param("ssss", $vin, $make, $nameplate, $country);
    
                    foreach ($chunkData as $row) {
                        list($vin, $make, $nameplate, $country) = $row;
                        if (!$stmtEmployee->execute()) {
                            // Handle error
                            echo "Error: " . $stmtEmployee->error;
                        } else {
                            $totalInserted++;
                        }
    
                        // Keep track of the last inserted record
                        $lastRecord = $row;
    
                        // Display progress message
                        echo "Inserted $totalInserted rows.\n";
    
                        // Check if it's the first record and update history table
                        if ($firstRecord === null) {
                            $firstRecord = $row;
                            // Insert data into the history table for the first time
                            $insertHistoryQuery = "INSERT INTO {$this->historyTableName} (first_vin, last_vin, make, nameplate, country) VALUES (?, ?, ?, ?, ?)";
                            $stmtHistory = $this->connection->prepare($insertHistoryQuery);
                            $stmtHistory->bind_param("sssss", $vin, $vin, $make, $nameplate, $country); // Note: first_vin and last_vin are the same for the first record
                            if (!$stmtHistory->execute()) {
                                // Handle error
                                echo "Error: " . $stmtHistory->error;
                            }
                            $stmtHistory->close();
                        }
                    }
    
                    // Close the employee table statement
                    $stmtEmployee->close();
                }
            }
    
            // Close the file
            fclose($file);
    
            // Store the last record in the history table
            if (!empty($lastRecord)) {
                list($lastVin, $lastMake, $lastNameplate, $lastCountry) = $lastRecord; // Last record
    
                // Update the last record in the history table
                $updateHistoryQuery = "UPDATE {$this->historyTableName} SET last_vin = ? WHERE first_vin = ?";
                $stmtUpdateHistory = $this->connection->prepare($updateHistoryQuery);
                $stmtUpdateHistory->bind_param("ss", $lastVin, $firstRecord[0]); // Update last_vin where first_vin matches
                if (!$stmtUpdateHistory->execute()) {
                    // Handle error
                    echo "Error: " . $stmtUpdateHistory->error;
                }
                $stmtUpdateHistory->close();
    
                echo "Data imported successfully!";
            } else {
                echo "No data to import.";
            }
    
            // Commit the transaction
            $this->connection->commit();
        } else {
            echo "Error opening the file.";
        }
    
        return $totalInserted;
    }
    
}
?>

