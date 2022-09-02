<?php

namespace Oro\Bundle\PlatformBundle\MaterializedView\Exception;

use Oro\Bundle\PlatformBundle\Entity\MaterializedView;

/**
 * Thrown when {@see MaterializedView} is not found.
 */
class MaterializedViewDoesNotExistException extends \RuntimeException implements MaterializedViewExceptionInterface
{
    public static function create(string $name, string $context = ''): self
    {
        return new self(sprintf('Materialized view "%s" does not exist. %s', $name, $context));
    }
}
