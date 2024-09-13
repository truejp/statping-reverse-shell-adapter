<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################

# This Script is used as proxy for the API Calls, so that your webserver will not throw any annoying CORS exceptions without complex workarounds. */

# error_reporting(E_ALL);
# ini_set('display_errors', 1);

# Set the CORS headers to allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Authorization");

include '../config.php';

# Check if the request is a preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Headers: Authorization');
    exit();
}


# Set up cURL to make the API request with the Bearer token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $statping_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $statping_token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

# Execute the cURL request
$response = curl_exec($ch);

# Check for cURL errors
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
    exit();
}

# Close cURL session
curl_close($ch);

# Set the Content-Type header to JSON
header('Content-Type: application/json');

# Send the API response back to the client
echo $response;
