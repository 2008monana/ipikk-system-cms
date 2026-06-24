<?php
/**
 * Configuração centralizada de sessões/cookies - IPIKK
 */

if (!function_exists('ipikkRequestIsHttps')) {
    function ipikkRequestIsHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strtolower((string)$forwardedProto) === 'https') {
            return true;
        }

        $forwardedSsl = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? '';
        return strtolower((string)$forwardedSsl) === 'on';
    }
}

if (!function_exists('iniciarSessaoIpikk')) {
    function iniciarSessaoIpikk(int $lifetime = 0): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $secure = ipikkRequestIsHttps();
        $params = [
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        session_set_cookie_params($params);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');

        if ($lifetime > 0) {
            ini_set('session.cookie_lifetime', (string)$lifetime);
            ini_set('session.gc_maxlifetime', (string)$lifetime);
        }

        session_start();
    }
}
