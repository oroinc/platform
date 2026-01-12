<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Thrown when a transport layer error occurs during integration communication.
 *
 * This exception is raised when communication with the remote integration service fails,
 * including SOAP faults, HTTP errors, or other transport-level issues. It can carry
 * a fault code from the remote service for more detailed error handling.
 */
class TransportException extends \Exception implements IntegrationException
{
    /**
     * @var int|string
     */
    protected $faultCode;

    /**
     * @return int|string
     */
    public function getFaultCode()
    {
        return $this->faultCode;
    }

    /**
     * @param int|string $faultCode
     * @return TransportException
     */
    public function setFaultCode($faultCode)
    {
        $this->faultCode = $faultCode;

        return $this;
    }
}
