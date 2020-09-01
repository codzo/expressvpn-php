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
    $next_location = '';
    while (($next = next($location_list))) {
        if ($next == $location) {
            $next_location = next($location_list) ;
            if (!$next_location) {
                // we come to the last location
                $next_location = first($location_list);
            }
            break;
        }
    }
    $ev->connect($next_location);
}
