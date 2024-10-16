<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds "fields" filters that can be used to specify which fields of primary
 * or related entities should be returned.
 * As this filter has influence on the entity configuration, it is handled by a separate processor.
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleFieldsFilter
 */
class AddFieldsFilter implements ProcessorInterface
{
    public const FILTER_DESCRIPTION_TEMPLATE =
        'A list of fields of \'%s\' entity that will be returned in the response.';

    private FilterNamesRegistry $filterNamesRegistry;
    private ValueNormalizer $valueNormalizer;

    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $config = $context->getConfig();
        if (null === $config || !$config->isFieldsetEnabled()) {
            // the "fields" filter is disabled
            return;
        }

        $filterNames = $this->filterNamesRegistry->getFilterNames($context->getRequestType());
        $filterGroupName = $filterNames->getFieldsFilterGroupName();
        if (!$filterGroupName) {
            // the "fields" filter is not supported
            return;
        }

        $filterCollection = $context->getFilters();
        if ($filterCollection->has($filterGroupName)) {
            // filters have been already set
            return;
        }

        $filterTemplate = $filterNames->getFieldsFilterTemplate();
        if (ApiActionGroup::INITIALIZE === $context->getLastGroup()) {
            // add "fields" filters for the primary entity and all associated entities,
            // it is required to display them on the API Sandbox
            $this->addFiltersForDocumentation($context, $filterTemplate, $config->isInclusionEnabled());
        } else {
            // add all requested "fields" filters
            $allFilterValues = $context->getFilterValues()->getGroup($filterGroupName);
            foreach ($allFilterValues as $filterValues) {
                $this->addFilter($filterTemplate, $filterCollection, end($filterValues)->getPath());
            }
        }
    }

    private function addFiltersForDocumentation(
        Context $context,
        string $filterTemplate,
        bool $isInclusionEnabled
    ): void {
        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // the metadata does not exist
            return;
        }

        $filterCollection = $context->getFilters();
        $requestType = $context->getRequestType();

        // the "fields" filter for the primary entity
        $this->addFilterForEntityClass($filterTemplate, $filterCollection, $context->getClassName(), $requestType);

        // the "fields" filters for associated entities
        if (!$isInclusionEnabled) {
            return;
        }
        $config = $context->getConfig();
        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            $fieldConfig = $config->getField($associationName);
            if (null !== $fieldConfig && DataType::isAssociationAsField($fieldConfig->getDataType())) {
                continue;
            }
            $targetClasses = $association->getAcceptableTargetClassNames();
            foreach ($targetClasses as $targetClass) {
                $this->addFilterForEntityClass($filterTemplate, $filterCollection, $targetClass, $requestType);
            }
        }
    }

    private function addFilterForEntityClass(
        string $filterTemplate,
        FilterCollection $filterCollection,
        string $entityClass,
        RequestType $requestType
    ): void {
        $entityType = $this->convertToEntityType($entityClass, $requestType);
        if ($entityType) {
            $this->addFilter($filterTemplate, $filterCollection, $entityType);
        }
    }

    private function addFilter(string $filterTemplate, FilterCollection $filterCollection, string $entityType): void
    {
        $key = sprintf($filterTemplate, $entityType);
        if (null === $filterCollection->get($key)) {
            $filter = new FieldsFilter(
                DataType::STRING,
                sprintf(self::FILTER_DESCRIPTION_TEMPLATE, $entityType)
            );
            $filter->setArrayAllowed(true);
            $filterCollection->add($key, $filter, false);
        }
    }

    private function convertToEntityType(string $entityClass, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::tryConvertToEntityType($this->valueNormalizer, $entityClass, $requestType);
    }
}
