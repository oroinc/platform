<?php

namespace Oro\Bundle\DashboardBundle\Exception;

class InvalidConfigurationException extends \Exception implements Exception
{
    public function __construct($message = "")
    {
        $message = 'Can\'t find configuration for: ' . $message;
        parent::__construct($message);
    }
}
