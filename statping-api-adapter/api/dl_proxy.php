<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################
 
# This file is used to serve the matching script for each of the OSs that are supported by the deployment manager.

include '../config.php';

# Check if the request contains a valid token
if (isset($_GET['token']) && $_GET['token'] === $downloadToken) {
    # Provide the path to your .sh file
    $filePath = 'deployment/' . $_GET['fn'];

    # Check if the file exists
    if (file_exists($filePath)) {
        # Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));

        # Read and output the file contents
        readfile($filePath);
        exit;
    } else {
        # File not found
        die('File not found.');
    }
} else {
    # Invalid token
    die('Invalid token.');
}
?>
