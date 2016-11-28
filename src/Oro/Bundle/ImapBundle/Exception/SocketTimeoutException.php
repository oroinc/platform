<?php

namespace Oro\Bundle\ImapBundle\Exception;

class SocketTimeoutException extends \RuntimeException
{
    /** @var array */
    protected $socketMetadata = [];

    /**
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     * @param array $socketMetadata
     */
    public function __construct(
        $message = '',
        $code = 0,
        \Exception $previous = null,
        $socketMetadata = []
    ) {
        $this->socketMetadata = $socketMetadata;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getSocketMetadata()
    {
        return $this->socketMetadata;
    }
}
