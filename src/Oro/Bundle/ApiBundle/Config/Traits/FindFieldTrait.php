<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\FieldConfigInterface;

/**
 * @property FieldConfigInterface[] $fields
 */
trait FindFieldTrait
{
    /**
     * Finds a field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param string $fieldName
     * @param bool   $findByPropertyPath
     *
     * @return FieldConfigInterface|null
     */
    protected function doFindField($fieldName, $findByPropertyPath = false)
    {
        if (isset($this->fields[$fieldName])) {
            $field = $this->fields[$fieldName];
            if (!$findByPropertyPath) {
                return $field;
            }
            $propertyPath = $field->getPropertyPath();
            if (!$propertyPath || $propertyPath === $fieldName) {
                return $field;
            }
        }
        if ($findByPropertyPath) {
            foreach ($this->fields as $field) {
                $propertyPath = $field->getPropertyPath();
                if ($propertyPath && $propertyPath === $fieldName) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Finds the name of a field by its property path.
     *
     * @param string $propertyPath
     *
     * @return string|null
     */
    protected function doFindFieldNameByPropertyPath($propertyPath)
    {
        if (isset($this->fields[$propertyPath])) {
            $field = $this->fields[$propertyPath];
            $path = $field->getPropertyPath();
            if (!$path || $path === $propertyPath) {
                return $propertyPath;
            }
        }
        foreach ($this->fields as $fieldName => $field) {
            $path = $field->getPropertyPath();
            if ($propertyPath && $path === $propertyPath) {
                return $fieldName;
            }
        }

        return null;
    }
}
