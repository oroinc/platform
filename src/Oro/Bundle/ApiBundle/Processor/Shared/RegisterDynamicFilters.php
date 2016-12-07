<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterFactoryInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\RequestAwareFilterInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Registers all allowed dynamic filters.
 */
class RegisterDynamicFilters extends RegisterFilters
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param FilterFactoryInterface $filterFactory
     * @param DoctrineHelper         $doctrineHelper
     * @param ConfigProvider         $configProvider
     */
    public function __construct(
        FilterFactoryInterface $filterFactory,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider
    ) {
        parent::__construct($filterFactory);
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $allFilterValues = $context->getFilterValues();
        $filterValues = $allFilterValues->getGroup('filter');
        if (!empty($filterValues)) {
            $filters = $context->getFilters();
            $knownFilterKeys = [];
            $renameMap = [];
            foreach ($filters as $filterKey => $filter) {
                if ($filter instanceof SelfIdentifiableFilterInterface) {
                    try {
                        $actualFilterKey = $filter->searchFilterKey($filterValues);
                        if ($actualFilterKey) {
                            $knownFilterKeys[$actualFilterKey] = true;
                            $renameMap[$filterKey] = $actualFilterKey;
                        }
                    } catch (InvalidFilterValueKeyException $e) {
                        $context->addError(
                            Error::createValidationError(Constraint::FILTER)
                                ->setInnerException($e)
                                ->setSource(
                                    ErrorSource::createByParameter(
                                        $e->getFilterValue()->getSourceKey() ?: $filterKey
                                    )
                                )
                        );
                    }
                } elseif ($allFilterValues->has($filterKey)) {
                    $knownFilterKeys[$filterKey] = true;
                }
            }
            $this->renameFilters($filters, $renameMap);
            $this->addDynamicFilters($filters, $filterValues, $knownFilterKeys, $context);
        }
    }

    /**
     * @param FilterCollection $filters
     * @param array            $renameMap
     */
    protected function renameFilters($filters, $renameMap)
    {
        foreach ($renameMap as $filterKey => $newFilterKey) {
            $filter = $filters->get($filterKey);
            $filters->remove($filterKey);
            $filters->add($newFilterKey, $filter);
        }
    }

    /**
     * @param FilterCollection $filters
     * @param FilterValue[]    $filterValues
     * @param string[]         $knownFilterKeys
     * @param Context          $context
     */
    protected function addDynamicFilters($filters, $filterValues, $knownFilterKeys, $context)
    {
        foreach ($filterValues as $filterKey => $filterValue) {
            if (isset($knownFilterKeys[$filterKey])) {
                continue;
            }

            $filter = $this->getFilter($filterValue->getPath(), $context);
            if ($filter) {
                $filters->add($filterKey, $filter);
            } else {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER, 'The filter is not supported.')
                        ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey() ?: $filterKey))
                );
            }
        }
    }

    /**
     * @param string  $propertyPath
     * @param Context $context
     *
     * @return StandaloneFilter|null
     */
    protected function getFilter($propertyPath, Context $context)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($context->getClassName(), false);
        if (!$metadata) {
            return null;
        }

        $filterInfo = $this->getFilterInfo($propertyPath, $metadata, $context);
        if (null === $filterInfo) {
            return null;
        }

        list($filterConfig, $propertyPath, $isCollection) = $filterInfo;
        $filter = $this->createFilter($filterConfig, $propertyPath);
        if (null !== $filter) {
            if ($filter instanceof RequestAwareFilterInterface) {
                $filter->setRequestType($context->getRequestType());
            }
            // @todo BAP-11881. Update this code when NEQ operator for to-many collection
            // will be implemented in Oro\Bundle\ApiBundle\Filter\ComparisonFilter
            if ($isCollection) {
                $filter->setSupportedOperators([StandaloneFilter::EQ]);
            }
        }

        return $filter;
    }

    /**
     * @param string        $propertyPath
     * @param ClassMetadata $metadata
     * @param Context       $context
     *
     * @return array|null [filter config, property path, is collection]
     */
    protected function getFilterInfo($propertyPath, ClassMetadata $metadata, Context $context)
    {
        $filtersConfig = null;
        $associationPropertyPath = null;
        $isCollection = false;

        $path = explode('.', $propertyPath);
        if (count($path) > 1) {
            $fieldName = array_pop($path);
            $associationInfo = $this->getAssociationInfo($path, $context, $metadata);
            if (null !== $associationInfo) {
                list($filtersConfig, $associationPropertyPath, $isCollection) = $associationInfo;
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
                $result = [$filterConfig, $propertyPath, $isCollection];
            }
        }

        return $result;
    }

    /**
     * @param string[]      $path
     * @param Context       $context
     * @param ClassMetadata $metadata
     *
     * @return array|null [filters config, association property path, is collection]
     */
    protected function getAssociationInfo(array $path, Context $context, ClassMetadata $metadata)
    {
        $targetConfigExtras = [
            new EntityDefinitionConfigExtra($context->getAction()),
            new FiltersConfigExtra()
        ];

        $config = $context->getConfig();
        $filters = null;
        $associationPath = [];
        $isCollection = false;

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

            if ($metadata->isCollectionValuedAssociation($associationPropertyPath)) {
                $isCollection = true;
            }

            $metadata = $this->doctrineHelper->getEntityMetadataForClass($targetClass);
            $config = $targetConfig->getDefinition();
            $filters = $targetConfig->getFilters();
            $associationPath[] = $associationPropertyPath;
        }

        return [$filters, implode('.', $associationPath), $isCollection];
    }
}
