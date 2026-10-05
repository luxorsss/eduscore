<?php
// config/session.php
// Konfigurasi durasi sesi panjang (30 hari) agar login tidak cepat kedaluwarsa

if (session_status() === PHP_SESSION_NONE) {
    // 30 hari dalam detik (30 * 24 * 3600 = 2.592.000 detik)
    $lifetime = 2592000;
    
    // Set garbage collection lifetime PHP ke 30 hari
    ini_set('session.gc_maxlifetime', (string)$lifetime);
    
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
                 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}
