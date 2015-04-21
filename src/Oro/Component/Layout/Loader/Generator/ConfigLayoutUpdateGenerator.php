<?php

namespace Oro\Component\Layout\Loader\Generator;

use Oro\Component\Layout\Exception\SyntaxException;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Util\ArrayUtils;
use Oro\Component\Layout\Util\ReflectionUtils;

class ConfigLayoutUpdateGenerator extends AbstractLayoutUpdateGenerator
{
    const NODE_ACTIONS   = 'actions';
    const NODE_ITEMS     = 'items';
    const NODE_TREE      = 'tree';

    const ACTION_ADD_TREE = '@addTree';
    const ACTION_ADD      = '@add';

    const PATH_ATTR = '__path';

    /** @var ConfigLayoutUpdateGeneratorExtensionInterface[] */
    protected $extensions = [];

    /** @var ReflectionUtils */
    protected $helper;

    /**
     * @param ConfigLayoutUpdateGeneratorExtensionInterface $extension
     */
    public function addExtension(ConfigLayoutUpdateGeneratorExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGenerateBody(GeneratorData $data)
    {
        $body   = [];
        $source = $data->getSource();

        foreach ($source[self::NODE_ACTIONS] as $actionDefinition) {
            $actionName = key($actionDefinition);
            $arguments  = isset($actionDefinition[$actionName]) && is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [];

            $call = [];
            $this->normalizeActionName($actionName);
            $this->getHelper()->completeArguments($actionName, $arguments);

            array_walk(
                $arguments,
                function (&$arg) {
                    $arg = var_export($arg, true);
                }
            );
            $call[] = sprintf('$%s->%s(', self::PARAM_LAYOUT_MANIPULATOR, $actionName);
            $call[] = implode(', ', $arguments);
            $call[] = ');';

            $body[] = implode(' ', $call);
        }

        return implode("\n", $body);
    }

    /**
     * Validates given resource data, checks that "actions" node exists and consist valid actions.
     *
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function validate(GeneratorData $data)
    {
        $source = $data->getSource();

        if (!(is_array($source) && isset($source[self::NODE_ACTIONS]) && is_array($source[self::NODE_ACTIONS]))) {
            throw new SyntaxException(sprintf('expected array with "%s" node', self::NODE_ACTIONS), $source);
        }

        $actions = $source[self::NODE_ACTIONS];
        foreach ($actions as $nodeNo => $actionDefinition) {
            if (isset($actionDefinition[self::PATH_ATTR])) {
                $path = $actionDefinition[self::PATH_ATTR];
                unset ($actionDefinition[self::PATH_ATTR]);
            } else {
                $path = self::NODE_ACTIONS . '.' . $nodeNo;
            }

            if (!is_array($actionDefinition)) {
                throw new SyntaxException('expected array with action name as key', $actionDefinition, $path);
            }

            $actionName = key($actionDefinition);
            $arguments  = is_array($actionDefinition[$actionName])
                ? $actionDefinition[$actionName] : [$actionDefinition[$actionName]];

            if (strpos($actionName, '@') !== 0) {
                throw new SyntaxException(
                    sprintf('action name should start with "@" symbol, current name "%s"', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            $this->normalizeActionName($actionName);

            if (!$this->getHelper()->hasMethod($actionName)) {
                throw new SyntaxException(
                    sprintf('unknown action "%s", should be one of LayoutManipulatorInterface\'s methods', $actionName),
                    $actionDefinition,
                    $path
                );
            }

            if (!$this->getHelper()->isValidArguments($actionName, $arguments)) {
                throw new SyntaxException($this->getHelper()->getLastError(), $actionDefinition, $path);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        $source = $data->getSource();
        if (is_array($source)) {
            if (isset($source[self::NODE_ACTIONS])) {
                // traversing through actions, looking for "@addTree" action
                $actions = $source[self::NODE_ACTIONS];
                foreach ($actions as $nodeNo => $actionDefinition) {
                    // do not validate syntax, error will be thrown afterwards
                    $actionName = is_array($actionDefinition) ? key($actionDefinition) : '';

                    if (self::ACTION_ADD_TREE === $actionName) {
                        $path       = self::NODE_ACTIONS . '.' . $nodeNo;
                        $actionNode = reset($actionDefinition);

                        // looking for items, parent and tree it self
                        if (!(isset($actionNode[self::NODE_ITEMS], $actionNode[self::NODE_TREE]))) {
                            throw new SyntaxException(
                                'expected array with keys "items" and "tree"',
                                $actionDefinition,
                                $path
                            );
                        }

                        $transformedActions = [];
                        $treeParent         = key($actionNode[self::NODE_TREE]);
                        $tree               = current($actionNode[self::NODE_TREE]);
                        try {
                            $this->processTree($transformedActions, $tree, $treeParent, $actionNode[self::NODE_ITEMS]);
                        } catch (\LogicException $e) {
                            throw new SyntaxException(
                                'invalid tree definition. ' . $e->getMessage(),
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
                        $source[self::NODE_ACTIONS] = array_merge($source[self::NODE_ACTIONS], $transformedActions);

                        // unset processed "@addTree" action
                        unset($source[self::NODE_ACTIONS][$nodeNo]);
                    }
                }
            }

            // apply extensions
            foreach ($this->extensions as $extension) {
                $extension->prepare($source, $visitorCollection);
            }
        }

        $data->setSource($source);
    }

    /**
     * Walk recursively through the tree, completing block definition in tree by found correspondent data "items" list
     *
     * @param array  $actions
     * @param mixed  $currentSubTree
     * @param string $parentId
     * @param array  $items
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

            if (ArrayUtils::isAssoc($itemDefinition)) {
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

    /**
     * @return ReflectionUtils
     */
    protected function getHelper()
    {
        if (null === $this->helper) {
            $this->helper = new ReflectionUtils('Oro\Component\Layout\LayoutManipulatorInterface');
        }

        return $this->helper;
    }

    /**
     * Removes "@" sign from beginning of action name
     *
     * @param string $actionName
     */
    protected function normalizeActionName(&$actionName)
    {
        $actionName = substr($actionName, 1);
    }
}
