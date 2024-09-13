<?php
########################################
#   (C) 2024 by Philipp Lehnet
#   Statping Reverse Shell Tool 
#   https://pixel-shift.de
#################

if (!isset($debug_mode)) {
    # this means the script is invoked from external source
    http_response_code(400);
    # ciao!
    die();
}

if ($privacy_mode && !$_SESSION['privacy_unlocked']) {
    echo "Privacy mode is enabled. Please unlock privacy mode to generate deployment scripts.\n";
    echo "Please reload this page.\n";
    exit;
}

$api_token = $api_token ?? '';

# Determine the script's directory URL
$script_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/api/collector.php?api_token=' . $api_token . '&device=';

# Directory where the manager script will be created
$deployment_dir = __DIR__ . '/deployment';

# Ensure the deployment directory exists
echo "Creating the deployment directory...\n";
if (!is_dir($deployment_dir)) {
    mkdir($deployment_dir, 0700, true);
}
else {
    rmdir($deployment_dir);
    mkdir($deployment_dir, 0700, true);
    echo "The deployment directory has been wiped and recreated.\n";
}



####################
# Ubuntu Manager Script
# x86_64 Linux (Ubuntu)
####################

$ubuntu_script_path = $deployment_dir . '/' . $ubuntu_manager_script;
# Ubuntu Manager Script Template
$script_content = <<<EOT
#!/bin/bash

# Base URL
BASE_URL="$script_url"

# File to store the device name and job status
STATE_FILE="\$HOME/device_monitor_state.txt"

# Function to display current status
display_status() {
    if [ -f "\$STATE_FILE" ]; then
        DEVICE_NAME=\$(cat "\$STATE_FILE")
        if [ -n "\$DEVICE_NAME" ]; then
            echo -e "\e[1mMonitoring Status\e[0m"
            echo "---------------------"
            echo -e "Current Device Name: \033[32m\$DEVICE_NAME\033[0m"
            check_existing_cron_job
            echo "---------------------"
        fi
    else
        echo -e "\e[1mLifecycle Ping Script v0.1 - Philipp Lehnet, 2023\e[0m"
        echo "---------------------"
        echo -e "\e[31mNo device is currently being monitored.\e[0m"
    fi
}

# Function to check if a job is already registered
check_existing_job() {
    display_status
    if [ -f "\$STATE_FILE" ]; then
        DEVICE_NAME=\$(cat "\$STATE_FILE")
        if [ -n "\$DEVICE_NAME" ]; then
            echo -e "1. \e[91mDisable Monitoring\e[0m"
            echo -e "2. \e[96mSet a New Device Name\e[0m"
            echo -e "3. \e[34mCheck Job Registration\e[0m"
            echo -e "4. \e[92mRe-enable Monitoring\e[0m"
            echo -e "5. \e[93mFactory Reset\e[0m"
            echo -e "6. \e[93mExit\e[0m"
            read -p "Enter your choice (1/2/3/4/5/6): " choice
            case \$choice in
                1)
                    echo "Disabling monitoring..."
                    remove_cron_job
                    ;;
                2)
                    read -p "Enter a new device name: " new_device_name
                    set_device_name "\$new_device_name"
                    ;;
                3)
                    check_existing_cron_job
                    ;;
                4)
                    re_enable_monitoring
                    ;;
                5)
                    uninstall_tool
                    ;;
                6)
                    echo "Closing the script. Goodbye!"
                    exit 0
                    ;;
                *)
                    echo "Invalid choice. Exiting..."
                    exit 1
                    ;;
            esac
        fi
    fi
}

# Function to set the device name
set_device_name() {
    device_name="\$1"
    if [ -n "\$device_name" ]; then
        echo "\$device_name" > "\$STATE_FILE"
        echo "Device name set to: \$device_name"
        register_cron_job
    else
        echo "Invalid device name. Please provide a valid device name."
    fi
}

# Function to check if a job is already registered in crontab
check_existing_cron_job() {
    DEVICE_NAME=\$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        crontab -l | grep -q "\$DEVICE_NAME"
        if [ \$? -eq 0 ]; then
            echo -e "The device monitoring job for '\e[32m\$DEVICE_NAME\e[0m' is registered in crontab."
        else
            echo -e "The device monitoring job for '\e[91m\$DEVICE_NAME\e[0m' is not registered in crontab."
        fi
    fi
}

# Function to remove the cron job associated with the device
remove_cron_job() {
    DEVICE_NAME=\$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        crontab -l | grep -v "\$DEVICE_NAME" | crontab -
        echo -e "Monitoring for '\e[91m\$DEVICE_NAME\e[0m' has been disabled."
    else
        echo "No device name found to disable monitoring."
    fi
}

# Function to re-enable monitoring
re_enable_monitoring() {
    remove_cron_job
    DEVICE_NAME=\$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        register_cron_job
        echo -e "Monitoring for '\e[32m\$DEVICE_NAME\e[0m' has been re-enabled."
    else
        echo "No device name found to re-enable monitoring."
    fi
}

# Function to register a cron job for the device
register_cron_job() {
    DEVICE_NAME=\$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        (crontab -l 2>/dev/null; echo "* * * * * /usr/bin/curl -I -s -o /dev/null \"\$BASE_URL\$DEVICE_NAME\"") | crontab -
        echo -e "Monitoring for '\e[32m\$DEVICE_NAME\e[0m' has been registered."
    else
        echo "No device name found to register monitoring."
    fi
}

# Function to uninstall the tool
uninstall_tool() {
    echo "Uninstalling the tool..."
    remove_cron_job
    rm -f "\$STATE_FILE"
    echo "Tool uninstalled. All associated cron jobs and the config file have been removed."
    exit 0
}

# Main script logic
check_existing_job

if [ ! -f "\$STATE_FILE" ]; then
    echo -e "\e[1mLifecycle Ping Script v0.1 - Philipp Lehnet, 2024\e[0m"
    echo "---------------------"
    read -p "Enter the device name to monitor: " device_name
    set_device_name "\$device_name"
fi

echo "All operations executed. Have a nice day!"
EOT;
# Write the script to the file
file_put_contents($ubuntu_script_path, $script_content);

# Adjust r+w permissions
chmod($ubuntu_script_path, 0700);
echo "The script 'manager-ubuntu.sh' has been created in the 'deployment' directory.\n";


####################
# Manager Script MacOS
# MacOS
####################

$macos_script_path = $deployment_dir . '/' . $macos_manager_script;
# MacOS Manager Script Template
$script_content = <<<EOT
#!/bin/bash

# Base URL
BASE_URL="$script_url"

# File to store the device name and job status
STATE_FILE="\$HOME/device_monitor_state.txt"

# Function to display current status
display_status() {
    if [ -f "\$STATE_FILE" ]; then
        DEVICE_NAME=$(cat "\$STATE_FILE")
        if [ -n "\$DEVICE_NAME" ]; then
            echo -e "\033[1mMonitoring Status\033[0m"
            echo "---------------------"
            echo -e "Current Device Name: \033[32m\$DEVICE_NAME\033[0m"
            check_existing_cron_job
            echo "---------------------"
        fi
    else
        echo -e "\033[1mLifecycle Ping Script v0.1 - Philipp Lehnet, 2024\033[0m"
        echo "---------------------"
        echo -e "\033[31mNo device is currently being monitored.\033[0m"
    fi
}

# Function to check if a job is already registered
check_existing_job() {
    display_status
    if [ -f "\$STATE_FILE" ]; then
        DEVICE_NAME=$(cat "\$STATE_FILE")
        if [ -n "\$DEVICE_NAME" ]; then
            echo -e "1. [91mDisable Monitoring[0m"
            echo -e "2. [96mSet a New Device Name[0m"
            echo -e "3. [34mCheck Job Registration[0m"
            echo -e "4. [92mRe-enable Monitoring[0m"
            echo -e "5. [93mFactory Reset[0m"
            echo -e "6. [93mExit[0m"
            read -p "Enter your choice (1/2/3/4/5/6): " choice
            case \$choice in
                1)
                    echo "Disabling monitoring..."
                    remove_cron_job
                    ;;
                2)
                    read -p "Enter a new device name: " new_device_name
                    set_device_name "\$new_device_name"
                    ;;
                3)
                    check_existing_cron_job
                    ;;
                4)
                    re_enable_monitoring
                    ;;
                5)
                    uninstall_tool
                    ;;
                6)
                    echo "Closing the script. Goodbye!"
                    exit 0
                    ;;
                *)
                    echo "Invalid choice. Exiting..."
                    exit 1
                    ;;
            esac
        fi
    fi
}


# Function to set the device name
set_device_name() {
    device_name="$1"
    if [ -n "\$device_name" ]; then
        echo "\$device_name" > "\$STATE_FILE"
        echo "Device name set to: \$device_name"
        register_cron_job
    else
        echo "Invalid device name. Please provide a valid device name."
    fi
}

# Function to check if a job is already registered in crontab
check_existing_cron_job() {
    DEVICE_NAME=$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        crontab -l | grep -q "\$DEVICE_NAME"
        if [ $? -eq 0 ]; then
            echo -e "The device monitoring job for '\033[32m\$DEVICE_NAME\033[0m' is registered in crontab."
        else
            echo -e "The device monitoring job for '\033[91m\$DEVICE_NAME\033[0m' is not registered in crontab."
        fi
    fi
}

# Function to remove the cron job associated with the device
remove_cron_job() {
    DEVICE_NAME=$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        (crontab -l | grep -v "\$DEVICE_NAME") | crontab -
        echo -e "Monitoring for '\033[91m\$DEVICE_NAME\033[0m' has been disabled."
    else
        echo "No device name found to disable monitoring."
    fi
}

# Function to re-enable monitoring
re_enable_monitoring() {
    remove_cron_job
    DEVICE_NAME=$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        register_cron_job
        echo -e "Monitoring for '\033[32m\$DEVICE_NAME\033[0m' has been re-enabled."
    else
        echo "No device name found to re-enable monitoring."
    fi
}

# Function to register a cron job for the device
register_cron_job() {
    DEVICE_NAME=$(cat "\$STATE_FILE")
    if [ -n "\$DEVICE_NAME" ]; then
        (crontab -l 2>/dev/null; echo "* * * * * /usr/bin/curl -I -s -o /dev/null \"\$BASE_URL\$DEVICE_NAME\"") | crontab -
        echo -e "Monitoring for '\033[32m\$DEVICE_NAME\033[0m' has been registered."
    else
        echo "No device name found to register monitoring."
    fi
}

# Function to uninstall the tool
uninstall_tool() {
    echo "Uninstalling the tool..."
    remove_cron_job
    rm -f "\$STATE_FILE"
    echo "Tool uninstalled. All associated cron jobs and the config file have been removed."
    exit 0
}

# Main script logic
check_existing_job

if [ ! -f "\$STATE_FILE" ]; then
    echo -e "\033[1mLifecycle Ping Script v0.1 - Philipp Lehnet, 2024\033[0m"
    echo "---------------------"
    read -p "Enter the device name to monitor: " device_name
    set_device_name "\$device_name"
fi

echo "All operations executed. Have a nice day!"
EOT;
# Write the script to the file
file_put_contents($macos_script_path, $script_content);

# Adjust r+w permissions
chmod($macos_script_path, 0700);
echo "The script 'manager-macos.sh' has been created in the 'deployment' directory.\n";


####################
# Windows Manager Script
# Windows (PowerShell)
####################

$windows_script_path = $deployment_dir . '/' . $windows_manager_script;
# Windows Manager Script Template - for now simple hello world
$script_content = <<<EOT
# Self-elevate the script if required
if (-Not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] 'Administrator')) {
    if ([int](Get-CimInstance -Class Win32_OperatingSystem | Select-Object -ExpandProperty BuildNumber) -ge 6000) {
        \$CommandLine = "-File `"" + \$MyInvocation.MyCommand.Path + "`" " + \$MyInvocation.UnboundArguments
        Start-Process -FilePath PowerShell.exe -Verb Runas -ArgumentList \$CommandLine
        Exit
    }
}
# Base URL
\$BASE_URL = "$script_url"

# File to store the device name and job status
\$STATE_FILE = "\$HOME\device_monitor_state.txt"

# Function to display current status
function Display-Status {
    if (Test-Path \$STATE_FILE) {
        \$DEVICE_NAME = Get-Content \$STATE_FILE
        if (\$DEVICE_NAME) {
            Write-Host "Monitoring Status" -ForegroundColor Green
            Write-Host "---------------------"
            Write-Host "Current Device Name: \$DEVICE_NAME" -ForegroundColor Green
            Check-Existing-Task
            Write-Host "---------------------"
        }
    } else {
        Write-Host "Lifecycle Ping Script v0.1 - Philipp Lehnet, 2024" -ForegroundColor Green
        Write-Host "---------------------"
        Write-Host "No device is currently being monitored." -ForegroundColor Red
    }
}

# Function to check if a job is already registered
function Check-Existing-Job {
    Display-Status
    if (Test-Path \$STATE_FILE) {
        \$DEVICE_NAME = Get-Content \$STATE_FILE
        if (\$DEVICE_NAME) {
            Write-Host "1. Disable Monitoring" -ForegroundColor Red
            Write-Host "2. Set a New Device Name" -ForegroundColor Cyan
            Write-Host "3. Check Job Registration" -ForegroundColor Blue
            Write-Host "4. Re-enable Monitoring" -ForegroundColor Green
            Write-Host "5. Factory Reset" -ForegroundColor Yellow
            Write-Host "6. Exit" -ForegroundColor Yellow
            \$choice = Read-Host "Enter your choice (1/2/3/4/5/6)"
            switch (\$choice) {
                1 { Write-Host "Disabling monitoring..."; Remove-Cron-Job }
                2 { \$new_device_name = Read-Host "Enter a new device name"; Set-Device-Name \$new_device_name }
                3 { Check-Existing-Task }
                4 { Re-Enable-Monitoring }
                5 { Uninstall-Tool }
                6 { Write-Host "Closing the script. Goodbye!"; exit }
                default { Write-Host "Invalid choice. Exiting..."; exit }
            }
        }
    }
}

# Function to set the device name
function Set-Device-Name {
    param ([string]\$device_name)
    Write-Host "Removing existing cron job..." -ForegroundColor Yellow
    Remove-Cron-Job
    Write-Host "Setting device name..." -ForegroundColor Yellow
    if (\$device_name) {
        Set-Content -Path \$STATE_FILE -Value \$device_name
        Write-Host "Device name set to: \$device_name"
        Register-Cron-Job
    } else {
        Write-Host "Invalid device name. Please provide a valid device name." -ForegroundColor Red
    }
}

# Function to check if a job is already registered in Task Scheduler
function Check-Existing-Task {
    \$DEVICE_NAME = Get-Content \$STATE_FILE
    if (\$DEVICE_NAME) {
        \$task = Get-ScheduledTask | Where-Object { \$_.TaskName -eq \$DEVICE_NAME }
        if (\$task) {
            Write-Host "The device monitoring job for '\$DEVICE_NAME' is registered in Task Scheduler." -ForegroundColor Green
        } else {
            Write-Host "The device monitoring job for '\$DEVICE_NAME' is not registered in Task Scheduler." -ForegroundColor Red
        }
    }
}

# Function to remove the task associated with the device
function Remove-Cron-Job {
    Write-Host "Removing cron job..." -ForegroundColor Yellow
    \$DEVICE_NAME = Get-Content \$STATE_FILE
    if (\$DEVICE_NAME) {
        Unregister-ScheduledTask -TaskName \$DEVICE_NAME -Confirm:\$false 2>\$null
        Write-Host "Monitoring for '\$DEVICE_NAME' has been disabled." -ForegroundColor Red
    } else {
        Write-Host "No device name found to disable monitoring." -ForegroundColor Red
    }
}

# Function to re-enable monitoring
function Re-Enable-Monitoring {
    Write-Host "Re-enabling monitoring..." -ForegroundColor Yellow
    Remove-Cron-Job
    \$DEVICE_NAME = Get-Content \$STATE_FILE
    if (\$DEVICE_NAME) {
        Register-Cron-Job
        Write-Host "Monitoring for '\$DEVICE_NAME' has been re-enabled." -ForegroundColor Green
    } else {
        Write-Host "No device name found to re-enable monitoring." -ForegroundColor Red
    }
}

# Function to register a task for the device
function Register-Cron-Job {
    Write-Host "Registering cron job..." -ForegroundColor Yellow
    \$DEVICE_NAME = Get-Content \$STATE_FILE
    if (\$DEVICE_NAME) {
        \$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-Command `"Invoke-WebRequest -Uri '\$BASE_URL\$DEVICE_NAME'`""
        \$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1)
        \$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount
        \$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
        Register-ScheduledTask -TaskName \$DEVICE_NAME -Action \$action -Trigger \$trigger -Principal \$principal -Settings \$settings
        Write-Host "Monitoring for '\$DEVICE_NAME' has been registered." -ForegroundColor Green
    } else {
        Write-Host "No device name found to register monitoring." -ForegroundColor Red
    }
}

# Function to uninstall the tool
function Uninstall-Tool {
    Write-Host "Uninstalling the tool..." -ForegroundColor Yellow
    Remove-Cron-Job
    Remove-Item -Path \$STATE_FILE -Force
    Write-Host "Tool uninstalled. All associated tasks and the config file have been removed."
    exit
}

# Main script logic
if (-not (Test-Path \$STATE_FILE)) {
    Write-Host "Lifecycle Ping Script v0.1 - Philipp Lehnet, 2024" -ForegroundColor Green
    Write-Host "---------------------"
    \$device_name = Read-Host "Enter the device name to monitor"
    Set-Device-Name \$device_name
} else {
    Check-Existing-Job
}

Write-Host "All operations executed. Have a nice day!"
EOT;
# Write the script to the file
file_put_contents($windows_script_path, $script_content);

# Adjust r+w permissions
chmod($windows_script_path, 0700);
echo "The script 'manager-windows.ps1' has been created in the 'deployment' directory.\n";

echo "Deployment scripts have been generated successfully.\n";

?>
