<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Validates that "meta" filter is supported and all requested meta properties are allowed.
 */
class ValidateMetaPropertyFilterSupported implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;

    public function __construct(FilterNamesRegistry $filterNamesRegistry)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritDoc}
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
        if (null !== $config && !$context->hasErrors()) {
            $this->validateRequestedMetaProperties($context, $config, $filterName);
        }
    }

    private function validateRequestedMetaProperties(
        Context $context,
        EntityDefinitionConfig $config,
        string $filterName
    ): void {
        $disabledMetaProperties = $config->getDisabledMetaProperties();
        if (!$disabledMetaProperties) {
            return;
        }

        /** @var MetaPropertiesConfigExtra|null $configExtra */
        $configExtra = $context->getConfigExtra(MetaPropertiesConfigExtra::NAME);
        if (null === $configExtra) {
            return;
        }

        $requestedMetaProperties = $configExtra->getMetaPropertyNames();
        foreach ($requestedMetaProperties as $name) {
            if (\in_array($name, $disabledMetaProperties, true)) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER, sprintf(
                        'The "%s" is not allowed meta property. Allowed properties: %s.',
                        $name,
                        implode(
                            ', ',
                            $this->getAllowedMetaProperties(
                                $context->getFilters()->get($filterName),
                                $disabledMetaProperties
                            )
                        )
                    ))->setSource(ErrorSource::createByParameter($filterName))
                );
            }
        }
    }

    private function getAllowedMetaProperties(?MetaPropertyFilter $filter, array $disabledMetaProperties): array
    {
        if (null === $filter) {
            return [];
        }

        return array_diff(array_keys($filter->getAllowedMetaProperties()), $disabledMetaProperties);
    }
}
