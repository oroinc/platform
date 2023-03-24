<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether the "fields" filters exist,
 * and if so, adds the corresponding configuration extra into the context.
 * These filters are used to specify which fields of primary
 * or related entities should be returned.
 */
class HandleFieldsFilter implements ProcessorInterface
{
    private FilterNamesRegistry $filterNamesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasConfigExtra(FilterFieldsConfigExtra::NAME)) {
            // the "fields" filters are already processed
            return;
        }

        $requestType = $context->getRequestType();
        $filterGroupName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getFieldsFilterGroupName();
        if (!$filterGroupName) {
            // the "fields" filter is not supported
            return;
        }

        $filterValues = $context->getFilterValues()->getGroup($filterGroupName);
        if (!$filterValues) {
            // filtering of fields was not requested
            return;
        }

        $fields = $this->processFilterValues($filterValues, $filterGroupName, $requestType, $context);
        if ($context->hasErrors()) {
            // detected errors in the filter value
            return;
        }

        $context->addConfigExtra(new FilterFieldsConfigExtra($fields));
    }

    /**
     * @param FilterValue[] $filterValues
     * @param string        $filterGroupName
     * @param RequestType   $requestType
     * @param Context       $context
     *
     * @return array [entity type => [field name, ...], ...]
     */
    private function processFilterValues(
        array $filterValues,
        string $filterGroupName,
        RequestType $requestType,
        Context $context
    ): array {
        $fields = [];
        foreach ($filterValues as $filterValue) {
            $path = $filterValue->getPath();
            if (!$path || ($path === $filterGroupName && $filterValue->getSourceKey() === $path)) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER)
                        ->setDetail('An entity type is not specified.')
                        ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
                );
                continue;
            }
            if (!$this->isKnownEntityType($path, $requestType)) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER)
                        ->setDetail('An entity type is not known.')
                        ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
                );
                continue;
            }

            try {
                $fields[$path] = $this->normalizeFilterValue($filterValue, $requestType);
            } catch (\Exception $e) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER)
                        ->setInnerException($e)
                        ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
                );
            }
        }

        return $fields;
    }

    private function isKnownEntityType(string $entityType, RequestType $requestType): bool
    {
        return null !== ValueNormalizerUtil::tryConvertToEntityClass($this->valueNormalizer, $entityType, $requestType);
    }

    private function normalizeFilterValue(FilterValue $filterValue, RequestType $requestType): mixed
    {
        $value = $filterValue->getValue();
        if ('' === $value) {
            return [];
        }

        return (array)$this->valueNormalizer->normalizeValue($value, DataType::STRING, $requestType, true);
    }
}
