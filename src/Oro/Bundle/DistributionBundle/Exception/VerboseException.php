<?php

namespace Oro\Bundle\DistributionBundle\Exception;

class VerboseException extends \Exception
{
    /**
     * @var string
     */
    protected $verboseMessage;

    /**
     * @param string $message
     * @param string $verboseMessage
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $verboseMessage = '', $code = 0, \Exception $previous = null)
    {
        $this->verboseMessage = $verboseMessage;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getVerboseMessage()
    {
        return $this->verboseMessage;
    }
}
