<?php

require_once("../bootstrap.php");

class LogViewerTest extends PHPUnit_Framework_TestCase
{
	protected $logViewer;
	public function setUp(){
	    global $app;
	    $this->logViewer = $app;
	}
	
	public function tearDown(){ }
    
    public function testInit()
    {
        $this->assertInstanceOf('SyonixLogViewer\LogViewer', $this->logViewer, "LogViewer is not instance of LogViewer");
    }
    
    /**
     * @depends testInit
     */
    public function testDb()
    {
        print_r($this->logViewer->getClients());
    }
}
?>