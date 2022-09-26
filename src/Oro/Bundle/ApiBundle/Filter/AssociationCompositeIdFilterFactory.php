<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

/**
 * The factory to create AssociationCompositeIdFilter.
 */
class AssociationCompositeIdFilterFactory
{
    private EntityIdTransformerRegistry $idTransformerRegistry;

    public function __construct(EntityIdTransformerRegistry $idTransformerRegistry)
    {
        $this->idTransformerRegistry = $idTransformerRegistry;
    }

    /**
     * Creates a new instance of CompositeIdentifierFilter.
     */
    public function createFilter(string $dataType): CompositeIdentifierFilter
    {
        $filter = new AssociationCompositeIdFilter($dataType);
        $filter->setEntityIdTransformerRegistry($this->idTransformerRegistry);

        return $filter;
    }
}
