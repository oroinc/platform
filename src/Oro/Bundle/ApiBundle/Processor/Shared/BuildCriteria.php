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
        $processedFilterKeys = [];

        $filters = $context->getFilters();
        /** @var FilterInterface $filter */
        foreach ($filters as $filterKey => $filter) {
            if ($filterValues->has($filterKey)) {
                $filter->apply($criteria, $filterValues->get($filterKey));

                $processedFilterKeys[$filterKey] = true;
            }
        }

        // process unknown filters
        $filterValues = $filterValues->getGroup('filter');
        foreach ($filterValues as $filterKey => $filterValue) {
            if (isset($processedFilterKeys[$filterKey])) {
                continue;
            }
            if ($filters->has($filterKey)) {
                continue;
            }

            $filter = $this->getFilter($filterValue, $context);
            if ($filter) {
                $filter->apply($criteria, $filterValue);
                $filters->add($filterKey, $filter);
            } else {
                $context->addError(
                    Error::createValidationError(
                        Constraint::FILTER,
                        sprintf('Filter "%s" is not supported.', $filterKey)
                    )->setSource(ErrorSource::createByParameter($filterKey))
                );
            }
        }
    }

    /**
     * @param FilterValue $filterValue
     * @param Context     $context
     *
     * @return ComparisonFilter|null
     */
    protected function getFilter(FilterValue $filterValue, Context $context)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (!$metadata) {
            return null;
        }

        $fieldName = $filterValue->getPath();
        $path = explode('.', $fieldName);
        $associations = null;
        if (count($path) > 1) {
            $fieldName = array_pop($path);
            list($filtersConfig, $associations) = $this->getAssociationFilters($path, $context, $metadata);
        } else {
            $filtersConfig = $context->getConfigOfFilters();
        }
        if (!$filtersConfig) {
            return null;
        }
        $filterConfig = $filtersConfig->getField($fieldName);
        if (!$filterConfig) {
            return null;
        }

        $filterValueField = $filterConfig->getPropertyPath() ?: $fieldName;
        if ($associations) {
            $filterValueField = $associations . '.' . $filterValueField;
        }

        $filter = new ComparisonFilter($filterConfig->getDataType());
        $filter->setField($filterValueField);

        return $filter;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array [filters config, associations]
     */
    protected function getAssociationFilters(array $path, Context $context, ClassMetadata $metadata)
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new FiltersConfigExtra()
        ];

        $config = $context->getConfig();
        $filters = null;
        $associations = [];

        foreach ($path as $fieldName) {
            if (!$config->hasField($fieldName)) {
                return [null, null];
            }

            $associationName = $config->getField($fieldName)->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($associationName)) {
                return [null, null];
            }

            $targetClass = $metadata->getAssociationTargetClass($associationName);
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass, false);
            if (!$metadata) {
                return [null, null];
            }

            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return [null, null];
            }

            $config = $targetConfig->getDefinition();
            $filters = $targetConfig->getFilters();
            $associations[] = $associationName;
        }

        return [$filters, implode('.', $associations)];
    }
}
