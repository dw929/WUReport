param(
    [string]$ApiUrl = "http://localhost:8080/api/report.php",
    [string]$ApiKey = "change-me-demo-key",
    [string]$Client = $env:TRMM_CLIENT_NAME,
    [string]$Site = $env:TRMM_SITE_NAME
)

$hostname = $env:COMPUTERNAME
$os = (Get-CimInstance Win32_OperatingSystem).Caption
$scanTime = (Get-Date).ToUniversalTime().ToString("o")

$missingUpdates = @()
$installedUpdates = @()
$compliance = "unknown"
$rebootRequired = $false
$statusMessage = "Scan not started"

try {
    $session = New-Object -ComObject Microsoft.Update.Session

    # Query updates that are applicable but not yet installed.
    $searcher = $session.CreateUpdateSearcher()
    $missingResult = $searcher.Search("IsInstalled=0 and IsHidden=0")

    foreach ($update in $missingResult.Updates) {
        $kb = @($update.KBArticleIDs) -join ','
        if ([string]::IsNullOrWhiteSpace($kb)) {
            $kb = "N/A"
        }

        $severity = if ([string]::IsNullOrWhiteSpace($update.MsrcSeverity)) {
            "unknown"
        } else {
            $update.MsrcSeverity
        }

        $missingUpdates += @{
            kb = $kb
            title = $update.Title
            severity = $severity
        }
    }

    # Query updates that are already installed.
    $installedResult = $searcher.Search("IsInstalled=1 and IsHidden=0")
    foreach ($update in $installedResult.Updates) {
        $kb = @($update.KBArticleIDs) -join ','
        if ([string]::IsNullOrWhiteSpace($kb)) {
            $kb = "N/A"
        }

        $installedUpdates += @{
            kb = $kb
            title = $update.Title
        }
    }

    $systemInfo = New-Object -ComObject Microsoft.Update.SystemInfo
    $rebootRequired = [bool]$systemInfo.RebootRequired

    if ($missingUpdates.Count -eq 0 -and -not $rebootRequired) {
        $compliance = "compliant"
    } elseif ($missingUpdates.Count -eq 0 -and $rebootRequired) {
        $compliance = "pending_reboot"
    } else {
        $compliance = "non_compliant"
    }

    $statusMessage = "Scan completed. Missing: $($missingUpdates.Count), Installed: $($installedUpdates.Count), RebootRequired: $rebootRequired"
} catch {
    $compliance = "scan_error"
    $statusMessage = "Windows Update scan failed: $($_.Exception.Message)"
}

$payload = @{
    hostname = $hostname
    trmm_client = $Client
    trmm_site = $Site
    os_version = $os
    last_scan_time = $scanTime
    reboot_required = $rebootRequired
    compliance = $compliance
    status = $statusMessage
    installed_updates_count = $installedUpdates.Count
    missing_updates_count = $missingUpdates.Count
    missing_updates = $missingUpdates
}

$json = $payload | ConvertTo-Json -Depth 6
Invoke-RestMethod -Method Post -Uri $ApiUrl -Headers @{ "X-API-Key" = $ApiKey } -ContentType "application/json" -Body $json
