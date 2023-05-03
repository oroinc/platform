<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that "meta" filter is supported.
 */
class ValidateMetaPropertyFilterSupported implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $filterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getMetaPropertyFilterName();
        if (null === $context->getFilterValues()->get($filterName)) {
            // nothing to validate because meta properties were not requested
            return;
        }

        $config = $context->getConfig();
        if (null === $config || !$config->isMetaPropertiesEnabled()) {
            $context->addError(
                Error::createValidationError(Constraint::FILTER, 'The filter is not supported.')
                    ->setSource(ErrorSource::createByParameter($filterName))
            );
        }
    }
}
