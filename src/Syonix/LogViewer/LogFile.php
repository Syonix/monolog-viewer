<?php
namespace Syonix\LogViewer;

use Dubture\Monolog\Parser\LineLogParser;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Syonix\Util\String;

class LogFile {
    protected $name;
    protected $slug;
    protected $args;
    protected $lines;
    protected $filesystem;

    public function __construct($name, $args, $cacheDir) {
        setlocale(LC_ALL, 'en_US.UTF8');
        
        $this->name = $name;
        $this->slug = String::toAscii($name);
        $this->args = $args;

        switch($this->args['type']) {
            case 'ftp':
                $this->filesystem = new Filesystem(new Ftp(array(
                    'host' => $this->args['host'],
                    'username' => $this->args['username'],
                    'password' => $this->args['password'],
                    'passive' => true,
                    'ssl' => true,
                )));
                break;
            case 'local':
                $this->filesystem = new Filesystem(new Local(APP_ROOT));
                break;
            case 'url':
                //$this->filesystem = new Filesystem(new Local(APP_ROOT));
                break;
            default:
                throw new \InvalidArgumentException("Invalid log file type: \"" . $this->args['type']."\"");
        }

        $file = $this->filesystem->read($this->args['path']);
        $lines = explode("\n", $file);
        $parser = new LineLogParser();
        foreach ($lines as $line) {
            $entry = $parser->parse($line, 0);
            if (count($entry) > 0) {
                $this->lines[] = $entry;
            }
        }
    }
    
    public function getLine($line)
    {
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
}
