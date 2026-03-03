<?php
session_start();
require 'config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home.php';

if (preg_match('/\.\.\//', $page)) {
    die('Directory traversal is not allowed');
}

if (preg_match('/^\/etc\//', $page)) {
    die('Access to /etc/ is restricted');
}

include $page;
?>
