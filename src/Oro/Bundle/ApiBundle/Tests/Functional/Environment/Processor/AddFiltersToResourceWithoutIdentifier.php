<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * This processor is used to test a possibility to add filters to resources without identifiers.
 */
class AddFiltersToResourceWithoutIdentifier implements ProcessorInterface
{
    public const FILTER1_KEY = 'filter1';
    public const FILTER2_KEY = 'filter2';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();
        if (!$filters->has(self::FILTER1_KEY)) {
            $filters->add(
                self::FILTER1_KEY,
                new StandaloneFilter(DataType::STRING, 'Test Filter 1')
            );
        }
        if (!$filters->has(self::FILTER2_KEY)) {
            $filters->add(
                self::FILTER2_KEY,
                new StandaloneFilter(DataType::DATE, 'Test Filter 2')
            );
        }
    }
}
