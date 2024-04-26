<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

/**
 * This interface can be implemented by describers that needs {@see SchemaStorage}.
 */
interface SchemaStorageAwareInterface
{
    public function setSchemaStorage(?SchemaStorage $schemaStorage): void;
}
