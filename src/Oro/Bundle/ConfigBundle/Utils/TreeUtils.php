<?php

namespace Oro\Bundle\ConfigBundle\Utils;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;

/**
 * Provides a set of static method to fetch config tree nodes.
 */
class TreeUtils
{
    /**
     * Finds node by name in tree.
     */
    public static function findNodeByName(GroupNodeDefinition $node, string $nodeName): ?GroupNodeDefinition
    {
        $resultNode = null;
        /** @var GroupNodeDefinition $childNode */
        foreach ($node as $childNode) {
            if ($childNode->getName() === $nodeName) {
                return $childNode;
            }
            if ($childNode instanceof GroupNodeDefinition && !$childNode->isEmpty()) {
                $resultNode = self::findNodeByName($childNode, $nodeName);
                if ($resultNode) {
                    return $resultNode;
                }
            }
        }

        return $resultNode;
    }

    /**
     * Picks nodes for needed level.
     */
    public static function getByNestingLevel(GroupNodeDefinition $node, int $neededLevel): ?GroupNodeDefinition
    {
        /** @var GroupNodeDefinition $childNode */
        foreach ($node as $childNode) {
            if ($neededLevel === $childNode->getLevel()) {
                return $childNode;
            }
            $node = self::getByNestingLevel($childNode, $neededLevel);
            if ($node !== null) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the name of the first child node.
     */
    public static function getFirstNodeName(GroupNodeDefinition $node): ?string
    {
        if ($node->isEmpty()) {
            return null;
        }

        return $node->first()->getName();
    }

    /**
     * Gets a config key which consists of the root node name and config key concatenated with the separator.
     */
    public static function getConfigKey(
        string $rootNodeName,
        string $configKey,
        string $separator = ConfigManager::SECTION_MODEL_SEPARATOR
    ): string {
        return $rootNodeName . $separator . $configKey;
    }
}
