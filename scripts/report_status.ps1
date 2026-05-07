param(
    [string]$ApiUrl = "http://localhost:8080/api/report.php",
    [string]$ApiKey = "change-me-demo-key",
    [string]$Client = $env:TRMM_CLIENT_NAME,
    [string]$Site = $env:TRMM_SITE_NAME
)

$hostname = $env:COMPUTERNAME
$os = (Get-CimInstance Win32_OperatingSystem).Caption

# Placeholder collection logic. Replace with real Windows Update query in your environment.
$payload = @{
    hostname = $hostname
    trmm_client = $Client
    trmm_site = $Site
    os_version = $os
    last_scan_time = (Get-Date).ToUniversalTime().ToString("o")
    reboot_required = $false
    compliance = "compliant"
    missing_updates = @()
}

$json = $payload | ConvertTo-Json -Depth 5
Invoke-RestMethod -Method Post -Uri $ApiUrl -Headers @{ "X-API-Key" = $ApiKey } -ContentType "application/json" -Body $json
