<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

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
