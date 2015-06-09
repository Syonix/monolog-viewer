<?php

class LogViewerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Syonix\LogViewer\LogViewer
     */
	protected $logViewer;

	public function setUp(){
        $config = array(
            'Client1' => array(
                'Log1' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                ),
                'Log2' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                )
            ),
            'Client2' => array(
                'Log3' => array(
                    'type' => 'local',
                    'path' => realpath(__DIR__ . '/res/test.log')
                )
            )
        );
	    $this->logViewer = new Syonix\LogViewer\LogViewer($config);
	}
	
	public function tearDown(){ }
    
    public function testInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\LogViewer', $this->logViewer, "LogViewer is not instance of LogViewer");
    }
    
    /**
     * @depends testInit
     */
    public function testClientsInit()
    {
        $this->assertInstanceOf('Syonix\LogViewer\Client', $this->logViewer->getFirstClient());
        $this->assertEquals('Client1', $this->logViewer->getFirstClient()->getName());
    }
    
    /**
     * @depends testClientsInit
     */
    public function testGetLogs()
    {
        $this->assertEquals(2, $this->logViewer->getFirstClient()->getLogs()->count());
    }

    /**
     * @depends testGetLogs
     */
    public function testGetLog()
    {
        $log = $this->logViewer->getFirstClient()->getFirstLog();
        $this->assertInstanceOf('Syonix\LogViewer\LogFile', $log);
        $this->assertEquals('Log1', $log->getName());
        return $log;
    }
    
    /**
     * @depends testGetLog
     */
    public function testGetLogLines($log)
    {
        $log->load();
        $lines = $log->getLines();
        $this->assertInstanceOf('DateTime', $lines[0]['date']);
        $this->assertEquals('debug', $lines[0]['logger']);
        $this->assertEquals('DEBUG', $lines[0]['level']);
        $this->assertEquals('Random debug message', $lines[0]['message']);
        $this->assertEquals('Context1', $lines[0]['context']['c1']);
        $this->assertTrue(is_array($lines[0]['extra']));
    }
}
