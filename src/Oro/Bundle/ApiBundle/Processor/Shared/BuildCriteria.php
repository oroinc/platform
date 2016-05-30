<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Applies all requested filters to the Criteria object.
 */
class BuildCriteria implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ConfigProvider $configProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ConfigProvider $configProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configProvider = $configProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $filterValues = $context->getFilterValues();

        $filters = $context->getFilters();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $filter->apply($criteria, $filterValues->get($filterKey));
                $filterValues->remove($filterKey);
            }
        }

        // Process unknown filters
        $filterValues = $filterValues->getGroup('filter');
        foreach ($filterValues as $filterKey => $filterValue) {
            /** @var FilterValue $filterValue */
            if ($filters->has($filterKey)) {
                continue;
            }

            $filterValueDataType = $this->getFilterValueDataType($context, $filterValue);
            if (!$filterValueDataType) {
                $context->addError(
                    Error::createValidationError(
                        Constraint::FILTER,
                        sprintf('Filter "%s" is not supported.', $filterKey)
                    )->setSource(ErrorSource::createByParameter($filterKey))
                );
                continue;
            }

            /** @var ComparisonFilter $filter */
            $filter = new ComparisonFilter($filterValueDataType);
            $filter->setField($filterValue->getPath());
            $filter->apply($criteria, $filterValue);

            $filters->add($filterKey, $filter);
        }
    }

    /**
     * @param Context     $context
     * @param FilterValue $filterValue
     *
     * @return string|null
     */
    protected function getFilterValueDataType($context, $filterValue)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (!$metadata) {
            return null;
        }

        $filterParts = explode('.', $filterValue->getPath());
        $filterFieldName = array_pop($filterParts);

        //all parts of filter path, except last one should be an associations
        foreach ($filterParts as $filterPart) {
            if (!$metadata->hasAssociation($filterPart)) {
                return null;
            }

            $metadata = $this->doctrineHelper->getEntityMetadataForClass(
                $metadata->getAssociationTargetClass($filterPart)
            );
        }

        //the last part of filter path should exist in metadata.
        if (!$metadata->hasAssociation($filterFieldName) && !$metadata->hasField($filterFieldName)) {
            return null;
        }

        $className = $metadata->getName();
        if ($metadata->hasAssociation($filterFieldName)) {
            $className = $metadata->getAssociationTargetClass($filterFieldName);
        }

        $config = $this->configProvider->getConfig(
            $className,
            $context->getVersion(),
            $context->getRequestType(),
            [
                new EntityDefinitionConfigExtra(),
                new FiltersConfigExtra()
            ]
        );

        if ($config->hasFilters() && $config->getFilters()->hasField($filterFieldName)) {
            $filterFieldConfig = $config->getFilters()->getField($filterFieldName);

            return $filterFieldConfig->getDataType();
        }

        return null;
    }
}
