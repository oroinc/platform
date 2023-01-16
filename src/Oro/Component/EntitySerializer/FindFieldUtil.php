<?php

namespace Oro\Component\EntitySerializer;

/**
 * A set of reusable static methods to find a field in a configuration section.
 */
class FindFieldUtil
{
    /**
     * Finds a field by its name or property path.
     * If $findByPropertyPath equals to TRUE do the find using a given field name as a property path.
     */
    public static function doFindField(
        array $fields,
        string $fieldName,
        bool $findByPropertyPath = false
    ): ?FieldConfigInterface {
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
            $foundFieldName = self::findFieldNameByPropertyPath($fields, $fieldName);
            if ($foundFieldName) {
                return $fields[$foundFieldName];
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
    public static function doFindFieldNameByPropertyPath(array $fields, string $propertyPath): ?string
    {
        if (isset($fields[$propertyPath])) {
            $fieldPropertyPath = $fields[$propertyPath]->getPropertyPath();
            if (!$fieldPropertyPath || $fieldPropertyPath === $propertyPath) {
                return $propertyPath;
            }
        }

        return self::findFieldNameByPropertyPath($fields, $propertyPath);
    }

    /**
     * @param FieldConfigInterface[] $fields
     * @param string                 $propertyPath
     *
     * @return string|null
     */
    private static function findFieldNameByPropertyPath(array $fields, string $propertyPath): ?string
    {
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
