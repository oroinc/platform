<?php

namespace Oro\Component\Layout;

/**
 * Base implementation of LayoutBuilderInterface
 * This is straightforward implementation with strict checking of all operations' arguments.
 * It means that:
 *  - several layout items with the same id cannot be added
 *  - only existing layout items can be removed
 *  - an alias for the layout item must be added before you can use it
 */
class LayoutBuilder implements LayoutBuilderInterface
{
    /** @var LayoutData */
    protected $layoutData;

    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolverInterface */
    protected $blockOptionsResolver;

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
            $this->layoutData->addItem($id, $parentId, $blockType, $options);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" item to the layout. ParentItemId: %s. BlockType: %s. Error: %s',
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
            $this->layoutData->removeItem($id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" item from the layout. Error: %s',
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
            $this->layoutData->addItemAlias($alias, $id);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot add "%s" alias for "%s" item. Error: %s',
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
            $this->layoutData->removeItemAlias($alias);
        } catch (\Exception $e) {
            throw new Exception\LogicException(
                sprintf(
                    'Cannot remove "%s" alias. Error: %s',
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
    public function getLayout($rootId = null)
    {
        $rootId = $rootId
            ? $this->layoutData->resolveItemId($rootId)
            : $this->layoutData->getRootItemId();

        $this->buildBlocks($rootId);
        $view = $this->buildBlockViews($rootId);

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
}
