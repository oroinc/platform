<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\Inflector;

class ConfigHelper
{
    /**
     * Returns translation key (placeholder) by entity class name and field name
     * example: orocrm.contact.first_name
     *
     * @param $className
     * @param $fieldName
     * @return string
     */
    public static function getTranslationKey($className, $fieldName)
    {
        $class      = str_replace(['Bundle\\Entity', 'Bundle\\'], '', $className);
        $classArray = explode('\\', strtolower($class));

        $keyArray   = [];
        foreach ($classArray as $item) {
            if (!in_array(Inflector::camelize($item), $keyArray)) {
                $keyArray[] = Inflector::camelize($item);
            }
        }
        $keyArray[] = Inflector::tableize($fieldName);

        return implode('.', $keyArray);
    }
}