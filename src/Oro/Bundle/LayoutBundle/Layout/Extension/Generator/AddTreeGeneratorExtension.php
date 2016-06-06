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

    const ACTION_ADD_TREE_KEY = '@addTree';
    const ACTION_ADD_KEY = '@add';

    const PATH_KEY = '__path';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $collection)
    {
        $source = $data->getSource();
        if (is_array($source) && isset($source[self::NODE_ACTIONS])) {
            // traversing through actions, looking for "@addTree" action
            $transformedActions = new \ArrayObject();
            foreach ($source[self::NODE_ACTIONS] as $nodeNo => $actionDefinition) {
                // do not validate syntax, error will be thrown afterwards
                $actionName = is_array($actionDefinition) ? key($actionDefinition) : '';

                if (self::ACTION_ADD_TREE_KEY !== $actionName) {
                    $transformedActions->append($actionDefinition);
                    continue;
                }
                $path = self::NODE_ACTIONS.'.'.$nodeNo;
                $actionNode = reset($actionDefinition);

                // looking for items, parent and tree it self
                if (!isset($actionNode[self::NODE_ITEMS], $actionNode[self::NODE_TREE])) {
                    throw new SyntaxException('expected array with keys "items" and "tree"', $actionDefinition, $path);
                }

                if (count($actionNode[self::NODE_TREE]) !== 1) {
                    throw new SyntaxException('tree expects only one child', $actionDefinition, $path);
                }

                $tree = reset($actionNode[self::NODE_TREE]);
                $treeParent = key($actionNode[self::NODE_TREE]);
                try {
                    $this->processTree($transformedActions, $tree, $treeParent, $actionNode[self::NODE_ITEMS], $path);
                } catch (\LogicException $e) {
                    throw new SyntaxException('invalid tree definition. '.$e->getMessage(), $actionDefinition, $path);
                }
            }
            $source[self::NODE_ACTIONS] = $transformedActions->getArrayCopy();

            $data->setSource($source);
        }
    }

    /**
     * Walk recursively through the tree, completing block definition in tree by found correspondent data "items" list
     *
     * @param \ArrayObject $actions
     * @param mixed $currentSubTree
     * @param string $parentId
     * @param array $items
     * @param string $path
     */
    protected function processTree(\ArrayObject $actions, $currentSubTree, $parentId, array $items, $path)
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

            $actions->append([
                self::ACTION_ADD_KEY => $itemDefinition,
                self::PATH_KEY => $path,
            ]);

            $this->processTree($actions, $subtree, $blockId, $items, $path);
        }
    }
}
