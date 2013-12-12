<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\Inflector;

class ConfigHelper
{
    /**
     * Returns translation key (placeholder) by entity class name and field name
     * example: orocrm.contact.first_name
     *
     * @param string $className
     * @param string $fieldName
     * @param string $propertyName  property key: label, description, plural_label, etc.
     *
     * @return string
     */
    public static function getTranslationKey($className, $fieldName = null, $propertyName = null)
    {
        $transKey = '';
        if ($className) {
            //example: className - OroCRM\Bundle\ContactBundle\Entity\ContactAddress
            $class      = str_replace(['Bundle\\Entity', 'Bundle\\'], '', $className);

            //example: className - OroCRM\Contact\ContactAddress
            $classArray = explode('\\', strtolower($class));

            /**
             * if entity name starts with bundle name -> remove bundle name from entity name
             * example:
             *  was  classArray - [OroCRM, Contact, ContactAddress]
             *  will classArray - [OroCRM, Contact, Address]
             */
            if (strpos($classArray[2], $classArray[1]) === 0) {
                $classArray[2] = str_replace($classArray[1], '', $classArray[2]);
            }
            $classArray = array_filter($classArray);

            $keyArray = [];
            foreach ($classArray as $item) {
                if (!in_array(Inflector::camelize($item), $keyArray)) {
                    $keyArray[] = Inflector::camelize($item);
                }
            }

            if (!is_null($fieldName)) {
                $keyArray[] = Inflector::tableize($fieldName);
            }

            if (!is_null($propertyName)) {
                $keyArray[] = Inflector::tableize($propertyName);
            }

            $transKey = implode('.', $keyArray);
        }

        return $transKey;
    }
}
