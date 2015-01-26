<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutViewFactory;

class TestBlockBuilder
{
    /** @var string */
    protected $id;

    /** @var int */
    protected $childCount = 0;

    /** @var LayoutContext */
    protected $context;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    /**
     * @param LayoutBuilder             $layoutBuilder
     * @param DeferredLayoutManipulator $layoutManipulator
     * @param LayoutViewFactory         $layoutViewFactory
     * @param LayoutContext             $context
     * @param string                    $id
     * @param string                    $blockType
     * @param array                     $options
     */
    public function __construct(
        LayoutBuilder $layoutBuilder,
        DeferredLayoutManipulator $layoutManipulator,
        LayoutViewFactory $layoutViewFactory,
        LayoutContext $context,
        $id = null,
        $blockType = null,
        array $options = []
    ) {
        $this->layoutBuilder     = $layoutBuilder;
        $this->layoutManipulator = $layoutManipulator;
        $this->layoutViewFactory = $layoutViewFactory;
        $this->context           = $context;
        $this->id                = $id;

        if ($blockType) {
            $this->layoutManipulator->add($id, null, $blockType, $options);
        }
    }

    /**
     * Adds a child block
     *
     * @param string $blockType
     * @param array  $options
     *
     * @return TestBlockBuilder
     */
    public function add($blockType, array $options = [])
    {
        $id = sprintf('%s_%s_id%d', $this->id, $blockType, ++$this->childCount);
        $this->layoutManipulator->add($id, $this->id, $blockType, $options);

        return $this;
    }

    /**
     * Creates a view for the built block
     *
     * @return BlockView
     */
    public function getBlockView()
    {
        $this->layoutManipulator->applyChanges();
        $layoutData = $this->layoutBuilder->getLayout();

        return $this->layoutViewFactory->createView($layoutData, $this->context);
    }

    /**
     * @param string $childId
     *
     * @return TestBlockBuilder
     */
    public function getChildBuilder($childId)
    {
        return new TestBlockBuilder(
            $this->layoutBuilder,
            $this->layoutManipulator,
            $this->layoutViewFactory,
            $this->context,
            $childId
        );
    }
}
