<?php
// Configuration File
// Questo file contiene credenziali sensibili. 
// NON COMMITTARLO MAI su GitHub/GitLab.

// API KEYS
define('GEMINI_API_KEY', 'AIzaSyA-FCtkZUETvNFlKUs0Zj5rAVA-uVTdans');
define('CAL_API_KEY', 'cal_live_91bd0d4c0256ce06faca4395d055c769');
define('CAL_EVENT_ID', '4249403');

// EMAILS
define('REPORT_EMAIL', 'info@davidefiore.com, fio.davide@gmail.com');

// Ensure this file cannot be accessed directly via URL
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
?>
