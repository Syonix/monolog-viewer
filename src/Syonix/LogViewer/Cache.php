<?php
namespace Syonix\LogViewer;

use Dubture\Monolog\Parser\LineLogParser;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

class Cache
{
    private $cache;
    private $expire;
    private $reverse;

    public function __construct(AdapterInterface $adapter, $expire = 300, $reverse = true)
    {
        $this->cache = new Filesystem($adapter);
        $this->expire = $expire;
        $this->reverse = $reverse;
    }

    public function get(LogFile $logFile)
    {
        if($this->cache->has($this->getFilename($logFile))) {
            $timestamp = $this->cache->getTimestamp($this->getFilename($logFile));
            if($timestamp > (time() - $this->expire)) {
                return $this->readCache($logFile);
            } else {
                $this->deleteCache($logFile);
            }
        }

        return $this->loadSource($logFile);
    }

    private function getFilename(LogFile $logFile)
    {
        return base64_encode($logFile->getIdentifier());
    }

    private function writeCache(LogFile $logFile)
    {
        $this->cache->write($this->getFilename($logFile), serialize($logFile));
    }

    private function readCache(LogFile $logFile)
    {
        return unserialize($this->cache->get($this->getFilename($logFile))->read());
    }

    private function deleteCache(LogFile $logFile)
    {
        $this->cache->delete($this->getFilename($logFile));
    }

    public function emptyCache()
    {
        $cache = $this->cache->get('/')->getContents();
        foreach ($cache as $file) {
            if($file['type'] == 'file' && substr($file['basename'], 0, 1) !== '.') $this->cache->delete($file['path']);
        }
    }

    private function loadSource(LogFile $logFile)
    {
        $args = $logFile->getArgs();

        switch($args['type']) {
            case 'ftp':
                $filesystem = new Filesystem(new Ftp(array(
                    'host' => $args['host'],
                    'username' => $args['username'],
                    'password' => $args['password'],
                    'passive' => true,
                    'ssl' => false,
                )));
                break;
            case 'local':
                $filesystem = new Filesystem(new Local(dirname($args['path'])));
                $args['path'] = basename($args['path']);
                break;
            default:
                throw new \InvalidArgumentException("Invalid log file type: \"" . $args['type']."\"");
        }

        $file = $filesystem->read($args['path']);
        $lines = explode("\n", $file);
        $parser = new LineLogParser();
        if(isset($args['pattern'])) {
            $hasCustomPattern = true;
            $parser->registerPattern('custom', $args['pattern']);
        } else {
            $hasCustomPattern = false;
        }

        foreach ($lines as $line) {
            $entry = ($hasCustomPattern ? $parser->parse($line, 0, 'custom') : $parser->parse($line, 0));
            if (count($entry) > 0) {
                if(!$logFile->hasLogger($entry['logger'])) {
                    $logFile->addLogger($entry['logger']);
                }
                $logFile->addLine($entry);
            }
        }

        if($this->reverse) $logFile->reverseLines();
        $this->writeCache($logFile);

        return $logFile;
    }
}
