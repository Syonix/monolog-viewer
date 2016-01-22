<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Dubture\Monolog\Parser\LineLogParser;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Adapter;
use League\Flysystem\Filesystem;
use Syonix\LogViewer\Exceptions\NoLogsConfiguredException;

class LogViewer
{
    protected $clients;
    protected $cacheDir;

    public function __construct($logs)
    {
        setlocale(LC_ALL, 'en_US.UTF8');

        $this->clients = new ArrayCollection();

        if(count($logs) == 0) {
            throw new NoLogsConfiguredException();
        }
        foreach ($logs as $client_name => $client_logs) {
            if(count($client_logs) > 0) {
                $client = new Client($client_name);
                foreach ($client_logs as $log_name => $args) {
                    $client->addLog(new LogFile($log_name, $client->getSlug(), $args));
                }
                $this->clients->add($client);
            }
        }
    }
    
    public function hasLogs()
    {
        return !$this->clients->isEmpty();
    }

    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param $slug
     * @return Client|null
     */
    public function getClient($slug)
    {
        foreach($this->clients as $client) {
            if($client->getSlug() == $slug) return $client;
        }
        return null;
    }
    
    public function getFirstClient()
    {
        return ($this->clients->count() > 0) ? $this->clients->first() : null;
    }
    
    public function clientExists($client)
    {
        return $this->clients->contains($client);
    }
}
