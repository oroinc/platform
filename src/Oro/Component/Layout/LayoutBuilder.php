<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception\BlockViewNotFoundException;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * Responsible for building {@see Layout}.
 */
class LayoutBuilder implements LayoutBuilderInterface
{
    private const BLOCK_THEMES = '_blockThemes';
    private const FORM_THEMES = '_formThemes';

    protected LayoutRegistryInterface $registry;

    protected RawLayoutBuilderInterface $rawLayoutBuilder;

    protected DeferredLayoutManipulatorInterface $layoutManipulator;

    protected BlockFactoryInterface $blockFactory;

    protected LayoutRendererRegistryInterface $rendererRegistry;

    protected ExpressionProcessor $expressionProcessor;

    protected LayoutContextStack $layoutContextStack;

    private ?BlockViewCache $blockViewCache;

    public function __construct(
        LayoutRegistryInterface $registry,
        RawLayoutBuilderInterface $rawLayoutBuilder,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        BlockFactoryInterface $blockFactory,
        LayoutRendererRegistryInterface $rendererRegistry,
        ExpressionProcessor $expressionProcessor,
        LayoutContextStack $layoutContextStack,
        BlockViewCache $blockViewCache = null
    ) {
        $this->registry            = $registry;
        $this->rawLayoutBuilder    = $rawLayoutBuilder;
        $this->layoutManipulator   = $layoutManipulator;
        $this->blockFactory        = $blockFactory;
        $this->rendererRegistry    = $rendererRegistry;
        $this->expressionProcessor = $expressionProcessor;
        $this->layoutContextStack  = $layoutContextStack;
        $this->blockViewCache      = $blockViewCache;
    }

    /**
     * {@inheritdoc}
     */
    public function add(
        $id,
        $parentId,
        $blockType,
        array $options = [],
        $siblingId = null,
        $prepend = null
    ) {
        $this->layoutManipulator->add($id, $parentId, $blockType, $options, $siblingId, $prepend);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($id)
    {
        $this->layoutManipulator->remove($id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function move($id, $parentId = null, $siblingId = null, $prepend = null)
    {
        $this->layoutManipulator->move($id, $parentId, $siblingId, $prepend);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addAlias($alias, $id)
    {
        $this->layoutManipulator->addAlias($alias, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAlias($alias)
    {
        $this->layoutManipulator->removeAlias($alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $this->layoutManipulator->setOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function appendOption($id, $optionName, $optionValue)
    {
        $this->layoutManipulator->appendOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function subtractOption($id, $optionName, $optionValue)
    {
        $this->layoutManipulator->subtractOption($id, $optionName, $optionValue);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceOption($id, $optionName, $oldOptionValue, $newOptionValue)
    {
        $this->layoutManipulator->replaceOption($id, $optionName, $oldOptionValue, $newOptionValue);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $this->layoutManipulator->removeOption($id, $optionName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function changeBlockType($id, $blockType, $optionsCallback = null)
    {
        $this->layoutManipulator->changeBlockType($id, $blockType, $optionsCallback);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setBlockTheme($themes, $id = null)
    {
        $this->layoutManipulator->setBlockTheme($themes, $id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormTheme($themes)
    {
        $this->layoutManipulator->setFormTheme($themes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->layoutManipulator->clear();
        $this->rawLayoutBuilder->clear();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout(ContextInterface $context, $rootId = null)
    {
        if (!$context->isResolved()) {
            $this->registry->configureContext($context);
            $context->resolve();
        }

        if ($this->blockViewCache) {
            $rootView = $this->blockViewCache->fetch($context);
            if ($rootView === null) {
                $rootView = $this->buildLayout($context);

                $this->blockViewCache->save($context, $rootView);
            }
        } else {
            $rootView = $this->buildLayout($context);
        }

        $rootBlockId = $rootView->getId();
        $blockThemes = $rootView->vars[self::BLOCK_THEMES];
        $formThemes = $rootView->vars[self::FORM_THEMES];
        $rootView = $this->getRootView($rootView, $rootId);

        if ($context->getOr('expressions_evaluate')) {
            $deferred = $context->getOr('expressions_evaluate_deferred');
            $encoding = $context->getOr('expressions_encoding');

            $this->processBlockViewData(
                $rootView,
                $context,
                new DataAccessor($this->registry, $context),
                $deferred,
                $encoding
            );
        }

        $layout = $this->createLayout($rootView, $context);

        foreach ($blockThemes as $blockId => $themes) {
            $layout->setBlockTheme($themes, $blockId !== $rootBlockId ? $blockId : null);
        }

        $layout->setFormTheme($formThemes);

        return $layout;
    }

    protected function buildLayout(ContextInterface $context): BlockView
    {
        $this->layoutManipulator->applyChanges($context);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();
        $rootView = $this->blockFactory->createBlockView($rawLayout, $context);
        $rootView->vars[self::BLOCK_THEMES] = $rawLayout->getBlockThemes();
        $rootView->vars[self::FORM_THEMES] = $rawLayout->getFormThemes();

        return $rootView;
    }

    /**
     * @param BlockView $rootView
     *
     * @return Layout
     */
    protected function createLayout(BlockView $rootView, ContextInterface $context)
    {
        return new Layout($rootView, $this->rendererRegistry, $context, $this->layoutContextStack);
    }

    /**
     * Processes expressions that work with data
     *
     * @param BlockView $blockView
     * @param ContextInterface $context
     * @param DataAccessor $data
     * @param bool $deferred
     * @param string $encoding
     */
    protected function processBlockViewData(
        BlockView $blockView,
        ContextInterface $context,
        DataAccessor $data,
        $deferred,
        $encoding
    ) {
        if ($deferred) {
            $this->expressionProcessor->processExpressions($blockView->vars, $context, $data, true, $encoding);
        }

        $this->buildValueBags($blockView);

        /** Removes child blocks in case parent block is hidden */
        if (!$blockView->isVisible()) {
            foreach ($blockView->children as $key => $childView) {
                unset($blockView->children[$key]);
            }

            return;
        }

        foreach ($blockView->children as $key => $childView) {
            $this->processBlockViewData($childView, $context, $data, $deferred, $encoding);

            if (!$childView->isVisible()) {
                unset($blockView->children[$key]);
            }
        }
    }

    protected function buildValueBags(BlockView $view)
    {
        array_walk_recursive(
            $view->vars,
            function (&$var) {
                if ($var instanceof OptionValueBag) {
                    $var = $var->buildValue();
                }
            }
        );
    }

    /**
     * @param BlockView    $rootView
     * @param int|null     $rootId
     *
     * @return BlockView
     * @throws BlockViewNotFoundException
     */
    private function getRootView(BlockView $rootView, $rootId)
    {
        if ($rootId !== null) {
            $rootView = $this->findBlockById($rootView, $rootId);
            if ($rootView === null) {
                throw new BlockViewNotFoundException(sprintf("BlockView with id \"%s\" is not found.", $rootId));
            }
        }

        return $rootView;
    }

    /**
     * @param BlockView $blockView
     * @param int       $id
     *
     * @return BlockView|null
     */
    private function findBlockById(BlockView $blockView, $id)
    {
        if ($blockView->getId() === $id) {
            return $blockView;
        }

        foreach ($blockView->children as $childView) {
            if ($childView->getId() === $id) {
                return $childView;
            }

            $childView = $this->findBlockById($childView, $id);
            if ($childView) {
                return $childView;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotAppliedActions()
    {
        return $this->layoutManipulator->getNotAppliedActions();
    }

    public function getLayoutContextStack(): LayoutContextStack
    {
        return $this->layoutContextStack;
    }
}
