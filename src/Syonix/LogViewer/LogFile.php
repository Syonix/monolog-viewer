<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
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
    protected $loggers;

    public function __construct($name, $args) {
        setlocale(LC_ALL, 'en_US.UTF8');
        
        $this->name = $name;
        $this->slug = String::toAscii($name);
        $this->args = $args;
    }

    public function load()
    {
        switch($this->args['type']) {
            case 'ftp':
                $this->filesystem = new Filesystem(new Ftp(array(
                    'host' => $this->args['host'],
                    'username' => $this->args['username'],
                    'password' => $this->args['password'],
                    'passive' => true,
                    'ssl' => false,
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
        $this->loggers = new ArrayCollection();
        foreach ($lines as $line) {
            $entry = ($hasCustomPattern ? $parser->parse($line, 0, 'custom') : $parser->parse($line, 0));
            if (count($entry) > 0) {
                if(!$this->loggers->contains($entry['logger'])) {
                    $this->loggers->add($entry['logger']);
                }
                $this->lines[] = $entry;
            }
        }
        return $this;
    }
    
    public function getLine($line)
    {
        return $this->lines[intval($line)];
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function countLines()
    {
        return count($this->lines);
    }
    
    public function getName()
    {
        return $this->name;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function getLoggers()
    {
        return $this->loggers;
    }
}
