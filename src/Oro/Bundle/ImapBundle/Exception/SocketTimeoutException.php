<?php

namespace Oro\Bundle\ImapBundle\Exception;

/**
 * Thrown when a socket timeout occurs during IMAP server communication.
 *
 * This exception is raised when the IMAP connector fails to receive a response from the server
 * within the configured timeout period. It includes socket metadata that can help diagnose
 * connection issues and understand the state of the socket when the timeout occurred.
 */
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
        ?\Exception $previous = null,
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
