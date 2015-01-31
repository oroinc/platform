<?php

namespace Oro\Component\Layout;

class LayoutManager
{
    /** @var LayoutBuilderInterface */
    protected $layoutBuilder;

    /**
     * @param LayoutBuilderInterface $layoutBuilder
     */
    public function __construct(LayoutBuilderInterface $layoutBuilder)
    {
        $this->layoutBuilder = $layoutBuilder;
    }

    /**
     * @return LayoutBuilderInterface
     */
    public function getLayoutBuilder()
    {
        $this->layoutBuilder->clear();

        return $this->layoutBuilder;
    }
}
