<?php
use App\Controllers\HomeController;

require_once __DIR__ . '/../vendor/autoload.php';

$controller = new HomeController();
$controller->index();