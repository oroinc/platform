<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;

/**
 * Represents a service to load data when they cannot be loaded by the EntitySerializer component.
 */
interface DataLoaderInterface
{
    public function loadData(QueryBuilder $qb, EntityDefinitionConfig $config, array $context): array;

    public function serializeData(array $data, EntityDefinitionConfig $config, array $context): array;
}
