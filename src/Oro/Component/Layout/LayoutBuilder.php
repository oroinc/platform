<?php

namespace Oro\Component\Layout;

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

    /**
     * @param LayoutRegistryInterface            $registry
     * @param RawLayoutBuilderInterface          $rawLayoutBuilder
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     * @param BlockFactoryInterface              $blockFactory
     * @param LayoutRendererRegistryInterface    $rendererRegistry
     */
    public function __construct(
        LayoutRegistryInterface $registry,
        RawLayoutBuilderInterface $rawLayoutBuilder,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        BlockFactoryInterface $blockFactory,
        LayoutRendererRegistryInterface $rendererRegistry
    ) {
        $this->registry          = $registry;
        $this->rawLayoutBuilder  = $rawLayoutBuilder;
        $this->layoutManipulator = $layoutManipulator;
        $this->blockFactory      = $blockFactory;
        $this->rendererRegistry  = $rendererRegistry;
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
        $rawLayout   = $this->rawLayoutBuilder->getRawLayout();
        $rootView    = $this->blockFactory->createBlockView($rawLayout, $context, $rootId);
        $layout      = $this->createLayout($rootView);
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
}
