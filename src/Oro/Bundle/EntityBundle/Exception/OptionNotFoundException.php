<?php

namespace Oro\Bundle\EntityBundle\Exception;

class OptionNotFoundException extends \Exception
{
    /**
     * {@inheritDoc}
     */
    public function __construct($optionName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Option "%s" not found', $optionName);
        parent::__construct($message, $code, $previous);
    }
}
