<?php

namespace Oro\Bundle\ConfigBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "scope" filter.
 */
class AddScopeFilter implements ProcessorInterface
{
    public const FILTER_KEY = 'scope';

    private array $scopes;

    public function __construct(array $scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * {@inheritDoc}
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
                sprintf('The configuration scope. Possible values: %s.', implode(', ', $this->scopes)),
                'user'
            )
        );
    }
}
