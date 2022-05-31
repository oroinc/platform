<?php

namespace Oro\Bundle\TranslationBundle\Exception;

/**
 * This exception is thrown when a translation service request returns invalid data.
 */
class TranslationServiceInvalidResponseException extends TranslationServiceAdapterException
{
    private string $response;

    public function __construct(string $message, string $response, \Throwable $previous = null)
    {
        parent::__construct($message, $previous);
        $this->response = $response;
    }

    public function getResponse(): string
    {
        return $this->response;
    }
}
