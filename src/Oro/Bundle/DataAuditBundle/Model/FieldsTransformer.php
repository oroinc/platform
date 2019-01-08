<?php

namespace Oro\Bundle\DataAuditBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;

/**
 * Transform AuditFields to a scalar data
 */
class FieldsTransformer
{
    /**
     * @param AbstractAuditField[] $fields
     * @return array
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
            if (in_array($field->getDataType(), ['date', 'datetime', 'array', 'jsonarray'], true)) {
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

            if ($field->getTranslationDomain()) {
                $data[$field->getField()]['translationDomain'] = $field->getTranslationDomain();
            }
        }

        return $data;
    }

    /**
     * @param Collection $collection
     * @return array
     */
    public function getCollectionData(Collection $collection): array
    {
        return $this->getData($collection->toArray());
    }
}
