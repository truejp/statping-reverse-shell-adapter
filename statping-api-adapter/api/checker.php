<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################
 
# checker.php will be used to check the status of a device based on the last time it was logged.
# This plays a big part in checking devices behind private NAT gateways or that don't offer a web service that can be checked natively.

if (!isset($_GET['device'])) {
    http_response_code(400); # Bad Request
    die();
}

include '../config.php';
$device = $_GET['device'];
$currentTime = time();
$file = '../storage/' . $storage_file;

# Read the existing data from the file if it exists
$logData = [];
if (file_exists($file)) {
    $logData = json_decode(file_get_contents($file), true);
    if ($logData === null) {
        $logData = [];
    }
}

if (array_key_exists($device, $logData) && $currentTime - $logData[$device] <= 180) {
    $response = [
        'timestamp' => $logData[$device],
        'device' => $device,
        'online' => true,
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    http_response_code(404); # Not Found - 404 will indicate inavailability of the device to statping
    echo 'Device not found or offline.';
}
?>
