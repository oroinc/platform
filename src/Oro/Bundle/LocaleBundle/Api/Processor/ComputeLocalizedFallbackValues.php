<?php

namespace Oro\Bundle\LocaleBundle\Api\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueCompleter;
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $fieldNames = $config->get(LocalizedFallbackValueCompleter::LOCALIZED_FALLBACK_VALUE_FIELDS);
        if (!$fieldNames) {
            return;
        }

        list($ids, $idsPerField) = $this->getLocalizedFallbackValueIds($fieldNames, $config, $context, $data);
        if (empty($ids)) {
            return;
        }

        $valuesPerField = $this->groupLocalizedFallbackValues(
            $this->loadLocalizedFallbackValues(array_unique($ids)),
            $idsPerField
        );
        foreach ($fieldNames as $fieldName) {
            $data[$fieldName] = isset($valuesPerField[$fieldName])
                ? $this->getLocalizedValue($valuesPerField[$fieldName])
                : null;
        }
        $context->setResult($data);
    }

    /**
     * @param string[]                   $fieldNames
     * @param EntityDefinitionConfig     $config
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     *
     * @return array [ids, idsPerField]
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
     * @return LocalizedFallbackValue[]
     */
    private function loadLocalizedFallbackValues(array $ids): array
    {
        return $this->doctrineHelper
            ->getEntityManagerForClass(LocalizedFallbackValue::class)
            ->createQueryBuilder()
            ->from(LocalizedFallbackValue::class, 'e')
            ->select('e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LocalizedFallbackValue[] $values
     * @param array                    $idsPerField [field name => [localized fallback value ID. ...], ...]
     *
     * @return array [field name => localized fallback value collection, ...]
     */
    private function groupLocalizedFallbackValues(array $values, array $idsPerField): array
    {
        $valuesMap = [];
        foreach ($values as $value) {
            $valuesMap[$value->getId()] = $value;
        }

        $result = [];
        foreach ($idsPerField as $fieldName => $ids) {
            $collection = new ArrayCollection();
            foreach ($ids as $id) {
                if (isset($valuesMap[$id])) {
                    $collection->add($valuesMap[$id]);
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
            $value = (string)$value;
        }

        return $value;
    }
}
