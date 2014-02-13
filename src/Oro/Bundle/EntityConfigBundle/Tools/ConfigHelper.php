<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\Inflector;

class ConfigHelper
{
    /**
     * Returns translation key (placeholder) by entity class name, field name and property code
     * example:
     *      [vendor].[bundle].[entity].[field].[config property]
     *      oro.user.group.name.label
     *
     *      if [entity] == [bundle] -> skip it
     *      oro.user.first_name.label
     *
     *      if NO fieldName -> add prefix 'entity_'
     *      oro.user.entity_label
     *      oro.user.group.entity_label
     *
     * @param string $propertyName property key: label, description, plural_label, etc.
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getTranslationKey($propertyName, $className, $fieldName = null)
    {
        if (empty($propertyName)) {
            throw new \InvalidArgumentException('$propertyName must not be empty');
        }
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }

        //example: className - OroCRM\Bundle\ContactBundle\Entity\ContactAddress
        $class      = str_replace(['Bundle\\Entity', 'Bundle\\'], '', $className);

        //example: className - OroCRM\Contact\ContactAddress
        $classArray = explode('\\', strtolower($class));
        $classArray = array_filter($classArray);

        $keyArray = [];
        foreach ($classArray as $item) {
            if (!in_array(Inflector::camelize($item), $keyArray)) {
                $keyArray[] = Inflector::camelize($item);
            }
        }

        if ($fieldName) {
            $keyArray[] = Inflector::tableize($fieldName);
        }

        $propertyName = Inflector::tableize($propertyName);
        $keyArray[] = $fieldName ? $propertyName : 'entity_' . $propertyName;

        return implode('.', $keyArray);
    }
}
