<?php
require_once 'SessionGuard.php';

$guard = new SessionGuard(); // Initialization with default parameters
$guard->start();             // Safe session launch

// Working with a session
$_SESSION['user'] = 'Tetiana';
echo 'Hello, ' . $_SESSION['user'] . PHP_EOL;

// Checking free space
echo $guard->getFreeSpaceMB() . PHP_EOL;

// Path diagnostics
$guard->diagnose('/tmp');
