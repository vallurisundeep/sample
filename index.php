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
$importer = new ImportData($connection);

// Specify the table name
$tableName = 'employee';

// Specify the path to the Excel file
$FilePath = 'data/data.txt';

// Import data from Excel file in batches
$importer->insertBatch($tableName, $FilePath);

echo "Data imported successfully!";
