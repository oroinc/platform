<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;

class LayoutBuilder implements LayoutBuilderInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var RawLayoutBuilderInterface */
    protected $rawLayoutBuilder;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var BlockFactoryInterface */
    protected $blockFactory;

    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /** @var ExpressionProcessor */
    protected $expressionProcessor;

    /**
     * @param LayoutRegistryInterface            $registry
     * @param RawLayoutBuilderInterface          $rawLayoutBuilder
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     * @param BlockFactoryInterface              $blockFactory
     * @param LayoutRendererRegistryInterface    $rendererRegistry
     * @param ExpressionProcessor                $expressionProcessor
     */
    public function __construct(
        LayoutRegistryInterface $registry,
        RawLayoutBuilderInterface $rawLayoutBuilder,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        BlockFactoryInterface $blockFactory,
        LayoutRendererRegistryInterface $rendererRegistry,
        ExpressionProcessor $expressionProcessor
    ) {
        $this->registry            = $registry;
        $this->rawLayoutBuilder    = $rawLayoutBuilder;
        $this->layoutManipulator   = $layoutManipulator;
        $this->blockFactory        = $blockFactory;
        $this->rendererRegistry    = $rendererRegistry;
        $this->expressionProcessor = $expressionProcessor;
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
        $prepend = false
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
    public function move($id, $parentId = null, $siblingId = null, $prepend = false)
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

        $this->layoutManipulator->applyChanges($context);
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();
        $rootView = $this->blockFactory->createBlockView($rawLayout, $context, $rootId);

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

        $layout = $this->createLayout($rootView);
        $rootBlockId = $rawLayout->getRootId();
        $blockThemes = $rawLayout->getBlockThemes();

        foreach ($blockThemes as $blockId => $themes) {
            $layout->setBlockTheme($themes, $blockId !== $rootBlockId ? $blockId : null);
        }

        $formThemes = $rawLayout->getFormThemes();
        $layout->setFormTheme($formThemes);

        return $layout;
    }

    /**
     * @param BlockView $rootView
     *
     * @return Layout
     */
    protected function createLayout(BlockView $rootView)
    {
        return new Layout($rootView, $this->rendererRegistry);
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
        $encoding,
        $deferred
    ) {
        if ($deferred) {
            $this->expressionProcessor->processExpressions($blockView->vars, $context, $data, true, $encoding);
        }

        $this->buildValueBags($blockView);

        foreach ($blockView->children as $childView) {
            $this->processBlockViewData($childView, $context, $data, $deferred, $encoding);
        }
    }

    /**
     * @param BlockView $view
     */
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
}
