<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

/**
 * The factory to create ExtendedAssociationFilter.
 */
class ExtendedAssociationFilterFactory
{
    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var AssociationManager */
    private $associationManager;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        AssociationManager $associationManager,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->associationManager = $associationManager;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * Creates a new instance of ExtendedAssociationFilter.
     */
    public function createFilter(string $dataType): ExtendedAssociationFilter
    {
        $filter = new ExtendedAssociationFilter($dataType);
        $filter->setValueNormalizer($this->valueNormalizer);
        $filter->setAssociationManager($this->associationManager);
        $filter->setEntityOverrideProviderRegistry($this->entityOverrideProviderRegistry);

        return $filter;
    }
}
