<?php
namespace Syonix\LogViewer\Exceptions;

class ConfigFileMissingException extends \Exception
{
    public function __construct($code = 0, \Exception $previous = null) {
        parent::__construct("The config file is missing.", $code, $previous);
    }
}
