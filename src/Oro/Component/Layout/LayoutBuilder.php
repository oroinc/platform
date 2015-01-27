<?php

namespace Oro\Component\Layout;

class LayoutBuilder implements LayoutBuilderInterface
{
    /** @var LayoutDataBuilder */
    protected $layoutDataBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    /**
     * @param LayoutDataBuilder         $layoutDataBuilder
     * @param DeferredLayoutManipulator $layoutManipulator
     * @param LayoutViewFactory         $layoutViewFactory
     */
    public function __construct(
        LayoutDataBuilder $layoutDataBuilder,
        DeferredLayoutManipulator $layoutManipulator,
        LayoutViewFactory $layoutViewFactory
    ) {
        $this->layoutDataBuilder = $layoutDataBuilder;
        $this->layoutManipulator = $layoutManipulator;
        $this->layoutViewFactory = $layoutViewFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function add($id, $parentId = null, $blockType = null, array $options = [])
    {
        $this->layoutManipulator->add($id, $parentId, $blockType, $options);

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
    public function getLayout(ContextInterface $context, $rootId = null)
    {
        $this->layoutManipulator->applyChanges();
        $layoutData = $this->layoutDataBuilder->getLayoutData();
        $rootView   = $this->layoutViewFactory->createView($layoutData, $context, $rootId);

        return new Layout($rootView);
    }
}
