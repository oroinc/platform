<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

/**
 * The factory to create ExtendedAssociationFilter.
 */
class ExtendedAssociationFilterFactory
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param ValueNormalizer    $valueNormalizer
     * @param AssociationManager $associationManager
     */
    public function __construct(ValueNormalizer $valueNormalizer, AssociationManager $associationManager)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->associationManager = $associationManager;
    }

    /**
     * Creates a new instance of ExtendedAssociationFilter.
     *
     * @param string $dataType
     *
     * @return ExtendedAssociationFilter
     */
    public function createFilter($dataType)
    {
        $filter = new ExtendedAssociationFilter($dataType);
        $filter->setValueNormalizer($this->valueNormalizer);
        $filter->setAssociationManager($this->associationManager);

        return $filter;
    }
}
