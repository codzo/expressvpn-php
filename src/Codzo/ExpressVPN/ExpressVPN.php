<?php
namespace Codzo\ExpressVPN;

use Codzo\ExpressVPN\Exception\InvalidLocationException;

class ExpressVPN
{
    /**
     * @var array the list of locations
     */
    protected static $locations_list = array();

    /**
     * @var string expressvpn cmd, default to 'expressvpn'
     */
    protected $cli;

    public function __construct($config=null)
    {
        $cli = $config->get('expressvpn.cli');
        if(!$cli) {
            $cli = 'expressvpn';
        }
        $this->cli = $cli;

        // get all available locations
        $this->getAllLocations();
    }
    /**
     * Connect to a location
     * No action when connected to same location; will disconnect if connect to
     * different location.
     * 
     * @param $location string the code of location
     * 
     * @return bool True when connected
     */
    public function connect($location='')
    {
        // check if location is valid
        if($location && !key_exists($location, $this->getAllLocations())) {
            throw new InvalidLocationException($location);
        }

        // check if already connected
        $status = $this->status();

        // already connected
        if($status['connected']) {
            // but want to connect to default or same location
            // do nothing
            if(!$location || $location==$status['location']) {
                $this->log(sprintf('Already connected to %s, no action', $status['location']));
                return 0;
            }

            // but want to connect to different location
            // disconnect for now
            $this->log(sprintf('Already connected to %s, disconnect now', $status['location']));
            $this->disconnect();
        }

        return $this->exec('connect ' . $location);
    }

    public function disconnect()
    {
        return $this->exec('disconnect');
    }

    /**
     * get current status
     * @return array ['connected'=>true|false, 'location'=>string]
     */
    public function status()
    {
        $keyword = 'connected to';
        $output = array();
        $this->exec('status', $output);

        $pos = stripos($output[0], $keyword);
        if ($pos===false) {
            // not connected
            return array(
                'connected' => false,
                'location'  => ''
            );
        }

        $location = trim(substr($output[0], $pos + strlen($keyword)));
        $alias = '';
        foreach(static::$locations_list as $a=>$l) {
            if($l['location']==$location) {
                $alias = $a;
                break;
            }
        }
        return array(
            'connected' => true,
            'location'  => ($alias ?? $location)
        );
        return $output;
    }

    /**
     * execute a command
     * 
     * @param $args   string cmd args
     * @param $output array  var to store cmd output
     * 
     * @return int the execute status code
     */
    protected function exec(string $args, &$output=null)
    {
        $cmd = sprintf(
            '%s %s',
            escapeshellcmd($this->cli),
            $args
        );
        $this->log('Execute: ' . $cmd);

        $return_var = null;
        \exec($cmd, $output, $return_var);
        
        $this->log('Returned status: ' . $return_var);

        if($output) {
            $this->log('Output from command line:');
            foreach($output as $k=>$l) {
                $output[$k] = preg_replace('/[^[:print:]]/', '', $l);
                $this->log($output[$k]);
            }
        } else {
            $this->log('No output from command line.');
        }

        return $return_var;
    }

    protected function log($log)
    {
        file_put_contents(
            'data/expressvpn.log',
            date('c') . ' ' . $log . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * parse the output from command help
     * Note line 1 and 2 carry no actual data and omitted from return data
     * 
     * @param $help_output array
     * @return array parsed data
     */
    public function getAllLocations()
    {
        if(!static::$locations_list) {
            $help_output = array();
            $rv = $this->exec('list all', $help_output);

            $sections = array();
            preg_match_all('/-+ */', $help_output[1], $sections);
            $cs = array_map('strlen', $sections[0]);

            $locations = array();
            foreach($help_output as $l) {
                $p = 0;
                $loc = array();
                foreach($cs as $n) {
                    $loc[] = trim(substr($l, $p, $n));
                    $p+=$n;
                }
                $locations[] = $loc;
            }

            $col_names = array_map('strtolower', $locations[0]);
            $output = array();
            /**
            * ignore first 2 lines
            */
            for($i=2; $i<sizeof($locations); $i++) {
                $alias = $locations[$i][0];
                if($alias) {
                    $loc_t = array();
                    foreach($col_names as $k=>$col) {
                        $loc_t[$col] = $locations[$i][$k];
                    }
                    $output[$alias] = $loc_t;
                }
            }

            static::$locations_list = $output;
        }
        return static::$locations_list ;
    }

}
