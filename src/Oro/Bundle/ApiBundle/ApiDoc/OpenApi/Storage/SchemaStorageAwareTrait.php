<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This trait can be used by classes that implement {@see SchemaStorageAwareInterface}.
 */
trait SchemaStorageAwareTrait
{
    private ?SchemaStorage $schemaStorage = null;

    public function setSchemaStorage(?SchemaStorage $schemaStorage): void
    {
        $this->schemaStorage = $schemaStorage;
    }
}
