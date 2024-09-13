<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################

# IMPORTANT NOTE: PLEASE CHANGE THE SETTINGS IN config.php BEFORE USING THIS TOOL IN PRODUCTION!
session_start();

# Define the required deployment script paths
include 'config.php';

# handle privacy mode POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_privacy'])) {
    if ($_POST['download_token'] === $downloadToken) {
        $_SESSION['privacy_unlocked'] = true;
    }
    else {
        $unlock_error_msg = "<div class='error'>Invalid download token. Try again.</div>";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_privacy'])) {
    $_SESSION['privacy_unlocked'] = false;
}

# Implement privacy mode

$deployment_dir = __DIR__ . '/api/deployment';
$storage_dir = __DIR__ . '/storage';


$manager_script_ubuntu = $deployment_dir . '/manager-ubuntu.sh';

if ($privacy_mode && !$_SESSION['privacy_unlocked']) {
    $dl_tk = 'YOUR_DOWNLOAD_TOKEN';
} else {
    $dl_tk = $downloadToken;
}



$dl_proxy_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/dl_proxy.php?token=' . $dl_tk;
$statping_check_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/checker.php?device=';

# Deployment codes for different plattforms
# Currently supported:
# - Ubuntu Desktop
# - Ubuntu Server
#


# Define manager URLs
$ubuntu_url = $dl_proxy_url . '&fn=' . $ubuntu_manager_script;
$windows_url = $dl_proxy_url . '&fn=' . $windows_manager_script;
$macos_url = $dl_proxy_url . '&fn=' . $macos_manager_script;

$deployment_commands_ubuntu_desktop = <<<EOT
sudo apt-get install curl -y
sudo curl -o /usr/local/bin/checkin-manager "$ubuntu_url"
sudo chmod +x /usr/local/bin/checkin-manager
checkin-manager
EOT;

$deployment_commands_ubuntu_server = <<<EOT
sudo apt-get install curl -y
mkdir -p ~/checkin-manager
curl -o ~/checkin-manager/checkin-manager.sh "$ubuntu_url"
echo 'export PATH="$HOME/checkin-manager:$PATH"' >> ~/.bashrc
chmod +x ~/checkin-manager/checkin-manager.sh
checkin-manager
EOT;

$deployment_commands_macos = <<<EOT
brew install curl
sudo curl -o /usr/local/bin/checkin-manager "$macos_url"
sudo chmod +x /usr/local/bin/checkin-manager
checkin-manager
EOT;

$deployment_commands_windows = <<<EOT
Invoke-WebRequest -Uri "$windows_url" -OutFile "C:\Program Files\checkin-manager.ps1"
powershell.exe -ExecutionPolicy Bypass -File "C:\Program Files\checkin-manager.ps1"
Start-Process "powershell.exe" -ArgumentList "C:\Program Files\checkin-manager.ps1"
EOT;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        .status {
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .button {
            padding: 10px 20px;
            font-size: 1em;
            color: white;
            background-color: blue;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .deployment-section {
            margin-top: 40px;
        }
        .status-banner {
            padding: 10px;
            background-color: #f4f4f4;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid grey;
            padding: 8px;
            text-align: left;
        }
        pre {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<?php if ($privacy_mode) {
    echo '<div class="status-banner">';
    if (!$_SESSION['privacy_unlocked']) { ?>
        <div class="success">Privacy mode is enabled. Enter download token to unlock all features.</div>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="margin-top: 15px;" method="post">
            <label for="download_token">Download Token:</label>
            <input type="text" name="download_token" id="download_token" required>
            <button type="submit" class="button" name="unlock_privacy">Unlock Privacy Mode</button>
        </form>
        
    <?php }
    else { ?>
        <div class="error">Privacy mode is unlocked. You can now access all features.</div>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="margin-top: 15px;" method="post">
            <button type="submit" class="button" name="lock_privacy">Lock Privacy Mode</button>
        </form>
    <?php }
    echo $unlock_error_msg . '</div>';
} 

if ($debug_mode): ?>
<div class="status-banner">
        <div class="error">Debug mode is enabled. You can view the device log below.</div>
</div>
<?php endif; ?>

<h1>Statping Reverse Shell Tool</h1>
<p>This tool helps you to monitor your hosts in private networks and behind NAT Gateways with Statping.<br>
It generates the deployment scripts and instructions for you to deploy the monitoring script on your devices.<br>
The underlying principle is based on reverse SSH tunneling, where the devices will check in with the server to update their status.</p>


<?php
# Handle the deployment script generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_deployment'])) {
    echo "<pre>
    <div class='status'>Starting deployments manager...</div>
    <p>Generating deployment scripts...<p>";
    include 'api/generate-deployments.php';
    echo "<div class='success'>Deployment scripts generated successfully.</div></pre>";
}

# Handle the storage file generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_storage'])) {
    
    if ($privacy_mode && !$_SESSION['privacy_unlocked']) {
        echo "<div class='error'>Error: Privacy mode is enabled. Unlock privacy mode to generate the storage file.</div>";
        echo "<p>Please reload this page.</p>";
        exit;
    }
    echo "<pre>
    <div class='status'>Starting storage manager...</div>
    <p>Wiping local storage...</p>";
    # Check if the storage directory exists
    if (is_dir($storage_dir)) {
        $files = glob($storage_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    echo "<p>Generating local storage folder...</p>";
    # Check if the storage directory exists
    if (!is_dir($storage_dir)) {
        mkdir($storage_dir, 0700, true);
    }
    else {
        echo "<p>The storage directory already exists.</p>";
    }

    echo "<p>Generating local storage file...</p>";
    $logData = [];
    file_put_contents($storage_dir . '/' . $storage_file, json_encode($logData));

    echo "<div class='success'>Local storage file generated successfully.</div></pre>";
}

# Check if scripts were indeed generated
$all_scripts_exist = (file_exists('api/deployment/' . $ubuntu_manager_script) && file_exists('api/deployment/' . $windows_manager_script) && file_exists('api/deployment/' . $macos_manager_script));
$storage_exists = file_exists($storage_dir) && file_exists($storage_dir . '/' . $storage_file);
?>

<div class="status">
    <?php if ($all_scripts_exist): ?>
        <div class="success">All required deployment scripts are available.</div>
    <?php else: ?>
        <div class="error">Error: One or more required deployment scripts are missing.</div>
    <?php endif; ?>
</div>

<div class="status">
    <?php 
    # check if storage folder and json file exists
    if (file_exists($storage_dir) && file_exists($storage_dir . '/' . $storage_file)) {
        echo '<div class="success">Storage folder and file exist.</div>';
    } else {
        echo '<div class="error">Error: Storage folder or file is missing.</div>';
    }
    ?>
</div>
<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="margin-top: 15px;" method="post">
    <button type="submit" class="button" name="create_deployment">Regenerate Deployments</button>
</form>

<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" style="margin-top: 15px;" method="post">
    <button type="submit" class="button" name="create_storage">Rebuild Local Storage</button>
</form>

<?php if ($all_scripts_exist && $storage_exists): ?>
    <div class="deployment-section">
        <h2>Deployment Instructions</h2>
        <p>Use the following instructions to execute the local manager script on the host. With these scripts, you will be able to use the command line interface (CLI) to control if your clients are updating your proxy server.</p>

        <h3>Ubuntu 2x.xx (regular)</h3>
        <pre id="ubuntu-regular"><?= htmlspecialchars($deployment_commands_ubuntu_desktop) ?></pre>
        <button onclick="copyToClipboard('ubuntu-regular')">Copy to Clipboard</button>
        
        <h3>Ubuntu (Single User alternative)</h3>
        <pre id="ubuntu-alt"><?= htmlspecialchars($deployment_commands_ubuntu_server) ?></pre>
        <button onclick="copyToClipboard('ubuntu-alt')">Copy to Clipboard</button>

        <h3>macOS (Apple Silicon / x86_64)</h3>
        <p>If brew is not installed, you can install it by running the following commands:</p>
        <pre id="brew-fix">/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
brew --version</pre>
        <button onclick="copyToClipboard('brew-fix')">Copy to Clipboard</button>
        <p>This might take a while. Afterwards you might need to run some post-install steps, to add brew to your environment. The installer will tell you. <br>
        Then you can run the following commands to install the manager script:</p>

        <pre id="macos-regular"><?= htmlspecialchars($deployment_commands_macos) ?></pre>
        <button onclick="copyToClipboard('macos-regular')">Copy to Clipboard</button>

        <h3>Windows</h3>
        <pre id="windows-regular"><?= htmlspecialchars($deployment_commands_windows) ?></pre>
        <button onclick="copyToClipboard('windows-regular')">Copy to Clipboard</button>
        <p>This script is using the MS Task Scheduler. It will ask you to become an administrator. Once it has been set up, there is no additional need for administrative privileges unless you want to update the settings.</p>
        
        <hr>
        <h2>Device Status</h2>
        <?php if ($debug_mode): ?>
            <table style="border: 1px solid grey;">
                <tr>
                    <th>Device</th>
                    <th>Last Check-in</th>
                    <th>Status</th>
                    <th>Statping URL</th>
                </tr>
                <?php
                $logData = json_decode(file_get_contents($storage_dir . '/' . $storage_file), true);
                foreach ($logData as $device => $timestamp) {
                    echo "<tr><td>$device</td><td>" . date('Y-m-d H:i:s', $timestamp) . "</td><td>" . ($currentTime - $timestamp <= 180 ? 'Online' : 'Offline') . "</td><td>" . $statping_check_url . $device . "</td></tr>";
                }
                if (empty($logData)) {
                    echo '<tr><td colspan="3">No devices found.</td></tr>';
                }
                ?>
            </table>
            <p><strong>Note:</strong> The device status is based on the last check-in time. Devices that have not checked in within the last 3 minutes are considered offline.</p>
        <?php else: ?>
            <div class="error">Debug mode is disabled. Enable it in the config file to view the device log.</div>
        <?php endif; ?>

    </div>
<?php endif; ?>
<p>Written by: <a href="https://pixel-shift.de" target="_blank">Pixel Shift</a> / Philipp Lehnet</p>

<script>
function copyToClipboard(elementId) {
    // simple javascript that copeis the content of a pre element to the clipboard
    var textElement = document.getElementById(elementId);
    if (!textElement) {
        console.error("Element mit der ID '" + elementId + "' wurde nicht gefunden.");
        return;
    }
    var textToCopy = textElement.innerText;
    navigator.clipboard.writeText(textToCopy).then(function() {
        var button = document.querySelector("button[onclick=\"copyToClipboard('" + elementId + "')\"]");
        if (button) {
            button.innerText = "Copied!";
            setTimeout(function() {
                button.innerText = "Copy to Clipboard";
            }, 2000);
        }
    }).catch(function(err) {
        console.error("Failed to copy: ", err);
    });
}

</script>

</body>
</html>