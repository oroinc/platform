<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Symfony\Component\ExpressionLanguage\Expression;

class BlockFactory implements BlockFactoryInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var ExpressionProcessor */
    protected $expressionProcessor;

    /** @var BlockOptionsResolver */
    protected $optionsResolver;

    /** @var BlockTypeHelperInterface */
    protected $typeHelper;

    /** @var RawLayout */
    protected $rawLayout;

    /** @var ContextInterface */
    protected $context;

    /** @var DataAccessorInterface */
    protected $dataAccessor;

    /** @var BlockBuilder */
    protected $blockBuilder;

    /** @var Block */
    protected $block;

    /**
     * @param LayoutRegistryInterface            $registry
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     * @param ExpressionProcessor                $expressionProcessor
     */
    public function __construct(
        LayoutRegistryInterface $registry,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        ExpressionProcessor $expressionProcessor
    ) {
        $this->registry            = $registry;
        $this->layoutManipulator   = $layoutManipulator;
        $this->expressionProcessor = $expressionProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockView(RawLayout $rawLayout, ContextInterface $context)
    {
        $this->initializeState($rawLayout, $context);
        try {
            $rootId = $this->rawLayout->getRootId();

            $this->buildBlocks($rootId);
            $this->layoutManipulator->applyChanges($this->context, true);
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
        $this->rawLayout = $rawLayout;
        $this->context   = $context;

        $this->dataAccessor    = new DataAccessor($this->registry, $this->context);
        $this->optionsResolver = new BlockOptionsResolver($this->registry);
        $this->typeHelper      = new BlockTypeHelper($this->registry);
        $this->blockBuilder    = new BlockBuilder(
            $this->layoutManipulator,
            $this->rawLayout,
            $this->typeHelper,
            $this->context
        );
        $this->block           = new Block(
            $this->rawLayout,
            $this->typeHelper,
            $this->context,
            $this->dataAccessor
        );
    }

    /**
     * Clears the state of this factory
     */
    protected function clearState()
    {
        $this->rawLayout       = null;
        $this->context         = null;
        $this->dataAccessor    = null;
        $this->optionsResolver = null;
        $this->typeHelper      = null;
        $this->blockBuilder    = null;
        $this->block           = null;
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
        // build the root block
        if (!$this->rawLayout->hasProperty($rootId, RawLayout::RESOLVED_OPTIONS, true)) {
            $this->buildBlock($rootId);
        }

        // build child blocks
        $iterator = $this->rawLayout->getHierarchyIterator($rootId);
        foreach ($iterator as $id) {
            if (!$this->rawLayout->has($id) || $this->rawLayout->hasProperty($id, RawLayout::RESOLVED_OPTIONS, true)) {
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
        $this->layoutManipulator->applyChanges($this->context);
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

        $viewsCollection = new BlockViewCollection($views);
        foreach ($views as $view) {
            $view->blocks = $viewsCollection;
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
        $types     = $this->typeHelper->getTypes($blockType);

        $this->setBlockResolvedOptions($id, $blockType, $options, $types);
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
        $blockType       = $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true);
        $resolvedOptions = $this->rawLayout->getProperty($id, RawLayout::RESOLVED_OPTIONS, true);
        $types           = $this->typeHelper->getTypes($blockType);
        $view            = new BlockView($parentView);

        if (is_null($resolvedOptions)) { // Try to resolve options again to render block
            $options  = $this->rawLayout->getProperty($id, RawLayout::OPTIONS, true);
            $resolvedOptions = $this->setBlockResolvedOptions($id, $blockType, $options, $types);
        }

        // point the block view state to the current block
        $this->block->initialize($id);
        // build the view
        foreach ($types as $type) {
            $type->buildView($view, $this->block, $resolvedOptions);
            $this->registry->buildView($type->getName(), $view, $this->block, $resolvedOptions);
        }

        array_walk_recursive(
            $view->vars,
            function (&$var) {
                if ($var instanceof Options) {
                    $var = $var->toArray();
                }
            }
        );

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
        $types     = $this->typeHelper->getTypes($blockType);

        // point the block view state to the current block
        $this->block->initialize($id);
        // finish the view
        foreach ($types as $type) {
            $type->finishView($view, $this->block);
            $this->registry->finishView($type->getName(), $view, $this->block);
        }
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
        return $this->typeHelper->isInstanceOf(
            $this->rawLayout->getProperty($id, RawLayout::BLOCK_TYPE, true),
            ContainerType::NAME
        );
    }

    /**
     * Processes expressions that don't work with data
     *
     * @param Options $options
     */
    protected function processExpressions(Options $options)
    {
        if (!$this->context->getOr('expressions_evaluate')) {
            return;
        }

        $values = $options->toArray();

        if ($this->context->getOr('expressions_evaluate_deferred')) {
            $this->expressionProcessor->processExpressions($values, $this->context, null, true, null);
        } else {
            $this->expressionProcessor->processExpressions(
                $values,
                $this->context,
                $this->dataAccessor,
                true,
                $this->context->getOr('expressions_encoding')
            );
        }

        $options->setMultiple($values);
    }

    /**
     * @param Options $options
     * @return Options
     */
    protected function resolveValueBags(Options $options)
    {
        foreach ($options as $key => $value) {
            if ($value instanceof Expression) {
                continue;
            }

            if ($value instanceof Options) {
                $options[$key] = $this->resolveValueBags($value);
            } elseif ($value instanceof OptionValueBag) {
                $options[$key] = $value->buildValue();
            }
        }

        return $options;
    }

    /**
     * Setting resolved options for block
     *
     * @param string $id
     * @param string $blockType
     * @param array  $options
     * @param array  $types
     *
     * @return Options
     */
    protected function setBlockResolvedOptions($id, $blockType, $options, $types)
    {
        // resolve options
        $resolvedOptions = new Options($this->optionsResolver->resolveOptions($blockType, $options));

        $this->processExpressions($resolvedOptions);
        $resolvedOptions = $this->resolveValueBags($resolvedOptions);

        if ($resolvedOptions->get('visible', false) !== false) {
            // point the block builder state to the current block
            $this->blockBuilder->initialize($id);
            // iterate from parent to current
            foreach ($types as $type) {
                $type->buildBlock($this->blockBuilder, $resolvedOptions);
                $this->registry->buildBlock($type->getName(), $this->blockBuilder, $resolvedOptions);
            }
            $this->rawLayout->setProperty($id, RawLayout::RESOLVED_OPTIONS, $resolvedOptions);
        } else {
            $this->rawLayout->remove($id);
        }

        return $resolvedOptions;
    }
}
