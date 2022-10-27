<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * The factory to create ExtendedAssociationFilter.
 */
class ExtendedAssociationFilterFactory
{
    private ValueNormalizer $valueNormalizer;
    private ExtendedAssociationProvider $extendedAssociationProvider;
    private EntityOverrideProviderRegistry $entityOverrideProviderRegistry;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        ExtendedAssociationProvider $extendedAssociationProvider,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->extendedAssociationProvider = $extendedAssociationProvider;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * Creates a new instance of ExtendedAssociationFilter.
     */
    public function createFilter(string $dataType): ExtendedAssociationFilter
    {
        $filter = new ExtendedAssociationFilter($dataType);
        $filter->setValueNormalizer($this->valueNormalizer);
        $filter->setExtendedAssociationProvider($this->extendedAssociationProvider);
        $filter->setEntityOverrideProviderRegistry($this->entityOverrideProviderRegistry);

        return $filter;
    }
}
