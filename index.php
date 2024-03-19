<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Parser;

$csvFile = fopen('cars_data.csv', 'w');
fputcsv($csvFile, Parser::$headers);

(new Parser())->run();

fclose($csvFile);