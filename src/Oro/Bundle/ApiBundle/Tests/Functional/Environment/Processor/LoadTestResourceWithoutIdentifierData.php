<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Emulates loading of data for testing API resource without identifier.
 */
class LoadTestResourceWithoutIdentifierData implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $resultName = 'test';

        // test that it is possible to add a filter for "get" action for resources without identifier
        $filters = $context->getFilters();
        $filterValues = $context->getFilterValues();
        $filterKeys = [
            AddFiltersToResourceWithoutIdentifier::FILTER1_KEY,
            AddFiltersToResourceWithoutIdentifier::FILTER2_KEY
        ];
        foreach ($filterKeys as $filterKey) {
            if ($filters->has($filterKey)) {
                $filterValue = $filterValues->get($filterKey);
                if (null !== $filterValue) {
                    $val = $filterValue->getValue();
                    if ($val instanceof \DateTime) {
                        $val = $val->format('j/n/Y');
                    }
                    $resultName .= sprintf(' (%s value: %s)', $filterKey, $val);
                }
            }
        }

        $context->setResult(['name' => $resultName]);
    }
}
