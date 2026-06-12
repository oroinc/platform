<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Consumption\Exception;

/**
 * Exception thrown when JSON decoding fails for Message Queue consumption groups.
 */
class ConsumptionGroupsJsonException extends \RuntimeException implements ExceptionInterface
{
    public static function create(\JsonException $exception)
    {
        return new self(
            'Failed to decode JSON for MQ consumption groups: ' . $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }
}
