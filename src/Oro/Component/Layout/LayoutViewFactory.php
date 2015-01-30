<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\Type\ContainerType;

class LayoutViewFactory implements LayoutViewFactoryInterface
{
    /** @var BlockTypeRegistryInterface */
    protected $blockTypeRegistry;

    /** @var BlockOptionsResolverInterface */
    protected $blockOptionsResolver;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var RawLayout */
    protected $rawLayout;

    /** @var BlockBuilder */
    protected $currentBlockBuilder;

    /** @var Block */
    protected $currentBlock;

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
    public function createView(RawLayout $rawLayout, ContextInterface $context, $rootId = null)
    {
        $this->initializeState($rawLayout, $context);
        try {
            $rootId = $rootId
                ? $this->rawLayout->resolveId($rootId)
                : $this->rawLayout->getRootId();

            $this->buildBlocks($rootId);
            $rootView = $this->buildBlockViews($rootId);

            $this->clearState();

            return $rootView;
        } catch (\Exception $e) {
            $this->clearState();
            throw $e;
        }
    }

    /**
     * Initializes the state of this factory
     *
     * @param RawLayout        $rawLayout
     * @param ContextInterface $context
     */
    protected function initializeState(RawLayout $rawLayout, ContextInterface $context)
    {
        $this->rawLayout           = $rawLayout;
        $this->currentBlockBuilder = $this->createBlockBuilder($context);
        $this->currentBlock        = $this->createBlock($context);
    }

    /**
     * Clears the state of this factory
     */
    protected function clearState()
    {
        $this->rawLayout           = null;
        $this->currentBlockBuilder = null;
        $this->currentBlock        = null;
    }

    /**
     * Builds all blocks starting with and including the given root block
     *
     * @param string $rootId
     *
     * @throws Exception\LogicException if a child block is added to not container
     */
    protected function buildBlocks($rootId)
    {
        $this->layoutManipulator->resetCounters();

        // build the root block
        if (!$this->rawLayout->hasProperty($rootId, RawLayout::RESOLVED_OPTIONS, true)) {
            $this->buildBlock($rootId);
        }

        // build child blocks
        $iterator = $this->rawLayout->getHierarchyIterator($rootId);
        foreach ($iterator as $id) {
            if ($this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS, true)) {
                // the block is already built
                continue;
            }
            if (!$this->isContainerBlock($iterator->getParent())) {
                $blockType = $this->rawLayout->getProperty($iterator->getParent(), RawLayout::BLOCK_TYPE, true);

                throw new Exception\LogicException(
                    sprintf(
                        'The "%s" item cannot be added as a child to "%s" item (block type: %s) '
                        . 'because only container blocks can have children.',
                        $id,
                        $iterator->getParent(),
                        $blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType
                    )
                );
            }
            $this->buildBlock($id);
        }

        // apply layout changes were made by built blocks and build newly added blocks
        $this->layoutManipulator->applyChanges();
        if ($this->layoutManipulator->getNumberOfAddedItems() !== 0) {
            $this->buildBlocks($rootId);
        }
    }

    /**
     * Builds views for all blocks starting with and including the given root block
     *
     * @param string $rootId
     *
     * @return BlockView The root block view
     */
    protected function buildBlockViews($rootId)
    {
        /** @var BlockView[] $views */
        $views = [];

        // build the root view
        $rootView       = $this->buildBlockView($rootId);
        $views[$rootId] = $rootView;
        // build child views
        $iterator = $this->rawLayout->getHierarchyIterator($rootId);
        foreach ($iterator as $id) {
            $parentView = $views[$iterator->getParent()];

            // build child view
            $view                      = $this->buildBlockView($id, $parentView);
            $parentView->children[$id] = $view;
            $views[$id]                = $view;
        }

        // finish the root view
        $this->finishBlockView($rootView, $rootId);
        // finish child views
        foreach ($iterator as $id) {
            $this->finishBlockView($views[$id], $id);
        }

        return $rootView;
    }

    /**
     * Builds the block
     *
     * @param string $id
     */
    protected function buildBlock($id)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::OPTIONS, true);
        $types     = $this->blockTypeRegistry->getBlockTypeChain($blockType);

        // resolve options
        $resolvedOptions = $this->blockOptionsResolver->resolve($blockType, $options);
        $this->rawLayout->setProperty($id, RawLayout::RESOLVED_OPTIONS, $resolvedOptions);

        // point the block builder state to the current block
        $this->currentBlockBuilder->initialize($id);
        // iterate from parent to current
        foreach ($types as $type) {
            $type->buildBlock($this->currentBlockBuilder, $resolvedOptions);
        }
    }

    /**
     * Created and builds the block view
     *
     * @param string         $id
     * @param BlockView|null $parentView
     *
     * @return BlockView
     */
    protected function buildBlockView($id, BlockView $parentView = null)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::RESOLVED_OPTIONS, true);
        $types     = $this->blockTypeRegistry->getBlockTypeChain($blockType);
        $typeNames = $this->getBlockTypeNames($types);

        $view = new BlockView($typeNames, $parentView);

        // add core variables to the block view, like id and variables required for rendering engine
        $uniqueBlockPrefix                 = '_' . $id;
        $blockPrefixes                     = $typeNames;
        $blockPrefixes[]                   = $uniqueBlockPrefix;
        $view->vars['id']                  = $id;
        $view->vars['unique_block_prefix'] = $uniqueBlockPrefix;
        $view->vars['block_prefixes']      = $blockPrefixes;
        $view->vars['cache_key']           = sprintf(
            '%s_%s',
            $uniqueBlockPrefix,
            $blockType instanceof BlockTypeInterface ? $blockType->getName() : $blockType
        );

        // point the block view state to the current block
        $this->currentBlock->initialize($id);
        // build the view
        foreach ($types as $type) {
            $type->buildView($view, $this->currentBlock, $options);
        }

        return $view;
    }

    /**
     * Finishes the building of the block view
     *
     * @param BlockView $view
     * @param string    $id
     */
    protected function finishBlockView(BlockView $view, $id)
    {
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $options   = $this->rawLayout->getProperty($id, RawLayout::RESOLVED_OPTIONS, true);
        $types     = $this->blockTypeRegistry->getBlockTypeChain($blockType);

        // point the block view state to the current block
        $this->currentBlock->initialize($id);
        // finish the view
        foreach ($types as $type) {
            $type->finishView($view, $this->currentBlock, $options);
        }
    }

    /**
     * Creates new instance of the block builder
     *
     * @param ContextInterface $context
     *
     * @return BlockBuilder
     */
    protected function createBlockBuilder(ContextInterface $context)
    {
        return new BlockBuilder($this->layoutManipulator, $context);
    }

    /**
     * Creates new instance of the block
     *
     * @param ContextInterface $context
     *
     * @return Block
     */
    protected function createBlock(ContextInterface $context)
    {
        return new Block($context);
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
        $blockType = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $types     = $this->blockTypeRegistry->getBlockTypeChain($blockType);

        return count($types) > 1 && $types[1]->getName() === ContainerType::NAME;
    }

    /**
     * Returns names of the given block types
     *
     * @param BlockTypeInterface[] $types
     *
     * @return string[]
     */
    protected function getBlockTypeNames($types)
    {
        return array_map(
            function (BlockTypeInterface $type) {
                return $type->getName();
            },
            $types
        );
    }
}
