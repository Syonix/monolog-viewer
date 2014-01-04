<?php

require_once("../bootstrap.php");

class LogViewerTest extends PHPUnit_Framework_TestCase
{
	protected $logViewer;
	public function setUp(){
	    $this->logViewer = new \SyonixLogViewer\LogViewer(dirname(__FILE__) . '/res/config.json');
	}
	
	public function tearDown(){ }
    
    public function testInit()
    {
        $this->assertInstanceOf('SyonixLogViewer\LogViewer', $this->logViewer, "LogViewer is not instance of LogViewer");
    }
    
    /**
     * @depends testInit
     */
    public function testClientsInit()
    {
        $this->assertContains('Client1', $this->logViewer->getClients());
    }
    
    /**
     * @depends testClientsInit
     */
    public function testGetLogs()
    {
        $this->assertArrayHasKey('log1', $this->logViewer->getLogs('client1'));
    }
    
    /**
     * @depends testGetLogs
     */
    public function testGetLog()
    {
        $log = $this->logViewer->getLog('client1', 'log1');
        $this->assertInstanceOf('\SyonixLogViewer\LogFile', $log);
        return $log;
    }
    
    /**
     * @depends testGetLog
     */
    public function testGetLogLines($log)
    {
        $lines = $log->getLines();
        $this->assertInstanceOf('DateTime', $lines[0]['date']);
        $this->assertEquals('debug', $lines[0]['logger']);
        $this->assertEquals('DEBUG', $lines[0]['level']);
        $this->assertEquals('Random debug message', $lines[0]['message']);
        $this->assertEquals('Context1', $lines[0]['context']['c1']);
        $this->assertTrue(is_array($lines[0]['extra']));
    }
}
?>