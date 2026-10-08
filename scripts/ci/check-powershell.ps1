$ErrorActionPreference = 'Stop'
$failed = $false
Get-ChildItem scripts -Recurse -Filter '*.ps1' | ForEach-Object {
    $tokens = $null
    $parseErrors = $null
    $null = [System.Management.Automation.Language.Parser]::ParseFile($_.FullName, [ref]$tokens, [ref]$parseErrors)
    if ($parseErrors.Count) {
        $parseErrors | ForEach-Object { Write-Output $_.Message }
        $failed = $true
    } else { Write-Output "Sintaxe válida: $($_.Name)" }
}
if ($failed) { exit 1 }
