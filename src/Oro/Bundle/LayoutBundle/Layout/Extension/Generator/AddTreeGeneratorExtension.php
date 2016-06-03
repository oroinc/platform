<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\PhpUtils\ArrayUtil;

class AddTreeGeneratorExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_ACTIONS = 'actions';
    const NODE_ITEMS = 'items';
    const NODE_TREE = 'tree';

    const ACTION_ADD_TREE = '@addTree';
    const ACTION_ADD = '@add';

    const PATH_ATTR = '__path';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $collection)
    {
        $source = $data->getSource();
        if (is_array($source) && isset($source[self::NODE_ACTIONS])) {
            // traversing through actions, looking for "@addTree" action
            $actions = $source[self::NODE_ACTIONS];
            $newActions = [];
            foreach ($actions as $nodeNo => $actionDefinition) {
                // do not validate syntax, error will be thrown afterwards
                $actionName = is_array($actionDefinition) ? key($actionDefinition) : '';

                if (self::ACTION_ADD_TREE === $actionName) {
                    $path = self::NODE_ACTIONS.'.'.$nodeNo;
                    $actionNode = reset($actionDefinition);

                    // looking for items, parent and tree it self
                    if (!isset($actionNode[self::NODE_ITEMS], $actionNode[self::NODE_TREE])) {
                        throw new SyntaxException(
                            'expected array with keys "items" and "tree"',
                            $actionDefinition,
                            $path
                        );
                    }

                    $transformedActions = [];
                    $treeParent = key($actionNode[self::NODE_TREE]);
                    $tree = current($actionNode[self::NODE_TREE]);
                    try {
                        $this->processTree($transformedActions, $tree, $treeParent, $actionNode[self::NODE_ITEMS]);
                    } catch (\LogicException $e) {
                        throw new SyntaxException(
                            'invalid tree definition. '.$e->getMessage(),
                            $actionDefinition,
                            $path
                        );
                    }

                    // pre-generate "path" option in order to show correct path if validation error will occur
                    array_walk(
                        $transformedActions,
                        function (&$val) use ($path) {
                            $val[self::PATH_ATTR] = $path;
                        }
                    );
                    $newActions = array_merge($newActions, $transformedActions);
                    unset($source[self::NODE_ACTIONS][$nodeNo]);
                }
            }
            $source[self::NODE_ACTIONS] = array_merge($source[self::NODE_ACTIONS], $newActions);
            $data->setSource($source);
        }
    }

    /**
     * Walk recursively through the tree, completing block definition in tree by found correspondent data "items" list
     *
     * @param array $actions
     * @param mixed $currentSubTree
     * @param string $parentId
     * @param array $items
     */
    protected function processTree(array &$actions, $currentSubTree, $parentId, array $items)
    {
        if (!is_array($currentSubTree)) {
            return;
        }

        foreach ($currentSubTree as $k => $subtree) {
            $blockId = is_numeric($k) && is_string($subtree) ? $subtree : $k;

            if (!isset($items[$blockId])) {
                throw new \LogicException(sprintf('Item with id "%s" not found in items list', $blockId));
            }

            $itemDefinition = $items[$blockId];

            if (ArrayUtil::isAssoc($itemDefinition)) {
                // merge associative values to arguments
                $itemDefinition = array_merge($itemDefinition, ['id' => $blockId, 'parentId' => $parentId]);
            } else {
                // prepend blockId and parentId to arguments
                array_unshift($itemDefinition, $blockId, $parentId);
            }

            $actions[] = [self::ACTION_ADD => $itemDefinition];

            $this->processTree($actions, $subtree, $blockId, $items);
        }
    }
}
