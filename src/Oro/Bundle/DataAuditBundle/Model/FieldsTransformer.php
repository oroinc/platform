<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;

/**
 * Transform AuditFields to a scalar data
 */
class FieldsTransformer
{
    /**
     * @param AbstractAuditField[] $fields
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getData(array $fields): array
    {
        $data = [];

        foreach ($fields as $field) {
            if (!$field instanceof AbstractAuditField) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected argument of type "%s", "%s" given',
                        AbstractAuditField::class,
                        is_object($field) ? get_class($field) : gettype($field)
                    )
                );
            }

            $newValue = $field->getNewValue();
            $oldValue = $field->getOldValue();
            $simpleTypes = [
                'date' => true,
                'date_immutable' => true,
                'datetime' => true,
                'datetimetz' => true,
                'time' => true,
                'array' => true,
                'jsonarray' => true,
            ];
            if (array_key_exists($field->getDataType(), $simpleTypes)) {
                $newValue = [
                    'value' => $newValue,
                    'type' => $field->getDataType(),
                ];
                $oldValue = [
                    'value' => $oldValue,
                    'type' => $field->getDataType(),
                ];
            }
            $data[$field->getField()] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];

            if (EntityPropertyInfo::methodExists($field, 'getCollectionDiffs')) {
                $collectionDiffs = $field->getCollectionDiffs();
                if ($collectionDiffs['added'] || $collectionDiffs['changed'] || $collectionDiffs['removed']) {
                    $data[$field->getField()]['collectionDiffs'] = $field->getCollectionDiffs();
                }
            }

            if ($field->getTranslationDomain()) {
                $data[$field->getField()]['translationDomain'] = $field->getTranslationDomain();
            }
        }

        return $data;
    }

    public function getCollectionData(Collection $collection): array
    {
        return $this->getData($collection->toArray());
    }
}
