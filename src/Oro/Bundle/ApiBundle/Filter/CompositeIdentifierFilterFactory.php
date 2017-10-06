<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;

/**
 * The factory to create CompositeIdentifierFilter.
 */
class CompositeIdentifierFilterFactory
{
    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /**
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * Creates a new instance of CompositeIdentifierFilter.
     *
     * @param string $dataType
     *
     * @return CompositeIdentifierFilter
     */
    public function createFilter($dataType)
    {
        $filter = new CompositeIdentifierFilter($dataType);
        $filter->setEntityIdTransformerRegistry($this->entityIdTransformerRegistry);

        return $filter;
    }
}
