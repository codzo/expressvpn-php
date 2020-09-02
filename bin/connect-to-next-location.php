#!/usr/bin/php
<?php
require_once(__DIR__ . '/../vendor/autoload.php');

use Codzo\ExpressVPN\ExpressVPN;
use Codzo\Config\Config;

chdir(__DIR__ . '/..');

$skip_list = array('in', 'smart');

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

    // now pointing at current location
    while (($next = next($location_list))) {
        if (!$next) {
            // we come to the last location
            $next = first($location_list);
        }

        // check location if to be skipped
        if(!in_array($next, $skip_list)) {
            break;
        }
    }
    $ev->connect($next);
}

$location = $ev->getConnectedLocation(true);

echo date('c') . ' ExpressVPN: connect to ' . $location . PHP_EOL;
