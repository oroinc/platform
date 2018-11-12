<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Processor\Context;
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
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\HandleFieldsFilter
 */
class AddFieldsFilter implements ProcessorInterface
{
    public const FILTER_KEY                  = 'fields';
    public const FILTER_KEY_TEMPLATE         = 'fields[%s]';
    public const FILTER_DESCRIPTION_TEMPLATE =
        'A list of fields of \'%s\' entity that will be returned in the response.';

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // filters have been already set
            return;
        }

        $config = $context->getConfig();
        if (null === $config || !$config->isFieldsetEnabled()) {
            // the "fields" filter is disabled
            return;
        }

        if ('initialize' === $context->getLastGroup()) {
            // add "fields" filters for the primary entity and all associated entities
            // this is required to display them on the API Sandbox
            $this->addFiltersForDocumentation($context);
        } else {
            // add all requested "fields" filters
            $filterValues = $context->getFilterValues()->getGroup(self::FILTER_KEY);
            foreach ($filterValues as $filterValue) {
                $this->addFilter($filters, $filterValue->getPath());
            }
        }
    }

    /**
     * @param Context $context
     */
    private function addFiltersForDocumentation(Context $context): void
    {
        $metadata = $context->getMetadata();
        if (null === $metadata) {
            // the metadata does not exist
            return;
        }

        $filters = $context->getFilters();
        $requestType = $context->getRequestType();

        // the "fields" filter for the primary entity
        $this->addFilterForEntityClass($filters, $context->getClassName(), $requestType);

        // the "fields" filters for associated entities
        $config = $context->getConfig();
        $associations = $metadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            $fieldConfig = $config->getField($associationName);
            if (null !== $fieldConfig && DataType::isAssociationAsField($fieldConfig->getDataType())) {
                continue;
            }
            $targetClasses = $association->getAcceptableTargetClassNames();
            foreach ($targetClasses as $targetClass) {
                $this->addFilterForEntityClass($filters, $targetClass, $requestType);
            }
        }
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityClass
     * @param RequestType      $requestType
     */
    private function addFilterForEntityClass(
        FilterCollection $filters,
        string $entityClass,
        RequestType $requestType
    ): void {
        $entityType = $this->convertToEntityType($entityClass, $requestType);
        if ($entityType) {
            $this->addFilter($filters, $entityType);
        }
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityType
     */
    private function addFilter(FilterCollection $filters, string $entityType): void
    {
        $filter = new FieldsFilter(
            DataType::STRING,
            sprintf(self::FILTER_DESCRIPTION_TEMPLATE, $entityType)
        );
        $filter->setArrayAllowed(true);

        $filters->add(
            sprintf(self::FILTER_KEY_TEMPLATE, $entityType),
            $filter
        );
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    private function convertToEntityType(string $entityClass, RequestType $requestType): ?string
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}
