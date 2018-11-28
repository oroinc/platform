<?php

namespace Oro\Bundle\ImapBundle\Exception;

use Zend\Mail\Header\HeaderName;

/**
 * Exception that throws in case if was detected wrong message header.
 */
class InvalidHeaderException extends \RuntimeException
{
    const MESSAGE_PATTERN = 'Unable to parse the header "%s". Reason: "%s".';

    /**
     * {@inheritdoc}
     */
    public function __construct(string $header, \Throwable $exception)
    {
        $message = sprintf(self::MESSAGE_PATTERN, $this->getHeaderName($header), $exception->getMessage());

        parent::__construct($message, $exception->getCode(), $exception);
    }

    /**
     * @param $header
     *
     * @return string
     */
    private function getHeaderName(string $header): string
    {
        $headerName = '';

        $parts = explode(':', $header, 2);
        if (isset($parts[0]) && HeaderName::isValid($parts[0])) {
            $headerName = $parts[0];
        }

        return $headerName;
    }
}
