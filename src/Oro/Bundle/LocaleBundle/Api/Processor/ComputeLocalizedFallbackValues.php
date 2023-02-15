<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueCompleter;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueExtractorInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of fields that are represented by to-many association to LocalizedFallbackValue.
 * @see \Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueCompleter
 */
class ComputeLocalizedFallbackValues implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private LocalizationHelper $localizationHelper;
    private LocalizedFallbackValueExtractorInterface $valueExtractor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        LocalizedFallbackValueExtractorInterface $valueExtractor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
        $this->valueExtractor = $valueExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $fieldNames = $config->get(LocalizedFallbackValueCompleter::LOCALIZED_FALLBACK_VALUE_FIELDS);
        if (!$fieldNames) {
            return;
        }

        $data = $context->getData();
        [$values, $idsPerField] = $this->extractIds($fieldNames, $config, $context, $data);
        if (!$values) {
            return;
        }

        foreach ($idsPerField as $key => [$itemIdsPerField, $classesPerField]) {
            $valuesPerField = $this->groupLocalizedFallbackValues($values, $itemIdsPerField, $classesPerField);
            foreach ($fieldNames as $fieldName) {
                $value = null;
                if (isset($valuesPerField[$fieldName])) {
                    $value = $this->getLocalizedValue($valuesPerField[$fieldName]);
                }
                $data[$key][$fieldName] = $value;
            }
        }
        $context->setData($data);
    }

    /**
     * @param array                      $fieldNames
     * @param EntityDefinitionConfig     $config
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     *
     * @return array [values ([class name => [id => value, ...], ...]), idsPerField]
     */
    private function extractIds(
        array $fieldNames,
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context,
        array $data
    ): array {
        $idsPerClass = [];
        $idsPerField = [];
        foreach ($data as $key => $item) {
            [$itemIds, $itemIdsPerField, $classesPerField] = $this->getLocalizedFallbackValueIds(
                $fieldNames,
                $config,
                $context,
                $item
            );
            if (!empty($itemIds)) {
                foreach ($itemIds as $class => $classIds) {
                    $idsPerClass[$class][] = $classIds;
                }
                $idsPerField[$key] = [$itemIdsPerField, $classesPerField];
            }
        }

        return [$this->loadLocalizedFallbackValues($idsPerClass), $idsPerField];
    }

    /**
     * @param string[]                   $fieldNames
     * @param EntityDefinitionConfig     $config
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     *
     * @return array [ids, idsPerField ([field name => id, ...])]
     */
    private function getLocalizedFallbackValueIds(
        array $fieldNames,
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context,
        array $data
    ): array {
        $associationMappings = $this->getAssociationMappings($context);

        $ids = [];
        $idsPerField = [];
        $classesPerField = [];
        foreach ($fieldNames as $fieldName) {
            if (!$context->isFieldRequested($fieldName, $data)) {
                continue;
            }
            $field = $config->getField($fieldName);
            if (!$field) {
                continue;
            }
            $dependsOn = $field->getDependsOn();
            if (!$dependsOn || count($dependsOn) !== 1) {
                continue;
            }
            $dependsOn = reset($dependsOn);
            $dependsOnFieldName = $config->findFieldNameByPropertyPath($dependsOn);
            if ($dependsOnFieldName && !empty($data[$dependsOnFieldName])) {
                $className = $associationMappings[$dependsOn]['targetEntity'];
                $classesPerField[$fieldName] = $className;
                foreach ($data[$dependsOnFieldName] as $item) {
                    $id = $item['id'];
                    $ids[$className][] = $id;
                    $idsPerField[$fieldName][] = $id;
                }
            }
        }

        return [$ids, $idsPerField, $classesPerField];
    }

    private function getAssociationMappings(CustomizeLoadedDataContext $context): array
    {
        $entityClass = $this->doctrineHelper->getManageableEntityClass($context->getClassName(), $context->getConfig());
        if (!$entityClass) {
            return [];
        }

        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);

        return $metadata ? $metadata->getAssociationMappings() : [];
    }

    /**
     * @param array $idsPerClass
     *
     * @return array [class name => [id => value, ...], ...]
     */
    private function loadLocalizedFallbackValues(array $idsPerClass): array
    {
        $result = [];
        foreach ($idsPerClass as $class => $classIds) {
            $classIds = array_unique(array_merge(...$classIds));

            /** @var AbstractLocalizedFallbackValue[] $values */
            $values = $this->doctrineHelper
                ->createQueryBuilder($class, 'e')
                ->where('e.id IN (:ids)')
                ->setParameter('ids', $classIds)
                ->getQuery()
                ->getResult();

            foreach ($values as $value) {
                $result[$class][$value->getId()] = $value;
            }
        }

        return $result;
    }

    /**
     * @param LocalizedFallbackValue[] $values          [class name => [id => value, ...], ...]
     * @param array                    $idsPerField     [field name => [localized fallback value ID. ...], ...]
     * @param array                    $classesPerField [field name => class name, ...]
     *
     * @return array [field name => localized fallback value collection, ...]
     */
    private function groupLocalizedFallbackValues(array $values, array $idsPerField, array $classesPerField): array
    {
        $result = [];
        foreach ($idsPerField as $fieldName => $ids) {
            $className = $classesPerField[$fieldName];

            $collection = new ArrayCollection();
            foreach ($ids as $id) {
                if (isset($values[$className][$id])) {
                    $collection->add($values[$className][$id]);
                }
            }
            if (!$collection->isEmpty()) {
                $result[$fieldName] = $collection;
            }
        }

        return $result;
    }

    private function getLocalizedValue(ArrayCollection $values): ?string
    {
        $value = $this->localizationHelper->getLocalizedValue($values);
        if (null !== $value) {
            $value = $this->valueExtractor->extractValue($value);
        }

        return $value;
    }
}
