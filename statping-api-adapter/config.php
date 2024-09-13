<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################

# IMPORTAMT: AFTER ANY CHANGES TO THIS FILE, YOU NEED TO REGENERATE THE DEPLOYMENT SCRIPTS!

# All configurations will be stored in this PHP file.
# Keep the contents private, as it contains your secrets for the statping API and your devices. 
# Collector API Token - will be used for deployment on your device

$debug_mode = true;
$privacy_mode = false;

# API token for the custom scripts 
$api_token = 'YOUR-API-TOKEN';

# Define your download token
$downloadToken = 'YOUR-DOWNLOAD-TOKEN';


# Replace 'YOUR_BEARER_TOKEN' with your actual Bearer token - see https://documenter.getpostman.com/view/1898229/SzmfXwi4?version=latest 
$statping_token = 'YOUR-STATPING-TOKEN';
$statping_url = 'https://statping.your-domain.com/api/services';


# Some files that are used by this app
$storage_file = 'device_log.json';
$ubuntu_manager_script = 'manager-ubuntu.sh';
$windows_manager_script = 'manager-windows.ps1';
$macos_manager_script = 'manager-macos.sh';