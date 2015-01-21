<?php

namespace Oro\Component\Layout;

class LayoutBuilder implements LayoutBuilderInterface
{
    const RESOLVED_OPTIONS_PROPERTY = 'resolved_options';

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
        $this->layoutData->addItem($id, $parentId, $blockType, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->layoutData->removeItem($id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->layoutData->addItemAlias($alias, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->layoutData->removeItemAlias($alias);

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

        $rootItem = $this->layoutData->getItem($rootId);
        $rootPath = $rootItem['path'];

        // build blocks if they are not built yet
        if (!$this->layoutData->hasItemProperty($rootId, self::RESOLVED_OPTIONS_PROPERTY)) {
            $this->buildBlock($rootId, $rootItem);
            $iterator = $this->layoutData->getHierarchyIterator($rootPath);
            foreach ($iterator as $id) {
                $item = $this->layoutData->getItem($id);
                if (!$this->layoutData->hasItemProperty($id, self::RESOLVED_OPTIONS_PROPERTY)) {
                    $this->buildBlock($id, $item);
                }
            }
        }

        $rootView = $this->createBlockView($rootId, $rootItem, $this->layoutData->getHierarchy($rootPath));

        return new Layout($rootView);
    }

    /**
     * @param string $id
     * @param array  $item
     */
    protected function buildBlock($id, array $item)
    {
        $types = $this->getBlockTypeHierarchy($item['block_type']);

        // resolve options
        $resolvedOptions = $this->blockOptionsResolver->resolve($item['block_type'], $item['options']);
        $this->layoutData->setItemProperty($id, self::RESOLVED_OPTIONS_PROPERTY, $resolvedOptions);

        // build block
        $blockBuilder = new LayoutBlockBuilder($this->layoutData, $id);
        foreach ($types as $type) {
            $type->buildBlock($blockBuilder, $resolvedOptions);
        }
    }

    /**
     * @param string    $id
     * @param array     $item
     * @param array     $hierarchy
     * @param BlockView $parentView
     *
     * @return BlockView
     */
    protected function createBlockView($id, array $item, array $hierarchy, BlockView $parentView = null)
    {
        $view            = new BlockView($parentView);
        $types           = $this->getBlockTypeHierarchy($item['block_type']);
        $resolvedOptions = $this->layoutData->getItemProperty($id, self::RESOLVED_OPTIONS_PROPERTY);
        $block           = new LayoutBlock($this->layoutData, $id);

        foreach ($types as $type) {
            $type->buildView($view, $block, $resolvedOptions);
        }

        foreach ($hierarchy as $childId => $children) {
            $view->children[] = $this->createBlockView(
                $childId,
                $this->layoutData->getItem($childId),
                $children,
                $view
            );
        }

        foreach ($types as $type) {
            $type->finishView($view, $block, $resolvedOptions);
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
