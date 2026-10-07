<?php
if (PHP_SAPI !== 'cli') {
    die("Solo ejecutable vía CLI\n");
}

$email = \strtolower(\trim($argv[1] ?? ''));
$password = $argv[2] ?? '';

if (empty($email) || empty($password)) {
    \fwrite(STDERR, "Uso: php load-credentials.php usuario@dominio.com PasswordHostinger\n");
    exit(1);
}

$masterKeyB64 = \getenv('CREDENTIAL_MASTER_KEY') ?: 'aOFx40L6HDvy4GfRdAjkVvtOa6Ph7W1s776oMRm0DA8=';
$masterKey = \base64_decode($masterKeyB64);

if (\strlen($masterKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    \fwrite(STDERR, "Error: CREDENTIAL_MASTER_KEY debe ser de 32 bytes en base64\n");
    exit(1);
}

$storePath = \getenv('SNAPPYMAIL_CREDENTIAL_STORE') ?: '/var/lib/snappymail/_data_/_default_/hostinger-credentials.json';
$dir = \dirname($storePath);
if (!\is_dir($dir)) {
    \mkdir($dir, 0755, true);
}

$data = \file_exists($storePath) ? (\json_decode(\file_get_contents($storePath), true) ?: []) : [];

$nonce = \random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
$encrypted = \sodium_crypto_secretbox($password, $nonce, $masterKey);

$data[$email] = \base64_encode($nonce . $encrypted);

\file_put_contents($storePath, \json_encode($data, JSON_PRETTY_PRINT));
\chmod($storePath, 0600);

echo "✓ Credencial cifrada y guardada con éxito para {$email}\n";
