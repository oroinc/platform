<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use OpenApi\Annotations as OA;

/**
 * Represents a service that describes models for OpenAPI specification.
 */
interface ModelDescriberInterface
{
    public function describe(
        OA\OpenApi $api,
        OA\Schema $schema,
        array $model,
        string $modelName,
        ?string $entityType,
        bool $isCollection,
        bool $isPrimary,
        bool $isRelationship
    ): void;

    public function describeUnion(OA\OpenApi $api, OA\Schema $schema, array $modelNames, bool $isCollection): void;
}
