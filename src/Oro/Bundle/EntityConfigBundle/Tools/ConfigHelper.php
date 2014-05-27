<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Doctrine\Common\Util\Inflector;

class ConfigHelper
{
    private static $configModelClasses = [
        'Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel'   => true,
        'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel'     => true,
        'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel'      => true,
        'Oro\Bundle\EntityConfigBundle\Entity\ConfigModelIndexValue' => true,
    ];

    /**
     * Checks whether the given class is one of entities used to store entity configs or not
     *
     * @param string $className
     * @return bool
     */
    public static function isConfigModelEntity($className)
    {
        return isset(self::$configModelClasses[$className]);
    }

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
     * @param string $scope
     * @param string $propertyName property key: label, description, plural_label, etc.
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getTranslationKey($scope, $propertyName, $className, $fieldName = null)
    {
        if (empty($scope)) {
            throw new \InvalidArgumentException('$scope must not be empty');
        }
        if (empty($propertyName)) {
            throw new \InvalidArgumentException('$propertyName must not be empty');
        }
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }

        //example: className - OroCRM\Bundle\ContactBundle\Entity\ContactAddress
        $class = str_replace(['Bundle\\Entity', 'Bundle\\'], '', $className);

        //example: className - OroCRM\Contact\ContactAddress
        $classArray = explode('\\', strtolower($class));
        $classArray = array_filter($classArray);

        $keyArray = [];
        foreach ($classArray as $item) {
            $item = Inflector::camelize($item);
            if (!in_array($item, $keyArray)) {
                $keyArray[] = $item;
            }
        }

        if ($fieldName) {
            $keyArray[] = Inflector::tableize($fieldName);
        }

        $propertyName = Inflector::tableize($propertyName);
        if ($scope !== 'entity') {
            $propertyName = Inflector::tableize($scope) . '_' . $propertyName;
        }
        if ($fieldName) {
            $keyArray[] = $propertyName;
        } else {
            $keyArray[] = 'entity_' . $propertyName;
        }

        return implode('.', $keyArray);
    }

    /**
     * Extracts module and entity names from the given full class name
     *
     * @param string $className
     * @return array [$moduleName, $entityName]
     */
    public static function getModuleAndEntityNames($className)
    {
        if (empty($className)) {
            return ['', ''];
        }

        $parts      = explode('\\', $className);
        $entityName = $parts[count($parts) - 1];

        $moduleName = null;
        $isBundle   = false;
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $part = $parts[$i];
            if (!in_array($part, array('Bundle', 'Bundles', 'Entity', 'Entities'))) {
                if (substr($part, -6) === 'Bundle') {
                    $isBundle = true;
                }
                $moduleName .= $part;
            } elseif (count($parts) >= 4) {
                $isBundle = true;
            }
        }

        if (!$moduleName || !$isBundle) {
            $moduleName = 'System';
        }

        return [$moduleName, $entityName];
    }
}
