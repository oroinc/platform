<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Replaces sorting by "id" field with sorting by real entity identifier field name.
 */
class CorrectSortValue implements ProcessorInterface
{
    const SORT_FILTER_KEY = 'sort';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(DoctrineHelper $doctrineHelper, ValueNormalizer $valueNormalizer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
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

        $filterValues = $context->getFilterValues();
        $sortFilterValue = $filterValues->get(self::SORT_FILTER_KEY);
        if (null === $sortFilterValue) {
            $sortFilter = $context->getFilters()->get(self::SORT_FILTER_KEY);
            if ($sortFilter instanceof StandaloneFilterWithDefaultValue) {
                $defaultValue = $sortFilter->getDefaultValueString();
                if (!empty($defaultValue)) {
                    $defaultValue = $this->valueNormalizer->normalizeValue(
                        $defaultValue,
                        $sortFilter->getDataType(),
                        $context->getRequestType(),
                        $sortFilter->isArrayAllowed()
                    );
                    $sortFilterValue = new FilterValue(self::SORT_FILTER_KEY, $defaultValue);
                    $filterValues->set(self::SORT_FILTER_KEY, $sortFilterValue);
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
    protected function normalizeValue($value, $entityClass, EntityDefinitionConfig $config = null)
    {
        if (empty($value) || !is_array($value)) {
            return $value;
        }

        $result = [];
        foreach ($value as $fieldName => $direction) {
            if ('id' === $fieldName) {
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
    protected function addEntityIdentifierFieldNames(
        array &$result,
        $entityClass,
        $direction,
        EntityDefinitionConfig $config = null
    ) {
        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        foreach ($idFieldNames as $propertyPath) {
            if (null !== $config) {
                $fieldName = $config->findFieldNameByPropertyPath($propertyPath);
                if ($fieldName) {
                    $propertyPath = $fieldName;
                }
            }
            $result[$propertyPath] = $direction;
        }
    }
}
