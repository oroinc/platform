<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Exception;

/**
 * This exception is thrown when some issue happens in a translation service adapter.
 */
class TranslationServiceAdapterException extends \RuntimeException
{
    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
