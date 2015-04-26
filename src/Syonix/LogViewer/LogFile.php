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

    public function __construct($name, $args) {
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
                $this->filesystem = new Filesystem(new Local(dirname($this->args['path'])));
                $this->args['path'] = basename($this->args['path']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid log file type: \"" . $this->args['type']."\"");
        }

        $file = $this->filesystem->read($this->args['path']);
        $lines = explode("\n", $file);
        $parser = new LineLogParser();
        if(isset($this->args['pattern'])) {
            $hasCustomPattern = true;
            $parser->registerPattern('custom', $this->args['pattern']);
        } else {
            $hasCustomPattern = false;
        }
        foreach ($lines as $line) {
            $entry = ($hasCustomPattern ? $parser->parse($line, 0, 'custom') : $parser->parse($line, 0));
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
