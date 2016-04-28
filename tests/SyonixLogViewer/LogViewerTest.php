<?php

use League\Flysystem\Adapter\NullAdapter;
use Syonix\LogViewer\LogFile;
use Syonix\LogViewer\LogFileCache;
use Syonix\LogViewer\LogManager;

class LogViewerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syonix\LogViewer\LogManager
     */
    protected $logManager;

    /**
     * @var \Syonix\LogViewer\LogFileCache
     */
    protected $cache;

    public function setUp()
    {
        $config = [
            'Client1' => [
                'Log1' => [
                    'type' => 'local',
                    'path' => realpath(__DIR__.'/res/test.log'),
                ],
                'Log2' => [
                    'type' => 'local',
                    'path' => realpath(__DIR__.'/res/test.log'),
                ],
            ],
            'Client2' => [
                'Log3' => [
                    'type' => 'local',
                    'path' => realpath(__DIR__.'/res/test.log'),
                ],
            ],
        ];
        $this->logManager = new LogManager($config);
    }

    public function tearDown()
    {
    }

    public function testInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\LogManager', $this->logManager, 'LogManager is not instance of LogManager');
    }

    /**
     * @depends testInit
     */
    public function testClientsInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\LogCollection', $this->logManager->getFirstLogCollection());
        $this->assertEquals('Client1', $this->logManager->getFirstLogCollection()->getName());
    }

    /**
     * @depends testClientsInit
     */
    public function testGetLogs()
    {
        $this->assertEquals(2, $this->logManager->getFirstLogCollection()->getLogs()->count());
    }

    /**
     * @depends testGetLogs
     */
    public function testGetLog()
    {
        $log = $this->logManager->getFirstLogCollection()->getFirstLog();
        $this->assertInstanceOf('Syonix\LogViewer\LogFile', $log);
        $this->assertEquals('Log1', $log->getName());

        return $log;
    }

    /**
     * @param LogFile $log
     *
     * @depends testGetLog
     */
    public function testGetLogLines(LogFile $log)
    {
        $adapter = new NullAdapter();
        $this->cache = new LogFileCache($adapter, 300, false);
        $log = $this->cache->get($log);
        $lines = $log->getLines();
        $this->assertInstanceOf('DateTime', $lines[0]['date']);
        $this->assertEquals('debug', $lines[0]['logger']);
        $this->assertEquals('DEBUG', $lines[0]['level']);
        $this->assertEquals('Random debug message', $lines[0]['message']);
        $this->assertEquals('Context1', $lines[0]['context']['c1']);
        $this->assertTrue(is_array($lines[0]['extra']));
    }
}
