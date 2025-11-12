# SessionGuard

    A minimalist PHP module for safe session startup and automatic cleanup of stale session files. Designed for transparent integration into systems that prioritize control, predictability, and disk space protection.

## Purpose
    SessionGuard addresses:
    • 	Safe session initialization without repeated starts
    • 	Cleanup of outdated `sess_*` files (default: older than 3 days)
    • 	Prevention of site crashes due to disk overflow
    • 	Logging of errors and environment diagnostics

## Features
    • 	Clean architecture with no external dependencies
    • 	Automatic deletion of expired session files
    • 	Writable path verification and disk space checks
    • 	Fallback paths if the primary session path is unavailable
    • 	Logging via `error_log()` when enabled

## Usage
    ```php
    require_once 'SessionGuard.php';

    $guard = new SessionGuard();
    $guard->start();

    // Work with $_SESSION directly
    $_SESSION['user'] = 'Tetiana';

    echo $guard->getFreeSpaceMB();
    $guard->diagnose('/tmp');

## Structure
	• SessionGuard.php — core module  
	• README.md — documentation  
	• examples.php — usage examples and tests  
	• LICENSE — license terms (MIT)
    
## License
    This project is licensed under the MIT License. See LICENSE for details.

## Author
    Tetiana N.
    Architectural clarity and control — in every line.
