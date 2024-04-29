<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage;

use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;

/**
 * Represents a storage for OpenAPI schemas.
 */
class SchemaStorage
{
    /** @var array [schema => schema index, ...] */
    private array $schemaMap = [];

    public function findSchema(OA\OpenApi $api, string $schema): ?OA\Schema
    {
        $index = $this->schemaMap[$schema] ?? null;
        if (null === $index) {
            return null;
        }

        return $api->components->schemas[$index];
    }

    public function getSchema(OA\OpenApi $api, string $schema): OA\Schema
    {
        $index = $this->schemaMap[$schema] ?? null;
        if (null === $index) {
            throw new \LogicException(sprintf('The schema "%s" does not exist.', $schema));
        }

        return $api->components->schemas[$index];
    }

    public function addSchema(OA\OpenApi $api, string $schema): OA\Schema
    {
        if (!$this->schemaMap) {
            Util::ensureComponentCollectionInitialized($api, 'schemas');
        }

        if (isset($this->schemaMap[$schema])) {
            throw new \LogicException(sprintf('The schema "%s" already exists.', $schema));
        }

        $schemaObj = Util::createChildItem(OA\Schema::class, $api->components, $schema);
        $schemaObj->schema = $schema;

        $index = \count($api->components->schemas);
        $this->schemaMap[$schema] = $index;
        $api->components->schemas[$index] = $schemaObj;

        return $schemaObj;
    }
}
