<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user'] = ['id' => 1, 'role' => 'admin', 'full_name' => 'Test User'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/PearlCare/dashboard';
require 'config/config.php';
require 'core/Controller.php';
require 'app/Controllers/DashboardController.php';
(new DashboardController())->index();
