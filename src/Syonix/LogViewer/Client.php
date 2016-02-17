<?php
namespace Syonix\LogViewer;

use Doctrine\Common\Collections\ArrayCollection;
use Syonix\Util\StringUtil;

class Client {
    protected $name;
    protected $slug;
    protected $logs;

    public function __construct($name = null)
    {
        $this->logs = new ArrayCollection();
        if($name !== null) {
            $this->setName($name);
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->slug = StringUtil::toAscii($name);
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function addLog(LogFile $log) {
        if(!$this->logs->contains($log)) {
            $this->logs->add($log);
        }
        return $this;
    }

    public function removeLog(LogFile $log) {
        if($this->logs->contains($log)) {
            $this->logs->remove($log);
        }
        return $this;
    }

    public function getLogs() {
        return $this->logs;
    }

    /**
     * @param $slug
     * @return LogFile|null
     */
    public function getLog($slug)
    {
        foreach($this->logs as $log) {
            if($log->getSlug() == $slug) return $log;
        }
        return null;
    }


    /**
     * @return LogFile|null
     */
    public function getFirstLog()
    {
        return ($this->logs->count() > 0) ? $this->logs->first() : null;
    }

    public function logExists($log)
    {
        foreach($this->logs as $existing_log) {
            if($existing_log->getSlug() == $log) return true;
        }
        return false;
    }

    public function toArray()
    {
        $logs = [];
        foreach($this->logs as $log) {
            $logs[] = $log->toArray();
        }
        return array(
            'name' => $this->name,
            'slug' => $this->slug,
            'logs' => $logs
        );
    }
}
