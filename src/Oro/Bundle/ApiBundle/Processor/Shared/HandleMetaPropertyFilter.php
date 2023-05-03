<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether the "meta" filter exists,
 * and if so, adds the corresponding configuration extra into the context.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter
 */
class HandleMetaPropertyFilter implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $requestType = $context->getRequestType();
        $filterName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getMetaPropertyFilterName();

        /** @var MetaPropertyFilter|null $filter */
        $filter = $context->getFilters()->get($filterName);
        if (null === $filter) {
            // the "meta" filter is not supported
            return;
        }

        $filterValue = $context->getFilterValues()->get($filterName);
        if (null === $filterValue) {
            // meta properties were not requested
            return;
        }

        try {
            $names = $this->valueNormalizer->normalizeValue(
                $filterValue->getValue(),
                DataType::STRING,
                $requestType,
                true
            );
        } catch (\Exception $e) {
            $context->addError(
                $this->createInvalidFilterValueKeyError($filterValue->getSourceKey())
                    ->setInnerException($e)
            );

            return;
        }

        if ($context->hasErrors()) {
            // detected errors in the filter value
            return;
        }

        $names = (array)$names;
        $configExtra = $context->getConfigExtra(MetaPropertiesConfigExtra::NAME);
        if (null === $configExtra) {
            $configExtra = new MetaPropertiesConfigExtra();
            $context->addConfigExtra($configExtra);
        }

        $allowedMetaProperties = $filter->getAllowedMetaProperties();
        foreach ($names as $name) {
            if (\array_key_exists($name, $allowedMetaProperties)) {
                $configExtra->addMetaProperty($name, $allowedMetaProperties[$name]);
            } else {
                $context->addError($this->createInvalidFilterValueKeyError(
                    $filterValue->getSourceKey(),
                    sprintf(
                        'The "%s" value is not allowed. Allowed values: %s',
                        $name,
                        implode(', ', array_keys($allowedMetaProperties))
                    )
                ));
            }
        }
    }

    private function createInvalidFilterValueKeyError(string $filterKey, string $detail = null): Error
    {
        return Error::createValidationError(Constraint::FILTER, $detail)
            ->setSource(ErrorSource::createByParameter($filterKey));
    }
}
