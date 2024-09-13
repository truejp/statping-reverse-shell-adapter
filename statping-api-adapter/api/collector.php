<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################

# This file will be used to log the devices that have been deployed. Everytime the endpoint is called by one of the devices, it will log the device and the timestamp.
include '../config.php';

if (!isset($_GET['api_token']) || $_GET['api_token'] !== $api_token) {
    http_response_code(401); # Unauthorized
    die();
}

if (!isset($_GET['device'])) {
    http_response_code(400); # Bad Request
    die();
}

$device = $_GET['device'];
$timestamp = time();
$file = '../storage/' . $storage_file;

# Read the existing data from the file if it exists
$logData = [];
if (file_exists($file)) {
    $logData = json_decode(file_get_contents($file), true);
    if ($logData === null) {
        $logData = [];
    }
}

# Add or update the device entry
$logData[$device] = $timestamp;

# Remove entries older than 1 day
$oneDayAgo = strtotime('-1 day');
foreach ($logData as $key => $value) {
    if ($value < $oneDayAgo) {
        unset($logData[$key]);
    }
}

# Write the updated data back to the file
file_put_contents($file, json_encode($logData));

echo 'Device logged successfully.';
?>