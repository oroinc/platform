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
class LayoutBuilder implements RawLayoutModifierInterface
{
    /** @var LayoutData */
    protected $layoutData;

    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolverInterface */
    protected $blockOptionsResolver;

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
            $this->layoutData->addItem($id, $parentId, $blockType, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to the layout. ParentItemId: %s. BlockType: %s. Reason: %s',
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
            $this->layoutData->removeItem($id);
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
            $this->layoutData->addItemAlias($alias, $id);
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
            $this->layoutData->removeItemAlias($alias);
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
            $options              = $this->layoutData->getItemProperty($id, LayoutData::OPTIONS);
            $options[$optionName] = $optionValue;
            $this->layoutData->setItemProperty($id, LayoutData::OPTIONS, $options);
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
            $options = $this->layoutData->getItemProperty($id, LayoutData::OPTIONS);
            unset($options[$optionName]);
            $this->layoutData->setItemProperty($id, LayoutData::OPTIONS, $options);
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
            ? $this->layoutData->resolveItemId($rootId)
            : $this->layoutData->getRootItemId();

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
        return $this->layoutData->hasItem($id);
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
        return $this->layoutData->hasItemAlias($alias);
    }

    /**
     * @param string $rootId
     */
    protected function buildBlocks($rootId)
    {
        // build blocks if they are not built yet
        if (!$this->layoutData->hasItemProperty($rootId, LayoutData::RESOLVED_OPTIONS)) {
            $this->buildBlock(
                $rootId,
                $this->layoutData->getItemProperty($rootId, LayoutData::BLOCK_TYPE),
                $this->layoutData->getItemProperty($rootId, LayoutData::OPTIONS)
            );
            $iterator = $this->layoutData->getHierarchyIterator($rootId);
            foreach ($iterator as $id) {
                if (!$this->layoutData->hasItemProperty($id, LayoutData::RESOLVED_OPTIONS)) {
                    $this->buildBlock(
                        $id,
                        $this->layoutData->getItemProperty($id, LayoutData::BLOCK_TYPE),
                        $this->layoutData->getItemProperty($id, LayoutData::OPTIONS)
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
            $this->layoutData->getItemProperty($rootId, LayoutData::BLOCK_TYPE),
            $this->layoutData->getItemProperty($rootId, LayoutData::RESOLVED_OPTIONS),
            $this->layoutData->getHierarchy($rootId)
        );
    }

    /**
     * @param string $id
     * @param array  $blockType
     * @param array  $options
     */
    protected function buildBlock($id, $blockType, array $options)
    {
        $types = $this->getBlockTypeHierarchy($blockType);

        // resolve options
        $resolvedOptions = $this->blockOptionsResolver->resolve($blockType, $options);
        $this->layoutData->setItemProperty($id, LayoutData::RESOLVED_OPTIONS, $resolvedOptions);

        // build block
        $blockBuilder = new LayoutBlockBuilder($this->layoutData, $id);
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
                $this->layoutData->getItemProperty($childId, LayoutData::BLOCK_TYPE),
                $this->layoutData->getItemProperty($childId, LayoutData::RESOLVED_OPTIONS),
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
     * @param string $blockType
     *
     * @return BlockTypeInterface[]
     */
    protected function getBlockTypeHierarchy($blockType)
    {
        $result = [];

        while ($blockType) {
            $type = $this->blockTypeRegistry->getBlockType($blockType);
            array_unshift($result, $type);
            $blockType = $type->getParent();
        }

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
