<?php

namespace Oro\Bundle\ChartBundle\Exception;

class InvalidConfigurationException extends \Exception implements Exception
{
    public function __construct($message = "")
    {
        $message = 'Can\'t find configuration for chart: ' . $message;
        parent::__construct($message);
    }
}
