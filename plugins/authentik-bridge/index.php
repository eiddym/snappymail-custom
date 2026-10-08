<?php
class AuthentikBridgePlugin extends \RainLoop\Plugins\AbstractPlugin
{
    const NAME = 'Authentik Bridge';
    const VERSION = '0.1.0';
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
        $user = \strtolower(\trim($sNewEmail));
        $local = \explode('@', $user)[0];

        if (empty($local) || empty($sPassword)) {
            $this->WriteLog('AuthentikBridge: Empty username or password provided', \LOG_WARNING);
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthFailed);
        }

        $ldapHost = \getenv('AUTHENTIK_LDAP_HOST') ?: 'ldap://authentik-ldap-outpost:3389';
        $baseDn = \getenv('AUTHENTIK_LDAP_BASE_DN') ?: 'ou=users,dc=marabuntarl,dc=com';

        $ds = @\ldap_connect($ldapHost);
        if (!$ds) {
            $this->WriteLog("AuthentikBridge: Could not connect to LDAP server at {$ldapHost}", \LOG_ERR);
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::ServiceUnavailable);
        }

        \ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        \ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $dn = "cn={$local},{$baseDn}";
        $bind = @\ldap_bind($ds, $dn, $sPassword);

        if (!$bind) {
            $this->WriteLog("AuthentikBridge: LDAP bind failed for DN {$dn}", \LOG_NOTICE);
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthFailed);
        }

        $this->WriteLog("AuthentikBridge: LDAP bind successful for {$user}", \LOG_INFO);
    }

    public function InjectHostingerPassword(&$sNewEmail, &$sNewImapUser, &$sPassword, &$sNewSmtpUser): void
    {
        $user = \strtolower(\trim($sNewEmail));
        $realPassword = $this->getHostingerPassword($user);

        if (empty($realPassword)) {
            $this->WriteLog("AuthentikBridge: No Hostinger credential found for {$user}", \LOG_ERR);
            throw new \RainLoop\Exceptions\ClientException(\RainLoop\Notifications::AuthFailed);
        }

        $sPassword = $realPassword;
        $sNewImapUser = $user;
        $sNewSmtpUser = $user;
        $this->WriteLog("AuthentikBridge: Successfully injected Hostinger password for {$user}", \LOG_INFO);
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
            $this->WriteLog('AuthentikBridge: Invalid master key length', \LOG_ERR);
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
}
