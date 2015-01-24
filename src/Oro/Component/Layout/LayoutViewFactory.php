<?php

namespace Oro\Component\Layout;

class LayoutViewFactory implements LayoutViewFactoryInterface
{
    /** @var LayoutData */
    protected $layoutData;

    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolverInterface */
    protected $blockOptionsResolver;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var  array */
    protected $blockTypeHierarchy = [];

    /**
     * @param BlockTypeRegistryInterface         $blockTypeRegistry
     * @param BlockOptionsResolverInterface      $blockOptionsResolver
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     */
    public function __construct(
        BlockTypeRegistryInterface $blockTypeRegistry,
        BlockOptionsResolverInterface $blockOptionsResolver,
        DeferredLayoutManipulatorInterface $layoutManipulator
    ) {
        $this->blockTypeRegistry    = $blockTypeRegistry;
        $this->blockOptionsResolver = $blockOptionsResolver;
        $this->layoutManipulator    = $layoutManipulator;
    }

    /**
     * {@inheritdoc}
     */
    public function createView(LayoutData $layoutData, $rootId = null)
    {
        $this->layoutData = $layoutData;

        $rootId = $this->resolveRootId($rootId);
        $this->buildBlocks($rootId);
        $rootBlockType = $this->layoutData->getProperty($rootId, LayoutData::BLOCK_TYPE, true);
        $rootView      = $this->createBlockView($rootId, $rootBlockType);
        $this->buildBlockView(
            $rootId,
            $rootBlockType,
            $this->layoutData->getProperty($rootId, LayoutData::RESOLVED_OPTIONS, true),
            $this->layoutData->getHierarchy($rootId),
            $rootView
        );

        $this->layoutData = null;

        return $rootView;
    }

    /**
     * @param string|null $rootId
     *
     * @return string
     */
    protected function resolveRootId($rootId)
    {
        return $rootId
            ? $this->layoutData->resolveId($rootId)
            : $this->layoutData->getRootId();
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
            $this->layoutManipulator->applyChanges();
        }
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
        $blockBuilder = new LayoutBlockBuilder($id, $this->layoutManipulator);
        // iterate from parent to current
        foreach ($types as $type) {
            $type->buildBlock($blockBuilder, $resolvedOptions);
        }
    }

    /**
     * @param string         $id
     * @param string         $blockType
     * @param BlockView|null $parentView
     *
     * @return BlockView
     */
    protected function createBlockView($id, $blockType, BlockView $parentView = null)
    {
        $view = new BlockView($parentView);

        // add core variables to the block view, like id and variables required for rendering engine
        $types                             = $this->getBlockTypeHierarchy($blockType);
        $uniqueBlockPrefix                 = '_' . $id;
        $view->vars['id']                  = $id;
        $view->vars['unique_block_prefix'] = $uniqueBlockPrefix;
        $view->vars['block_prefixes']      = $this->getBlockPrefixes($types, $uniqueBlockPrefix);
        $view->vars['cache_key']           = sprintf('%s_%s', $uniqueBlockPrefix, $blockType);

        return $view;
    }

    /**
     * @param string    $id
     * @param array     $blockType
     * @param array     $options
     * @param array     $hierarchy
     * @param BlockView $view
     *
     * @return BlockView
     */
    protected function buildBlockView($id, $blockType, array $options, array $hierarchy, BlockView $view)
    {
        $types = $this->getBlockTypeHierarchy($blockType);

        $block = new LayoutBlock($this->layoutData, $id);
        foreach ($types as $type) {
            $type->buildView($view, $block, $options);
        }

        foreach ($hierarchy as $childId => $children) {
            $childBlockType   = $this->layoutData->getProperty($childId, LayoutData::BLOCK_TYPE, true);
            $childView        = $this->createBlockView($childId, $childBlockType, $view);
            $view->children[] = $this->buildBlockView(
                $childId,
                $childBlockType,
                $this->layoutData->getProperty($childId, LayoutData::RESOLVED_OPTIONS, true),
                $children,
                $childView
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

        return count($types) > 1 && $types[1]->getName() === 'container';
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
