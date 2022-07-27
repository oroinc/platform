<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "searchText" filter to all searchable entities.
 */
class AddSearchTextFilter implements ProcessorInterface
{
    private AbstractSearchMappingProvider $searchMappingProvider;

    public function __construct(AbstractSearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        if (!$this->searchMappingProvider->isClassSupported($context->getClassName())) {
            return;
        }

        if (count($context->getResult()->getIdentifierFieldNames()) !== 1) {
            return;
        }

        $filter = $context->getFilters()->getOrAddField('searchText');
        $filter->setDataType(DataType::STRING);
        $filter->setType('simpleSearch');
        if ($context->hasExtra(DescriptionsConfigExtra::NAME)) {
            $filter->setDescription('Filter records by a search string.');
        }
    }
}
