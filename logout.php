<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Auth.php';

Auth::logout();
header('Location: login.php');
exit; 