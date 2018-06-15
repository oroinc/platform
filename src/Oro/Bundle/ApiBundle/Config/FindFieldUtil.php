<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A set of reusable static methods to find a field in a configuration section.
 */
class FindFieldUtil
{
    /**
     * Finds a field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     *
     * @param FieldConfigInterface[] $fields
     * @param string                 $fieldName
     * @param bool                   $findByPropertyPath
     *
     * @return FieldConfigInterface|null
     */
    public static function doFindField(array $fields, $fieldName, $findByPropertyPath = false)
    {
        if (isset($fields[$fieldName])) {
            $field = $fields[$fieldName];
            if (!$findByPropertyPath) {
                return $field;
            }
            $fieldPropertyPath = $field->getPropertyPath();
            if (!$fieldPropertyPath
                || $fieldPropertyPath === $fieldName
                || ConfigUtil::IGNORE_PROPERTY_PATH === $fieldName
            ) {
                return $field;
            }
        }
        if ($findByPropertyPath) {
            foreach ($fields as $field) {
                $fieldPropertyPath = $field->getPropertyPath();
                if ($fieldPropertyPath
                    && $fieldPropertyPath === $fieldName
                    && ConfigUtil::IGNORE_PROPERTY_PATH !== $fieldPropertyPath
                ) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Finds the name of a field by its property path.
     *
     * @param FieldConfigInterface[] $fields
     * @param string                 $propertyPath
     *
     * @return string|null
     */
    public static function doFindFieldNameByPropertyPath(array $fields, $propertyPath)
    {
        if (isset($fields[$propertyPath])) {
            $fieldPropertyPath = $fields[$propertyPath]->getPropertyPath();
            if (!$fieldPropertyPath
                || $fieldPropertyPath === $propertyPath
                || ConfigUtil::IGNORE_PROPERTY_PATH === $fieldPropertyPath
            ) {
                return $propertyPath;
            }
        }
        foreach ($fields as $fieldName => $field) {
            $fieldPropertyPath = $field->getPropertyPath();
            if ($fieldPropertyPath
                && $fieldPropertyPath === $propertyPath
                && ConfigUtil::IGNORE_PROPERTY_PATH !== $fieldPropertyPath
            ) {
                return $fieldName;
            }
        }

        return null;
    }
}
