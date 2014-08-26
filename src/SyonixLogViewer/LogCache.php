<?php
namespace SyonixLogViewer;

use Requests;

class LogCache {
    private $cacheDir;

    public function __construct($cacheDir)
    {
        if(!is_writable($cacheDir))
        {
            throw new \InvalidArgumentException("Cannot write cache directory.");
        }
        $this->cacheDir = $cacheDir;
    }

    public function get($remotePath)
    {
        $localName = str_replace('://', '_', $remotePath);
        $localName = str_replace('/', '_', $localName);
        $localPath = $this->cacheDir.$localName;
        if($this->existsLocally($localPath))
        {
            $this->updateCache($remotePath, $localPath);
        }
        else
        {
            $this->createCache($remotePath, $localPath);
        }
        return file_get_contents($localPath);
    }

    public function getModificationDateLocal($localPath)
    {
        $time = new \DateTime();
        $time->setTimestamp(filemtime($localPath));
        $time->setTimezone(new \DateTimeZone('GMT'));
        return $time;
    }

    private function existsLocally($localPath)
    {
        if(is_file($localPath))
        {
            if(!is_readable($localPath))
            {
                throw new \InvalidArgumentException("Locally cached file is not readable");
            }
            return true;
        }
        return false;
    }

    public function createCache($remotePath, $localPath)
    {
        $response = Requests::get($remotePath);
        switch($response->status_code)
        {
            case 200:
                file_put_contents($localPath, $response->body);
                break;
            default:
                throw new \Exception("Unexpected return value during cache update");
        }
    }

    public function updateCache($remotePath, $localPath)
    {
        $lastModifiedLocal = $this->getModificationDateLocal($localPath);
        $headers = array('If-Modified-Since' => $lastModifiedLocal->format('r'));
        $response = Requests::get($remotePath, $headers);
        switch($response->status_code)
        {
            case 200:
                file_put_contents($localPath, $response->body);
                break;
            case 304:
                break;
            default:
                throw new \Exception("Unexpected return value during cache update");
        }
    }
}
