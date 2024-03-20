<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Import\ImportData;
use App\Import\ImportDatas;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create a database connection
$database = new Database();
$connection = $database->getConnection();

// Specify the table names
$tableName = 'employee';
$historyTableName = 'history_table';

// Specify the path to the text file
$filePath = 'data/test.txt';

// Create an instance of ImportData
$importData = new ImportDatas($tableName, $historyTableName, $connection);

// Import data from file
$totalInserted = $importData->importFromFile($filePath);

// Notify user about the progress
echo "Data imported successfully! Inserted $totalInserted rows.";
?>
