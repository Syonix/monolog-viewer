<?php
namespace SyonixLogViewer;

class LogViewer {
    protected $basedir;
    protected $logs;
    protected $regex;
    protected $clients;

    public function __construct() {
        setlocale(LC_ALL, 'en_US.UTF8');

        $logs = array();
        $this->logs = array();
        $this->clients = array();
        $this->regex = '/\[([\d\s:-]+)\] ([\w\.]*):(?: : )?([^{]+) ({.+)/';
        $this->baseDir = dirname(__FILE__) . '/../..';
        
        $config = json_decode(file_get_contents($this->baseDir . '/config/config.json'), true);
        $logs = $config['clients'];
        
        foreach ($logs as $log) {
            $logfiles = array();
            foreach($log['logs'] as $name => $file) {
                $logfiles[$this->toAscii($name)] = array('name' => $name, 'file' => $file);
            }
            $this->logs[$this->toAscii($log['name'])] = array(
                'name' => $log['name'], 
                'slug' => $this->toAscii($log['name']),
                'logs' => $logfiles
            );
            $this->clients[$this->toAscii($log['name'])] = $log['name'];
        }
    }
    
    public function getRegex() {
        return $this->regex;
    }
    
    public function getFirstLog($client) {
        if(isset($this->logs[$client]['logs'])) {
            reset($this->logs[$client]['logs']);
            if(count($this->logs[$client]['logs']) > 0)
                return key($this->logs[$client]['logs']);
        }
        return null;
    }
    
    public function getLogs($client = null) {
        if($client !== null && isset($this->logs[$client]['logs'])) return $this->logs[$client]['logs'];
        return $this->logs;
    }
    
    public function getLog($client, $slug) {
        if(isset($this->logs[$client]['logs'][$slug]))
        {
            $file = $this->logs[$client]['logs'][$slug];
            return new LogFile($file['name'], $file['file'], $this->regex);
        }
    }
    
    public function getClients() {
        return $this->clients;
    }
    
    public function getFirstClient() {
        reset($this->clients);
        return (count($this->clients) > 0) ? key($this->clients) : null;
    }
    
    public function clientExists($client) {
        return isset($this->clients[$client]);
    }
    
    public function logExists($client, $log) {
        return isset($this->logs[$client]['logs'][$log]);
    }
    
    function toAscii($str, $replace=array(), $delimiter='-') {
        // Courtesy of Cubiq http://cubiq.org/the-perfect-php-clean-url-generator
    	if( !empty($replace) ) {
    		$str = str_replace((array)$replace, ' ', $str);
    	}
    
    	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    	$clean = strtolower(trim($clean, '-'));
    	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    
    	return $clean;
    }
}