<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;

/**
 * Creates a new instance of NestedAssociationFilter.
 */
class NestedAssociationFilterFactory
{
    private EntityAliasResolverRegistry $entityAliasResolverRegistry;

    public function __construct(EntityAliasResolverRegistry $entityAliasResolverRegistry)
    {
        $this->entityAliasResolverRegistry = $entityAliasResolverRegistry;
    }

    public function createFilter(string $dataType): NestedAssociationFilter
    {
        $filter = new NestedAssociationFilter($dataType);
        $filter->setEntityAliasResolverRegistry($this->entityAliasResolverRegistry);

        return $filter;
    }
}
