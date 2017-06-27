<?php

namespace Intelie\LiveObserver\Observer\Dispatch;

class Console
{

    public function __construct()
    {

        $writer = new \Zend\Log\Writer\Syslog();
        $this->logger = new \Zend\Log\Logger();
        $this->logger->addWriter($writer);

    }

    public function execute()
    {
        return $this;

    }

    public function log($text)
    {
        $this->logger->info($text);
    }

}
