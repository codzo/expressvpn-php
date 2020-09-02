#!/usr/bin/php
<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Codzo\ExpressVPN\ExpressVPN;
use Codzo\Config\Config;

chdir(__DIR__ . '/..');

$ev = new ExpressVPN(new Config());
if (!$ev->isConnected()) {
    $ev->connect();
} else {
    $location = $ev->getConnectedLocation();
    $location_list = array_keys($ev->getAllLocations());

    // find the location we are connected to
    while (($next = next($location_list))) {
        if ($next == $location) {
            break;
        }
    }

    if($next) {
        // now pointing at current location
        $next = next($location_list);
    }

    if(!$next) {
        // come to the end of list, default to 'smart'
        $next = 'smart';
    } 
    $ev->connect($next);
}

$location = $ev->getConnectedLocation(true);

echo date('c') . ' ExpressVPN: connect to ' . $location . PHP_EOL;
