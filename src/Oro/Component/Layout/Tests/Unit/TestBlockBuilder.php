<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\LayoutBuilderInterface;

class TestBlockBuilder
{
    /** @var string */
    protected $id;

    /** @var LayoutBuilderInterface */
    protected $layoutBuilder;

    /** @var ContextInterface */
    protected $context;

    /** @var int */
    protected $childCount = 0;

    /**
     * @param LayoutBuilderInterface $layoutBuilder
     * @param ContextInterface       $context
     * @param string                 $id
     * @param string                 $blockType
     * @param array                  $options
     */
    public function __construct(
        LayoutBuilderInterface $layoutBuilder,
        ContextInterface $context,
        $id = null,
        $blockType = null,
        array $options = []
    ) {
        $this->layoutBuilder = $layoutBuilder;
        $this->context       = $context;
        $this->id            = $id;

        if ($blockType) {
            $this->layoutBuilder->add($id, null, $blockType, $options);
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
        $this->layoutBuilder->add($id, $this->id, $blockType, $options);

        return $this;
    }

    /**
     * Creates a view for the built block
     *
     * @return BlockView
     */
    public function getBlockView()
    {
        $layout = $this->layoutBuilder->getLayout($this->context);

        return $layout->getView();
    }

    /**
     * @param string $childId
     *
     * @return TestBlockBuilder
     */
    public function getChildBuilder($childId)
    {
        return new TestBlockBuilder($this->layoutBuilder, $this->context, $childId);
    }
}
