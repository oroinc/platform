<?php

namespace Oro\Component\Layout;

/**
 * Base implementation of LayoutBuilderInterface
 * This is straightforward implementation with strict checking of all operations' arguments.
 * It means that:
 *  - several layout items with the same id cannot be added
 *  - only existing layout items can be removed
 *  - an alias must be added before you can use it
 *  - an alias can be added for existing item only
 *  - only existing alias can be removed
 */
class LayoutBuilder implements RawLayoutManipulatorInterface
{
    /** @var LayoutData */
    protected $layoutData;

    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolverInterface */
    protected $blockOptionsResolver;

    /** @var  array */
    protected $blockTypeHierarchy = [];

    /** @var bool */
    protected $frozen = false;

    /** @var bool */
    protected $optionsFrozen = false;

    /**
     * @param BlockTypeRegistryInterface    $blockTypeRegistry
     * @param BlockOptionsResolverInterface $blockOptionsResolver
     */
    public function __construct(
        BlockTypeRegistryInterface $blockTypeRegistry,
        BlockOptionsResolverInterface $blockOptionsResolver
    ) {
        $this->blockTypeRegistry    = $blockTypeRegistry;
        $this->blockOptionsResolver = $blockOptionsResolver;

        $this->layoutData = new LayoutData();
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        try {
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
            $this->layoutData->add($id, $parentId, $blockType, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to the layout. ParentId: %s. BlockType: %s. Reason: %s',
                    $id,
                    $parentId,
                    $blockType,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        try {
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
            $this->layoutData->remove($id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" item from the layout. Reason: %s',
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        try {
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
            $this->layoutData->addAlias($alias, $id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" alias for "%s" item. Reason: %s',
                    $alias,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        try {
            if ($this->frozen) {
                throw new Exception\LogicException('Cannot modify frozen layout.');
            }
            $this->layoutData->removeAlias($alias);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" alias. Reason: %s',
                    $alias,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        try {
            if ($this->optionsFrozen) {
                throw new Exception\LogicException('Cannot change frozen options.');
            }
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            $options              = $this->layoutData->getProperty($id, LayoutData::OPTIONS);
            $options[$optionName] = $optionValue;
            $this->layoutData->setProperty($id, LayoutData::OPTIONS, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot set a value for "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        try {
            if ($this->optionsFrozen) {
                throw new Exception\LogicException('Cannot change frozen options.');
            }
            if (empty($optionName)) {
                throw new Exception\InvalidArgumentException('The option name must not be empty.');
            }
            $options = $this->layoutData->getProperty($id, LayoutData::OPTIONS);
            unset($options[$optionName]);
            $this->layoutData->setProperty($id, LayoutData::OPTIONS, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" option for "%s" item. Reason: %s',
                    $optionName,
                    $id,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout($rootId = null)
    {
        $rootId = $rootId
            ? $this->layoutData->resolveId($rootId)
            : $this->layoutData->getRootId();

        $this->optionsFrozen = true;

        $this->buildBlocks($rootId);
        $view = $this->buildBlockViews($rootId);

        $this->frozen = true;

        return new Layout($view);
    }

    /**
     * Checks whether the item with the given id exists in the layout
     *
     * @param string $id The item id
     *
     * @return bool
     */
    public function has($id)
    {
        return $this->layoutData->has($id);
    }

    /**
     * Checks whether the given item alias exists
     *
     * @param string $alias The item alias
     *
     * @return bool
     */
    public function hasAlias($alias)
    {
        return $this->layoutData->hasAlias($alias);
    }

    /**
     * @param string $rootId
     */
    protected function buildBlocks($rootId)
    {
        // build blocks if they are not built yet
        if (!$this->layoutData->hasProperty($rootId, LayoutData::RESOLVED_OPTIONS, true)) {
            $this->buildBlock(
                $rootId,
                $this->layoutData->getProperty($rootId, LayoutData::BLOCK_TYPE, true),
                $this->layoutData->getProperty($rootId, LayoutData::OPTIONS, true)
            );
            $iterator = $this->layoutData->getHierarchyIterator($rootId);
            foreach ($iterator as $id) {
                if (!$this->layoutData->hasProperty($id, LayoutData::RESOLVED_OPTIONS, true)) {
                    $depth    = $iterator->getDepth();
                    $parentId = $depth === 0
                        ? $rootId
                        : $iterator->getSubIterator($depth - 1)->current();
                    if (!$this->isContainerBlock($parentId)) {
                        throw new Exception\LogicException(
                            sprintf(
                                'The "%s" item cannot be added as a child to "%s" item (block type: %s) '
                                . 'because only container blocks can have children.',
                                $id,
                                $parentId,
                                $this->layoutData->getProperty($parentId, LayoutData::BLOCK_TYPE, true)
                            )
                        );
                    }
                    $this->buildBlock(
                        $id,
                        $this->layoutData->getProperty($id, LayoutData::BLOCK_TYPE, true),
                        $this->layoutData->getProperty($id, LayoutData::OPTIONS, true)
                    );
                }
            }
        }
    }

    /**
     * @param string $rootId
     *
     * @return BlockView
     */
    protected function buildBlockViews($rootId)
    {
        return $this->createBlockView(
            $rootId,
            $this->layoutData->getProperty($rootId, LayoutData::BLOCK_TYPE, true),
            $this->layoutData->getProperty($rootId, LayoutData::RESOLVED_OPTIONS, true),
            $this->layoutData->getHierarchy($rootId)
        );
    }

    /**
     * @param string $id
     * @param string $blockType
     * @param array  $options
     */
    protected function buildBlock($id, $blockType, array $options)
    {
        $types = $this->getBlockTypeHierarchy($blockType);

        // resolve options
        $resolvedOptions = $this->blockOptionsResolver->resolve($blockType, $options);
        $this->layoutData->setProperty($id, LayoutData::RESOLVED_OPTIONS, $resolvedOptions);

        // build block
        $blockBuilder = new LayoutBlockBuilder($this->layoutData, $id);
        // iterate from parent to current
        foreach ($types as $type) {
            $type->buildBlock($blockBuilder, $resolvedOptions);
        }
    }

    /**
     * @param string    $id
     * @param array     $blockType
     * @param array     $options
     * @param array     $hierarchy
     * @param BlockView $parentView
     *
     * @return BlockView
     */
    protected function createBlockView($id, $blockType, array $options, array $hierarchy, BlockView $parentView = null)
    {
        $view  = new BlockView($parentView);
        $types = $this->getBlockTypeHierarchy($blockType);

        // add core variables to the block view, like id and variables required for rendering engine
        $view->vars['id']                  = $id;
        $uniqueBlockPrefix                 = '_' . $id;
        $view->vars['unique_block_prefix'] = $uniqueBlockPrefix;
        $view->vars['block_prefixes']      = $this->getBlockPrefixes($types, $uniqueBlockPrefix);
        $view->vars['cache_key']           = sprintf('%s_%s', $uniqueBlockPrefix, $blockType);

        $block = new LayoutBlock($this->layoutData, $id);
        foreach ($types as $type) {
            $type->buildView($view, $block, $options);
        }

        foreach ($hierarchy as $childId => $children) {
            $view->children[] = $this->createBlockView(
                $childId,
                $this->layoutData->getProperty($childId, LayoutData::BLOCK_TYPE, true),
                $this->layoutData->getProperty($childId, LayoutData::RESOLVED_OPTIONS, true),
                $children,
                $view
            );
        }

        foreach ($types as $type) {
            $type->finishView($view, $block, $options);
        }

        return $view;
    }

    /**
     * Checks whether the given block is a container for other blocks
     *
     * @param string $id
     *
     * @return bool
     */
    protected function isContainerBlock($id)
    {
        $blockType = $this->layoutData->getProperty($id, LayoutData::BLOCK_TYPE, true);
        $types     = $this->getBlockTypeHierarchy($blockType);
        // iterate from current to parent
        /** @var BlockTypeInterface $type */
        $type = end($types);
        while ($type) {
            if ($type->getName() === 'container') {
                return true;
            }
            $type = prev($types);
        }

        return false;
    }

    /**
     * @param string $blockType
     *
     * @return BlockTypeInterface[]
     */
    protected function getBlockTypeHierarchy($blockType)
    {
        if (isset($this->blockTypeHierarchy[$blockType])) {
            return $this->blockTypeHierarchy[$blockType];
        }

        $result = [];
        while ($blockType) {
            $type = $this->blockTypeRegistry->getBlockType($blockType);
            array_unshift($result, $type);
            $blockType = $type->getParent();
        }
        $this->blockTypeHierarchy[$blockType] = $result;

        return $result;
    }

    /**
     * @param BlockTypeInterface[] $types
     * @param string               $uniqueBlockPrefix
     *
     * @return string[]
     */
    protected function getBlockPrefixes($types, $uniqueBlockPrefix)
    {
        $blockPrefixes   = array_map(
            function (BlockTypeInterface $type) {
                return $type->getName();
            },
            $types
        );
        $blockPrefixes[] = $uniqueBlockPrefix;

        return $blockPrefixes;
    }
}
