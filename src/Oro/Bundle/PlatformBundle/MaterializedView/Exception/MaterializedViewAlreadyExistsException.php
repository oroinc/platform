<?php

namespace Oro\Bundle\PlatformBundle\MaterializedView\Exception;

/**
 * Thrown when {@see MaterializedView} cannot be created because already exists.
 */
class MaterializedViewAlreadyExistsException extends \RuntimeException implements MaterializedViewExceptionInterface
{
    public static function create(string $name): self
    {
        return new self(sprintf('Failed to create materialized view "%s" because it already exists', $name));
    }
}
