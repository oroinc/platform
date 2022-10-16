<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

/**
 * The factory to create AssociationCompositeIdentifierFilter.
 */
class AssociationCompositeIdentifierFilterFactory
{
    private EntityIdTransformerRegistry $idTransformerRegistry;

    public function __construct(EntityIdTransformerRegistry $idTransformerRegistry)
    {
        $this->idTransformerRegistry = $idTransformerRegistry;
    }

    /**
     * Creates a new instance of AssociationCompositeIdentifierFilter.
     */
    public function createFilter(string $dataType): AssociationCompositeIdentifierFilter
    {
        $filter = new AssociationCompositeIdentifierFilter($dataType);
        $filter->setEntityIdTransformerRegistry($this->idTransformerRegistry);

        return $filter;
    }
}
