[CmdletBinding()]
param(
    [string]$ProjectPath = 'C:\Apps\GestaoAtivos\sistema',
    [string]$PhpPath = 'C:\Apps\GestaoAtivos\php\php.exe',
    [string]$SiteName = 'GestaoAtivos'
)
$ErrorActionPreference = 'Stop'
$failures = [System.Collections.Generic.List[string]]::new()
function Check-Condition([bool]$Condition, [string]$Name) {
    if ($Condition) { Write-Output "OK: $Name" }
    else { Write-Output "FALHA: $Name"; $failures.Add($Name) }
}
Check-Condition (Test-Path -LiteralPath $PhpPath) 'Executável PHP'
foreach ($relative in @('artisan', '.env', 'vendor\autoload.php', 'public\index.php', 'public\web.config', 'storage', 'bootstrap\cache')) {
    Check-Condition (Test-Path -LiteralPath (Join-Path $ProjectPath $relative)) $relative
}
$envPath = Join-Path $ProjectPath '.env'
if (Test-Path -LiteralPath $envPath) {
    # Nunca imprimir valores do arquivo de ambiente.
    $lines = Get-Content -LiteralPath $envPath
    Check-Condition ([bool]($lines -match '^APP_ENV=production\s*$')) 'APP_ENV=production'
    Check-Condition ([bool]($lines -match '^APP_DEBUG=false\s*$')) 'APP_DEBUG=false'
    Check-Condition ([bool]($lines -match '^APP_KEY=base64:.+')) 'APP_KEY preenchida'
}
if (Test-Path -LiteralPath $PhpPath) {
    $modules = & $PhpPath -m
    Check-Condition ($LASTEXITCODE -eq 0) 'Inicialização PHP'
    foreach ($module in @('pdo_pgsql', 'mbstring', 'openssl', 'fileinfo', 'curl', 'gd', 'zip')) {
        Check-Condition ($modules -contains $module) "Extensão $module"
    }
}
try {
    Import-Module WebAdministration -ErrorAction Stop
    $site = Get-Website -Name $SiteName -ErrorAction Stop
    Check-Condition ($null -ne $site) 'Site IIS existe'
    if ($site) {
        $expected = [IO.Path]::GetFullPath((Join-Path $ProjectPath 'public')).TrimEnd('\')
        $actual = [IO.Path]::GetFullPath([Environment]::ExpandEnvironmentVariables($site.physicalPath)).TrimEnd('\')
        Check-Condition ($expected -eq $actual) 'Raiz IIS aponta para public'
        Check-Condition ($site.State -eq 'Started') 'Site iniciado'
        Check-Condition ($site.applicationPool -eq $SiteName) 'Pool exclusivo com nome do site'
    }
} catch {
    Check-Condition $false 'Consulta do IIS (executar como administrador no servidor)'
}
if ($failures.Count) { Write-Output "$($failures.Count) verificação(ões) falharam."; exit 1 }
Write-Output 'Pré-checagem concluída. Não substitui teste HTTP, login e restauração do backup.'
