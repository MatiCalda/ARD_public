<?php
namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerService
{
    private $logger;

    public function __construct($logPath){
        $this->logger = new Logger('app_logger');
        $this->logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    public function getLogger(){
        return $this->logger;
    }
}
