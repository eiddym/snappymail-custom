<?php
class AuthentikBridgePlugin extends \RainLoop\Plugins\AbstractPlugin
{
    const NAME = 'Authentik Bridge';
    const VERSION = '0.1.1';
    const REQUIRED = '2.36.0';
    const CATEGORY = 'Auth';
    const DESCRIPTION = 'Authenticates against Authentik LDAP Outpost and maps Hostinger IMAP credentials';

    public function Init(): void
    {
        $this->addHook('login.credentials.step-2', 'ValidateAuthentik');
        $this->addHook('login.credentials', 'InjectHostingerPassword');
    }

    public function ValidateAuthentik(&$sNewEmail, &$sPassword): void
    {
        $input = \strtolower(\trim($sNewEmail));
        $local = \explode('@', $input)[0];

        if (empty($local) || empty($sPassword)) {
            $this->writeLog('AuthentikBridge: Empty username or password provided');
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthError);
        }

        // Automatic domain completion if user typed short username (e.g. "ariel.ayaviri")
        $fullEmail = \str_contains($input, '@') ? $input : $local . '@marabuntarl.com';
        $sNewEmail = $fullEmail;

        $ldapHost = \getenv('AUTHENTIK_LDAP_HOST') ?: 'ldap://authentik-ldap-outpost:3389';
        $baseDn = \getenv('AUTHENTIK_LDAP_BASE_DN') ?: 'ou=users,dc=marabuntarl,dc=com';

        $ds = @\ldap_connect($ldapHost);
        if (!$ds) {
            $this->writeLog("AuthentikBridge: Could not connect to LDAP server at {$ldapHost}");
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::ServiceUnavailable);
        }

        \ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        \ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $dn = "cn={$local},{$baseDn}";
        $bind = @\ldap_bind($ds, $dn, $sPassword);

        if (!$bind) {
            $this->writeLog("AuthentikBridge: LDAP bind failed for DN {$dn}");
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthError);
        }

        $this->writeLog("AuthentikBridge: LDAP bind successful for {$fullEmail}");
    }

    public function InjectHostingerPassword(&$sNewEmail, &$sNewImapUser, &$sPassword, &$sNewSmtpUser): void
    {
        $input = \strtolower(\trim($sNewEmail));
        $local = \explode('@', $input)[0];
        $fullEmail = \str_contains($input, '@') ? $input : $local . '@marabuntarl.com';
        $sNewEmail = $fullEmail;

        $realPassword = $this->getHostingerPassword($fullEmail);

        if (empty($realPassword)) {
            $this->writeLog("AuthentikBridge: No Hostinger credential found for {$fullEmail}");
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthError);
        }

        $sPassword = $realPassword;
        $sNewImapUser = $fullEmail;
        $sNewSmtpUser = $fullEmail;
        $this->writeLog("AuthentikBridge: Successfully injected Hostinger password for {$fullEmail}");
    }

    private function getHostingerPassword(string $email): string
    {
        $storePath = \getenv('SNAPPYMAIL_CREDENTIAL_STORE') ?: '/var/lib/snappymail/_data_/_default_/hostinger-credentials.json';
        if (!\file_exists($storePath)) {
            return '';
        }

        $content = \file_get_contents($storePath);
        $data = \json_decode($content, true);
        if (!\is_array($data) || !isset($data[$email])) {
            return '';
        }

        $masterKeyB64 = \getenv('CREDENTIAL_MASTER_KEY') ?: 'aOFx40L6HDvy4GfRdAjkVvtOa6Ph7W1s776oMRm0DA8=';
        $masterKey = \base64_decode($masterKeyB64);
        if (\strlen($masterKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            $this->writeLog('AuthentikBridge: Invalid master key length');
            return '';
        }

        $payload = \base64_decode($data[$email]);
        $nonceBytes = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (\strlen($payload) <= $nonceBytes) {
            return '';
        }

        $nonce = \substr($payload, 0, $nonceBytes);
        $ciphertext = \substr($payload, $nonceBytes);

        $decrypted = \sodium_crypto_secretbox_open($ciphertext, $nonce, $masterKey);
        return $decrypted !== false ? $decrypted : '';
    }

    private function writeLog(string $message): void
    {
        try {
            if (\class_exists('\RainLoop\Api') && \RainLoop\Api::Actions() && \RainLoop\Api::Actions()->Logger()) {
                \RainLoop\Api::Actions()->Logger()->WriteLog($message);
            }
        } catch (\Throwable $e) {
            // Ignore logging errors
        }
    }
}
