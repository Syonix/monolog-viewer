<?php
namespace SyonixLogViewer;

use Dubture\Monolog\Parser\LineLogParser;

class LogFile {
    protected $name;
    protected $slug;
    protected $path;
    protected $lines;

    public function __construct($name, $path) {
        setlocale(LC_ALL, 'en_US.UTF8');
        
        $this->name = $name;
        $this->slug = $this->toAscii($name);
        $this->path = $path;
        
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $this->path);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        $file = curl_exec($ch);
        curl_close($ch);
        
        $lines = explode("\n", $file);

        foreach($lines as $line) {
            $parser = new LineLogParser(); 
            $entry = $parser->parse($line, 0);
            if(count($entry) > 0) {
                $this->lines[] = $entry;
            }
        }
    }
    
    public function getLine($line)
    {
        // Todo: Check
        return $this->lines[intval($line)];
    }
    
    public function getLines() {
        return $this->lines;
    }
    
    public function getName() {
        return $this->name;
    }

    
    public function getSlug() {
        return $this->slug;
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