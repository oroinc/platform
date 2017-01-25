<?php

namespace Oro\Bundle\UIBundle\Utils;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\UIBundle\Model\TreeItem;

class TreeUtils
{
    /** @var PropertyAccessor */
    private static $propertyAccessor;

    /**
     * @param \ArrayAccess $externalTree
     * @param string       $keyName
     * @param string       $labelName
     *
     * @return TreeItem[]
     */
    public static function buildFlatTreeItemList(\ArrayAccess $externalTree, $keyName, $labelName)
    {
        return self::buildFlatTreeItemListRecursive($externalTree, $keyName, $labelName);
    }

    /**
     * @param mixed         $nodes
     * @param string        $keyName
     * @param string        $labelName
     * @param TreeItem|null $parent
     *
     * @return array
     */
    private static function buildFlatTreeItemListRecursive($nodes, $keyName, $labelName, TreeItem $parent = null)
    {
        $items = [];
        foreach ($nodes as $node) {
            $key = self::getPropertyAccessor()->getValue($node, $keyName);
            $label = self::getPropertyAccessor()->getValue($node, $labelName);

            $item = new TreeItem($key, $label);
            if ($parent) {
                $item->setParent($parent);
            }

            $items[$key] = $item;

            $items = array_merge($items, self::buildFlatTreeItemListRecursive($node, $keyName, $labelName, $item));
        }

        return $items;
    }

    /**
     * @return PropertyAccessor
     */
    private static function getPropertyAccessor()
    {
        if (!self::$propertyAccessor) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return self::$propertyAccessor;
    }
}
