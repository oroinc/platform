<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Adds "scope" filter.
 */
class AddScopeFilter implements ProcessorInterface
{
    const FILTER_KEY = 'scope';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // the filter already exists
            return;
        }

        $filters->add(
            self::FILTER_KEY,
            new StandaloneFilterWithDefaultValue(
                DataType::STRING,
                'Configuration Scope',
                'user'
            )
        );
    }
}
