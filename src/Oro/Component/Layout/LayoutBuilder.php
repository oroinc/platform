<?php

namespace Oro\Component\Layout;

class LayoutBuilder implements LayoutBuilderInterface
{
    /** @var RawLayoutBuilderInterface */
    protected $rawLayoutBuilder;

    /** @var DeferredLayoutManipulatorInterface */
    protected $layoutManipulator;

    /** @var BlockFactoryInterface */
    protected $blockFactory;

    /** @var LayoutFactoryInterface */
    protected $layoutFactory;

    /**
     * @param RawLayoutBuilderInterface          $rawLayoutBuilder
     * @param DeferredLayoutManipulatorInterface $layoutManipulator
     * @param BlockFactoryInterface              $blockFactory
     * @param LayoutFactoryInterface             $layoutFactory
     */
    public function __construct(
        RawLayoutBuilderInterface $rawLayoutBuilder,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        BlockFactoryInterface $blockFactory,
        LayoutFactoryInterface $layoutFactory
    ) {
        $this->rawLayoutBuilder  = $rawLayoutBuilder;
        $this->layoutManipulator = $layoutManipulator;
        $this->blockFactory      = $blockFactory;
        $this->layoutFactory     = $layoutFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function add(
        $id,
        $parentId = null,
        $blockType = null,
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
    public function removeOption($id, $optionName)
    {
        $this->layoutManipulator->removeOption($id, $optionName);

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
    public function clear()
    {
        $this->layoutManipulator->clear();
        $this->rawLayoutBuilder->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getLayout(ContextInterface $context, $rootId = null)
    {
        $this->layoutManipulator->applyChanges();
        $rawLayout   = $this->rawLayoutBuilder->getRawLayout();
        $rootView    = $this->blockFactory->createBlockView($rawLayout, $context, $rootId);
        $layout      = $this->layoutFactory->createLayout($rootView);
        $rootBlockId = $rawLayout->getRootId();
        $blockThemes = $rawLayout->getBlockThemes();
        foreach ($blockThemes as $blockId => $themes) {
            $layout->setBlockTheme($themes, $blockId !== $rootBlockId ? $blockId : null);
        }

        return $layout;
    }
}
