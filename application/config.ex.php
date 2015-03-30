<?php

date_default_timezone_set('America/New_York');
require_once 'application/flight/Flight.php';

// Autoload models and controllers
Flight::path('application/models');
Flight::path('application/controllers');

// Set views path and environment
Flight::set('flight.views.path', 'application/views');
Flight::set('root_dir', dirname(__FILE__));
Flight::set('base_url', '/Division-Tracker/');

// Set database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

Flight::register('aod', 'Database', array('aod'));