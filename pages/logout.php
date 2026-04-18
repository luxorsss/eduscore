<?php
session_start(); // Mulai sesi untuk mengenalinya

// Hapus semua variabel sesi
session_unset(); 

// Hancurkan sesi sepenuhnya dari server
session_destroy(); 

// Kembalikan pengguna ke halaman login
header("Location: login.php");
exit();
?>