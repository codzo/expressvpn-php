<?php
namespace Codzo\ExpressVPN\Exception;

class InvalidLocationException extends \Exception
{
    public function __construct($location, $locations_list=null)
    {
        $msg = "Invalid location: $location" ;
        if(is_array($locations_list)) {
            $msg .= '. Possible locations: '
                . implode(',', array_keys($locations_list)) ;
        }
        parent::__construct($msg);
    }
}
