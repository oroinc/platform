<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Registers all allowed nested filters and
 * if the filter group is specified, encloses filters keys
 * by the "{filter group}[%s]" pattern, e.g. "filter[%s]".
 */
class RegisterDynamicFilters extends RegisterFilters
{
    public const OPERATION_NAME = 'register_dynamic_filters';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /**
     * @param FilterFactoryInterface $filterFactory
     * @param DoctrineHelper         $doctrineHelper
     * @param ConfigProvider         $configProvider
     * @param FilterNamesRegistry    $filterNamesRegistry
     */
    public function __construct(
        FilterFactoryInterface $filterFactory,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        FilterNamesRegistry $filterNamesRegistry
    ) {
        parent::__construct($filterFactory);
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
        $this->filterNamesRegistry = $filterNamesRegistry;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the filters were already registered
            return;
        }

        $filterCollection = $context->getFilters();
        $allFilterValues = $context->getFilterValues();
        $filterGroup = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getDataFilterGroupName();
        if ('initialize' === $context->getLastGroup()) {
            $this->prepareFiltersForDocumentation($filterCollection, $filterGroup);
        } else {
            $renameMap = [];
            $knownFilterKeys = [];
            $filterValues = $this->getFilterValues($allFilterValues, $filterGroup);
            foreach ($filterCollection as $filterKey => $filter) {
                if ($filter instanceof SelfIdentifiableFilterInterface) {
                    try {
                        $actualFilterKeys = $filter->searchFilterKeys($filterValues);
                        if (empty($actualFilterKeys)) {
                            $renameMap[$filterKey] = null;
                        } else {
                            foreach ($actualFilterKeys as $actualFilterKey) {
                                $knownFilterKeys[$actualFilterKey] = true;
                                $renameMap[$filterKey][] = $actualFilterKey;
                            }
                        }
                    } catch (InvalidFilterValueKeyException $e) {
                        $actualFilterKey = $filterKey;
                        if ($filterGroup) {
                            $actualFilterKey = $filterCollection->getGroupedFilterKey($filterGroup, $filterKey);
                        }
                        $knownFilterKeys[$actualFilterKey] = true;
                        $renameMap[$filterKey] = null;
                        $context->addError($this->createInvalidFilterValueKeyError($actualFilterKey, $e));
                    }
                } elseif ($filterGroup) {
                    $groupedFilterKey = $filterCollection->getGroupedFilterKey($filterGroup, $filterKey);
                    if ($filter instanceof StandaloneFilterWithDefaultValue
                        || $allFilterValues->has($groupedFilterKey)
                    ) {
                        $knownFilterKeys[$groupedFilterKey] = true;
                        $renameMap[$filterKey][] = $groupedFilterKey;
                    } else {
                        $renameMap[$filterKey] = null;
                    }
                } elseif ($allFilterValues->has($filterKey)) {
                    $knownFilterKeys[$filterKey] = true;
                }
            }
            $this->renameFilters($filterCollection, $renameMap);
            $this->addDynamicFilters($filterCollection, $filterValues, $knownFilterKeys, $context);
        }
        if ($filterGroup) {
            $filterCollection->setDefaultGroupName($filterGroup);
            $allFilterValues->setDefaultGroupName($filterGroup);
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param FilterCollection $filterCollection
     * @param string|null      $filterGroup
     */
    private function prepareFiltersForDocumentation(FilterCollection $filterCollection, $filterGroup): void
    {
        if (!$filterGroup) {
            return;
        }

        $filters = $filterCollection->all();
        foreach ($filters as $filterKey => $filter) {
            $filterCollection->remove($filterKey);
            $filterCollection->add(
                $filterCollection->getGroupedFilterKey($filterGroup, $filterKey),
                $filter
            );
        }
    }

    /**
     * @param FilterValueAccessorInterface $allFilterValues
     * @param string|null                  $filterGroup
     *
     * @return FilterValue[]
     */
    private function getFilterValues(FilterValueAccessorInterface $allFilterValues, $filterGroup): array
    {
        if ($filterGroup) {
            return $allFilterValues->getGroup($filterGroup);
        }

        return $allFilterValues->getAll();
    }

    /**
     * @param string                         $filterKey
     * @param InvalidFilterValueKeyException $e
     *
     * @return Error
     */
    private function createInvalidFilterValueKeyError(string $filterKey, InvalidFilterValueKeyException $e): Error
    {
        return Error::createValidationError(Constraint::FILTER)
            ->setInnerException($e)
            ->setSource(
                ErrorSource::createByParameter(
                    $e->getFilterValue()->getSourceKey() ?: $filterKey
                )
            );
    }

    /**
     * @param FilterCollection $filterCollection
     * @param array            $renameMap
     */
    private function renameFilters(FilterCollection $filterCollection, array $renameMap): void
    {
        foreach ($renameMap as $filterKey => $newFilterKeys) {
            if (null !== $newFilterKeys) {
                $filter = $filterCollection->get($filterKey);
                foreach ($newFilterKeys as $newFilterKey) {
                    $filterCollection->add($newFilterKey, $filter);
                }
            }
            $filterCollection->remove($filterKey);
        }
    }

    /**
     * @param FilterCollection $filterCollection
     * @param FilterValue[]    $filterValues
     * @param string[]         $knownFilterKeys
     * @param Context          $context
     */
    private function addDynamicFilters(
        FilterCollection $filterCollection,
        array $filterValues,
        array $knownFilterKeys,
        Context $context
    ): void {
        foreach ($filterValues as $filterKey => $filterValue) {
            if (isset($knownFilterKeys[$filterKey])) {
                continue;
            }

            $filter = $this->getFilter($filterValue->getPath(), $context);
            if ($filter) {
                $filterCollection->add($filterKey, $filter);
            }
        }
    }

    /**
     * @param string  $propertyPath
     * @param Context $context
     *
     * @return StandaloneFilter|null
     */
    private function getFilter(string $propertyPath, Context $context): ?StandaloneFilter
    {
        $entityClass = $this->doctrineHelper->getManageableEntityClass(
            $context->getClassName(),
            $context->getConfig()
        );
        if (!$entityClass) {
            // only manageable entities or resources based on manageable entities are supported
            return null;
        }

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $filterInfo = $this->getFilterInfo($propertyPath, $metadata, $context);
        if (null === $filterInfo) {
            return null;
        }

        list($filterConfig, $propertyPath) = $filterInfo;

        return $this->createFilter($filterConfig, $propertyPath, $context);
    }

    /**
     * @param string        $propertyPath
     * @param ClassMetadata $metadata
     * @param Context       $context
     *
     * @return array|null [filter config, property path]
     */
    private function getFilterInfo(string $propertyPath, ClassMetadata $metadata, Context $context): ?array
    {
        $filtersConfig = null;
        $associationPropertyPath = null;

        $path = \explode('.', $propertyPath);
        if (\count($path) > 1) {
            $fieldName = \array_pop($path);
            $associationInfo = $this->getAssociationInfo($path, $context, $metadata);
            if (null !== $associationInfo) {
                list($filtersConfig, $associationPropertyPath) = $associationInfo;
            }
        } else {
            $fieldName = $propertyPath;
            $filtersConfig = $context->getConfigOfFilters();
        }

        $result = null;
        if ($filtersConfig) {
            $filterConfig = $filtersConfig->getField($fieldName);
            if ($filterConfig) {
                $propertyPath = $filterConfig->getPropertyPath($fieldName);
                if ($associationPropertyPath) {
                    $propertyPath = $associationPropertyPath . '.' . $propertyPath;
                }
                $result = [$filterConfig, $propertyPath];
            }
        }

        return $result;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array|null [filters config, association property path]
     */
    private function getAssociationInfo(array $path, Context $context, ClassMetadata $metadata): ?array
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new FiltersConfigExtra()
        ];

        $config = $context->getConfig();
        $filters = null;
        $associationPath = [];

        foreach ($path as $fieldName) {
            $field = $config->getField($fieldName);
            if (null === $field) {
                return null;
            }

            $associationPropertyPath = $field->getPropertyPath($fieldName);
            if (!$metadata->hasAssociation($associationPropertyPath)) {
                return null;
            }

            $targetClass = $metadata->getAssociationTargetClass($associationPropertyPath);
            $targetConfig = $this->configProvider->getConfig(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfigExtras
            );
            if (!$targetConfig->hasDefinition()) {
                return null;
            }

            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
            $config = $targetConfig->getDefinition();
            $filters = $targetConfig->getFilters();
            $associationPath[] = $associationPropertyPath;
        }

        return [$filters, \implode('.', $associationPath)];
    }
}
