<?php

//require_once('vendor/mikecao/flight/flight.php');
//require_once('vendor/github/GitHubClient.php');
require_once 'vendor/autoload.php';
require_once 'application/config.php';
require_once 'application/functions.php';



ini_set('session.save_path', realpath(dirname($_SERVER['DOCUMENT_ROOT']) . '/../session'));
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

session_start();

if (get_magic_quotes_gpc()) {
  $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
  while (list($key, $val) = each($process)) {
    foreach ($val as $k => $v) {
      unset($process[$key][$k]);
      if (is_array($v)) {
        $process[$key][stripslashes($k)] = $v;
        $process[] = &$process[$key][stripslashes($k)];
      } else {
        $process[$key][stripslashes($k)] = stripslashes($v);
      }
    }
  }
  unset($process);
}

require_once 'application/routes.php';
Flight::start();
