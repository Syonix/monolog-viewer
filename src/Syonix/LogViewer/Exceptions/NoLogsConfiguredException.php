<?php
namespace Syonix\LogViewer\Exceptions;

class NoLogsConfiguredException extends \Exception
{
    public function __construct($code = 0, \Exception $previous = null) {
        parent::__construct("No valid log files have been configured.", $code, $previous);
    }
}
