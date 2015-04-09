<?php
namespace Syonix\LogViewer;

use Syonix\Util\String;

class LogViewer
{
    protected $logs;
    protected $clients;
    protected $cacheDir;

    public function __construct($logs)
    {
        setlocale(LC_ALL, 'en_US.UTF8');

        $this->logs = array();
        $this->clients = array();

        $this->cacheDir = APP_PATH . 'cache/';
        
        foreach ($logs as $client_name => $client) {
            $logfiles = array();
            foreach($client as $log_name => $file) {
                $logfiles[String::toAscii($log_name)] = array('name' => $log_name, 'file' => $file);
            }
            
            // Only add clients with at least one log file.
            if(count($logfiles) > 0) {
                $client_slug = String::toAscii($client_name);
                $this->logs[$client_slug] = array(
                    'name' => $client_name,
                    'slug' => $client_slug,
                    'logs' => $logfiles
                );
                $this->clients[$client_slug] = $client_name;
            }
        }
    }
    
    public function hasLogs()
    {
        return (count($this->logs) > 0);
    }
    
    public function getFirstLog($client)
    {
        if(isset($this->logs[$client]['logs'])) {
            reset($this->logs[$client]['logs']);
            if(count($this->logs[$client]['logs']) > 0)
                return key($this->logs[$client]['logs']);
        }
        return null;
    }
    
    public function getLogs($client = null)
    {
        if($client !== null) {
            if(isset($this->logs[$client]['logs'])) return $this->logs[$client]['logs'];
            else return ;
        }
        return $this->logs;
    }
    
    public function getLog($client, $slug)
    {
        if(isset($this->logs[$client]['logs'][$slug]))
        {
            $file = $this->logs[$client]['logs'][$slug];
            return new LogFile($file['name'], $file['file']);
        }
        return false;
    }
    
    public function getClients()
    {
        return $this->clients;
    }
    
    public function getFirstClient()
    {
        reset($this->clients);
        return (count($this->clients) > 0) ? key($this->clients) : null;
    }
    
    public function clientExists($client)
    {
        return isset($this->clients[$client]);
    }
    
    public function logExists($client, $log)
    {
        return isset($this->logs[$client]['logs'][$log]);
    }
}
