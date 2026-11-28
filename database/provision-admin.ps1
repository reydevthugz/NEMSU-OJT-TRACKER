$ErrorActionPreference = 'Stop'

$phpCommand = Get-Command php -CommandType Application -ErrorAction SilentlyContinue
if ($null -eq $phpCommand) {
    Write-Error 'PHP CLI was not found. Open a Laragon terminal and retry.'
    exit 1
}

function Convert-SecureStringToPlainText([System.Security.SecureString] $Value) {
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($Value)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    } finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    }
}

$securePassword = $null
$secureConfirmation = $null
$password = $null
$confirmation = $null
$payloadJson = $null
$payloadBase64 = $null
$exitCode = 1

try {
    $name = Read-Host 'Administrator name'
    $email = Read-Host 'Administrator email'
    $securePassword = Read-Host -Prompt 'Administrator password (hidden)' -AsSecureString
    $secureConfirmation = Read-Host -Prompt 'Confirm administrator password (hidden)' -AsSecureString

    $password = Convert-SecureStringToPlainText $securePassword
    $confirmation = Convert-SecureStringToPlainText $secureConfirmation
    if (-not [String]::Equals($password, $confirmation, [StringComparison]::Ordinal)) {
        throw 'Password confirmation does not match.'
    }

    $payload = [ordered]@{
        name = $name
        email = $email
        password = $password
    }
    $payloadJson = ConvertTo-Json -InputObject $payload -Compress
    $payloadBase64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($payloadJson))

    $phpCode = @'
require_once getcwd() . '/app/bootstrap.php';
require_once getcwd() . '/app/models/AdministratorProvisioner.php';
try {
    $encoded = trim((string) stream_get_contents(STDIN));
    $json = base64_decode($encoded, true);
    if ($json === false) throw new InvalidArgumentException('Invalid provisioning input.');
    $payload = json_decode($json, true, 8, JSON_THROW_ON_ERROR);
    $id = (new AdministratorProvisioner(database_connection()))->createFirstAdministrator((string)($payload['name'] ?? ''), (string)($payload['email'] ?? ''), (string)($payload['password'] ?? ''));
    echo 'First administrator created successfully (user ID ' . $id . '). You can now sign in.' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
'@
    $phpCodeBase64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($phpCode))
    $payloadBase64 | & $phpCommand.Source '-r' "eval(base64_decode('$phpCodeBase64'));"
    $exitCode = $LASTEXITCODE
} catch {
    [Console]::Error.WriteLine($_.Exception.Message)
    $exitCode = 1
} finally {
    if ($null -ne $securePassword) {
        $securePassword.Dispose()
    }
    if ($null -ne $secureConfirmation) {
        $secureConfirmation.Dispose()
    }
    $password = $null
    $confirmation = $null
    $payload = $null
    $payloadJson = $null
    $payloadBase64 = $null
}

exit $exitCode
