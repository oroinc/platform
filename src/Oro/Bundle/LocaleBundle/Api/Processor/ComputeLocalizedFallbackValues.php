<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueCompleter;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueExtractorInterface;
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
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var LocalizedFallbackValueExtractorInterface */
    private $valueExtractor;

    /**
     * @param DoctrineHelper     $doctrineHelper
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, LocalizationHelper $localizationHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param LocalizedFallbackValueExtractorInterface $valueExtractor
     */
    public function setLocalizedFallbackValueExtractor(LocalizedFallbackValueExtractorInterface $valueExtractor)
    {
        $this->valueExtractor = $valueExtractor;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context)
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

        $ids = [];
        $idsPerField = [];
        foreach ($data as $key => $item) {
            [$itemIds, $itemIdsPerField] = $this->getLocalizedFallbackValueIds(
                $fieldNames,
                $config,
                $context,
                $item
            );
            if (!empty($itemIds)) {
                $ids[] = $itemIds;
                $idsPerField[$key] = $itemIdsPerField;
            }
        }
        if (empty($ids)) {
            return;
        }

        $ids = array_unique(array_merge(...$ids));
        $values = $this->loadLocalizedFallbackValues($ids);
        foreach ($idsPerField as $key => $itemIdsPerField) {
            $valuesPerField = $this->groupLocalizedFallbackValues($values, $itemIdsPerField);
            foreach ($fieldNames as $fieldName) {
                $data[$key][$fieldName] = isset($valuesPerField[$fieldName])
                    ? $this->getLocalizedValue($valuesPerField[$fieldName])
                    : null;
            }
        }
        $context->setData($data);
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
        $ids = [];
        $idsPerField = [];
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
            $dependsOnFieldName = $config->findFieldNameByPropertyPath(reset($dependsOn));
            if ($dependsOnFieldName && !empty($data[$dependsOnFieldName])) {
                foreach ($data[$dependsOnFieldName] as $item) {
                    $id = $item['id'];
                    $ids[] = $id;
                    $idsPerField[$fieldName][] = $id;
                }
            }
        }

        return [$ids, $idsPerField];
    }

    /**
     * @param int[] $ids
     *
     * @return LocalizedFallbackValue[] [id => value, ...]
     */
    private function loadLocalizedFallbackValues(array $ids): array
    {
        /** @var LocalizedFallbackValue[] $values */
        $values = $this->doctrineHelper
            ->createQueryBuilder(LocalizedFallbackValue::class, 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($values as $value) {
            $result[$value->getId()] = $value;
        }

        return $result;
    }

    /**
     * @param LocalizedFallbackValue[] $values      [id => value, ...]
     * @param array                    $idsPerField [field name => [localized fallback value ID. ...], ...]
     *
     * @return array [field name => localized fallback value collection, ...]
     */
    private function groupLocalizedFallbackValues(array $values, array $idsPerField): array
    {
        $result = [];
        foreach ($idsPerField as $fieldName => $ids) {
            $collection = new ArrayCollection();
            foreach ($ids as $id) {
                if (isset($values[$id])) {
                    $collection->add($values[$id]);
                }
            }
            if (!$collection->isEmpty()) {
                $result[$fieldName] = $collection;
            }
        }

        return $result;
    }

    /**
     * @param ArrayCollection $values
     *
     * @return string|null
     */
    private function getLocalizedValue(ArrayCollection $values): ?string
    {
        $value = $this->localizationHelper->getLocalizedValue($values);
        if (null !== $value) {
            $value = null !== $this->valueExtractor
                ? $this->valueExtractor->extractValue($value)
                : (string)$value;
        }

        return $value;
    }
}
