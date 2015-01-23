<?php

namespace Oro\Component\Layout;

class LayoutBlock implements BlockInterface
{
    /** @var string */
    protected $blockId;

    /** @var LayoutData */
    protected $layoutData;

    /**
     * @param LayoutData $layoutData
     * @param string     $blockId
     */
    public function __construct(LayoutData $layoutData, $blockId)
    {
        $this->layoutData = $layoutData;
        $this->blockId    = $blockId;
    }
}
