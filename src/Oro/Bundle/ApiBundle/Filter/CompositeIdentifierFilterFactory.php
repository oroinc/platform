<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

/**
 * The factory to create CompositeIdentifierFilter.
 */
class CompositeIdentifierFilterFactory
{
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * Creates a new instance of CompositeIdentifierFilter.
     */
    public function createFilter(string $dataType): CompositeIdentifierFilter
    {
        $filter = new CompositeIdentifierFilter($dataType);
        $filter->setEntityIdTransformerRegistry($this->entityIdTransformerRegistry);

        return $filter;
    }
}
