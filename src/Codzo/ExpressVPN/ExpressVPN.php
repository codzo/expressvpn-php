<?php

namespace Codzo\ExpressVPN;

use Codzo\Config\Config;

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

    /**
     * @var array current status
     */
    protected $last_output = array();

    public function __construct(Config $config)
    {
        $this->cli = $config->get('expressvpn.cli', 'expressvpn');
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
    public function connect($location = '')
    {
        return $this->exec('connect ' . $location);
    }

    public function disconnect()
    {
        return $this->exec('disconnect');
    }

    /**
     * get current status
     * @return array the output from `expressvpn status`
     */
    public function status()
    {
        return $this->exec('status');
    }

    /**
     * get output from last command execution
     */
    public function getLastOutput()
    {
        return $this->last_output;
    }

    /**
     * execute a command
     * Output to STDOUT will be stored in $this->last_output
     *
     * @param $args   string cmd args
     *
     * @return int the execute status code
     */
    protected function exec(string $args)
    {
        $cmd = sprintf(
            '%s %s',
            escapeshellcmd($this->cli),
            $args
        );
        $this->log('Execute: ' . $cmd);

        $rv = null;
        $this->last_output = array();
        \exec($cmd, $this->last_output, $rv);
        
        $this->log('Returned code: ' . $rv);

        if ($this->last_output) {
            $this->log('Output from command line:');
            foreach ($this->last_output as $k => $l) {
                $this->last_output[$k] = preg_replace('/[^[:print:]]/', '', $l);
                $this->log("\t" . $this->last_output[$k]);
            }
        } else {
            $this->log('No output from command line');
        }

        return $rv;
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
     * get all location alias
     * Note line 1 and 2 carry no actual data and omitted from return data
     *
     * @param $help_output array
     * @return array parsed data
     */
    public function getLocations()
    {
        if (!static::$locations_list) {
            $help_output = array();
            \exec(escapeshellcmd($this->cli) . ' list all', $help_output);
            if(sizeof($help_output)>2) {
                // remove first two lines
                $help_output = array_slice($help_output, 2);
                foreach( $help_output as $l) {
                    list($alias,) = explode(' ', $l);
                    if($alias) {
                        static::$locations_list[] = $alias;
                    }
                }
            }
        }
        return static::$locations_list ;
    }
}
