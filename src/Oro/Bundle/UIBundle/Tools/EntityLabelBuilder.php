<?php

namespace Oro\Bundle\UIBundle\Tools;

use Doctrine\Common\Util\Inflector;

class EntityLabelBuilder
{
    /**
     * Returns the translation key for entity label
     *
     * @param string $className
     *
     * @return string
     */
    public static function getEntityLabelTranslationKey($className)
    {
        return self::getTranslationKey('label', $className);
    }

    /**
     * Returns the translation key for entity plural label
     *
     * @param string $className
     *
     * @return string
     */
    public static function getEntityPluralLabelTranslationKey($className)
    {
        return self::getTranslationKey('plural_label', $className);
    }

    /**
     * Returns the translation key for field label
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return string
     */
    public static function getFieldLabelTranslationKey($className, $fieldName)
    {
        return self::getTranslationKey('label', $className, $fieldName);
    }


    /**
     * Returns the translation key for the given entity property
     *
     * The result format for entity: [vendor].[bundle].[entity].entity_[property]
     * Examples:
     *  label for Acme\Bundle\TestBundle\Entity\Product          -> acme.test.product.entity_label
     *  description for Acme\Bundle\ProductBundle\Entity\Product -> acme.product.entity_label
     *
     * The result format for field: [vendor].[bundle].[entity].[field].[property]
     * Examples:
     *  label for Acme\Bundle\TestBundle\Entity\Product::sellPrice    -> acme.test.product.sell_price.label
     *  label for Acme\Bundle\ProductBundle\Entity\Product::sellPrice -> acme.product.sell_price.label
     *
     * @param string      $propertyName
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return string
     */
    public static function getTranslationKey($propertyName, $className, $fieldName = null)
    {
        $parts = self::explodeClassName($className);

        if ($fieldName) {
            $parts[] = Inflector::tableize($fieldName);
            $parts[] = $propertyName;
        } else {
            $parts[] = 'entity_' . $propertyName;
        }

        return implode('.', $parts);
    }

    /**
     * Returns significant for building the translation key parts of the class name
     *
     * @param $className
     *
     * @return array
     */
    public static function explodeClassName($className)
    {
        // remove insignificant info from the class name. Examples:
        //  Acme\Bundle\TestBundle\Entity\Product   -> Acme\Test\Product
        //  Acme\Bundle\TestBundle\Document\Product -> Acme\Test\Product
        //  Acme\Bundle\TestBundle\Model\Product    -> Acme\Test\Product
        //  Acme\Bundle\TestBundle\Other\Product    -> Acme\Test\Other\Product
        //  Acme\Bundle\TestBundle\Product          -> Acme\Test\Product
        $className = str_replace(
            ['bundle\\entity\\', 'bundle\\document\\', 'bundle\\model\\', 'bundle\\'],
            '\\',
            strtolower($className)
        );

        // split the class name to parts
        $items = array_filter(explode('\\', str_replace("_", "", $className)));

        // remove duplicates
        $result = [];
        foreach ($items as $item) {
            if (!in_array($item, $result)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
