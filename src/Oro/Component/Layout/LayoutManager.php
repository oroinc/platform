<?php

namespace Oro\Component\Layout;

class LayoutManager
{
    /** @var LayoutBuilder */
    protected $layoutBuilder;

    /**
     * @param LayoutBuilder $layoutBuilder
     */
    public function __construct(LayoutBuilder $layoutBuilder)
    {
        $this->layoutBuilder = $layoutBuilder;
    }

    /**
     * @return RawLayoutManipulatorInterface
     */
    public function getLayoutBuilder()
    {
        return $this->layoutBuilder;
    }
}
