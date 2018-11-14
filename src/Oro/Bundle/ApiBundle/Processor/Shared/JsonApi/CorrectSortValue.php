<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Replaces sorting by "id" field with sorting by real entity identifier field name.
 */
class CorrectSortValue implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     * @param FilterNamesRegistry $filterNamesRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer,
        FilterNamesRegistry $filterNamesRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->filterNamesRegistry = $filterNamesRegistry;
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

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $sortFilterName = $this->filterNamesRegistry
            ->getFilterNames($context->getRequestType())
            ->getSortFilterName();
        $filterValues = $context->getFilterValues();
        $sortFilterValue = $filterValues->get($sortFilterName);
        if (null === $sortFilterValue) {
            $sortFilter = $context->getFilters()->get($sortFilterName);
            if ($sortFilter instanceof StandaloneFilterWithDefaultValue) {
                $defaultValue = $sortFilter->getDefaultValueString();
                if (!empty($defaultValue)) {
                    $defaultValue = $this->valueNormalizer->normalizeValue(
                        $defaultValue,
                        $sortFilter->getDataType(),
                        $context->getRequestType(),
                        $sortFilter->isArrayAllowed()
                    );
                    $sortFilterValue = new FilterValue($sortFilterName, $defaultValue);
                    $filterValues->set($sortFilterName, $sortFilterValue);
                }
            }
        }
        if (null !== $sortFilterValue) {
            $sortFilterValue->setValue(
                $this->normalizeValue($sortFilterValue->getValue(), $entityClass, $context->getConfig())
            );
        }
    }

    /**
     * @param mixed                       $value
     * @param string                      $entityClass
     * @param EntityDefinitionConfig|null $config
     *
     * @return mixed
     */
    private function normalizeValue($value, $entityClass, ?EntityDefinitionConfig $config)
    {
        if (empty($value) || !is_array($value)) {
            return $value;
        }

        $result = [];
        foreach ($value as $fieldName => $direction) {
            if (JsonApiDoc::ID === $fieldName) {
                $this->addEntityIdentifierFieldNames($result, $entityClass, $direction, $config);
            } else {
                $result[$fieldName] = $direction;
            }
        }

        return $result;
    }

    /**
     * @param string[]                    $result
     * @param string                      $entityClass
     * @param string                      $direction
     * @param EntityDefinitionConfig|null $config
     */
    private function addEntityIdentifierFieldNames(
        array &$result,
        $entityClass,
        $direction,
        ?EntityDefinitionConfig $config
    ) {
        if (null === $config) {
            $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
            foreach ($idFieldNames as $propertyPath) {
                $result[$propertyPath] = $direction;
            }
        } else {
            $idFieldNames = $config->getIdentifierFieldNames();
            if (empty($idFieldNames)) {
                $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
                foreach ($idFieldNames as $propertyPath) {
                    $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
                    if ($fieldName) {
                        $propertyPath = $fieldName;
                    }
                    $result[$propertyPath] = $direction;
                }
            } else {
                foreach ($idFieldNames as $fieldName) {
                    $result[$fieldName] = $direction;
                }
            }
        }
    }
}
