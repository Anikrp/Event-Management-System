<?php
session_start();
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit();
