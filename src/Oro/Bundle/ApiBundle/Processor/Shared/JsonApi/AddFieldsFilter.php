<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Adds "fields[]" filters.
 * These filters can be used to specify which fields of primary
 * or related entities should be returned.
 */
class AddFieldsFilter implements ProcessorInterface
{
    const FILTER_KEY          = 'fields';
    const FILTER_KEY_TEMPLATE = 'fields[%s]';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

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

        if (!$context->hasConfigExtra(DescriptionsConfigExtra::NAME)) {
            /**
             * this filter has descriptive nature and it should be added to the list of filters
             * only if descriptions are requested
             * actually a filtering by this filter is performed by
             * @see Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\HandleFieldsFilter
             */
            return;
        }

        $this->addFilter($filters, $context->getClassName(), $context->getRequestType());

        $associations = $context->getMetadata()->getAssociations();
        foreach ($associations as $association) {
            $targetClasses = $association->getAcceptableTargetClassNames();
            foreach ($targetClasses as $targetClass) {
                $this->addFilter($filters, $targetClass, $context->getRequestType());
            }
        }
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityClass
     * @param RequestType      $requestType
     */
    protected function addFilter(FilterCollection $filters, $entityClass, RequestType $requestType)
    {
        $entityType = $this->convertToEntityType($entityClass, $requestType);
        if ($entityType) {
            $filter = new FieldsFilter(
                DataType::STRING,
                sprintf('A list of fields for the \'%s\' entity to be returned.', $entityType)
            );
            $filter->setArrayAllowed(true);

            $filters->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $entityType),
                $filter
            );
        }
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function convertToEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}
