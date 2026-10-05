<?php
require_once __DIR__ . '/../config/session.php';

// Hapus semua variabel sesi
session_unset(); 

// Hancurkan sesi sepenuhnya dari server
session_destroy(); 

// Kembalikan pengguna ke halaman login
header("Location: login.php");
exit();
?>