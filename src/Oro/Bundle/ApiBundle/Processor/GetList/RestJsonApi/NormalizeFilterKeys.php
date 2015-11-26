<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\RestJsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;

class NormalizeFilterKeys implements ProcessorInterface
{
    const FILTER_KEY_TEMPLATE = 'filter[%s]';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $filterCollection = $context->getFilters();

        $filters = $filterCollection->all();
        foreach ($filters as $filterKey => $filter) {
            if ('sort' === $filterKey || 0 === strpos($filterKey, 'page[')) {
                continue;
            }
            $filterCollection->remove($filterKey);
            $filterCollection->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $filterKey),
                $filter
            );
        }
    }
}
