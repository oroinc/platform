<?php

namespace Oro\Component\Layout;

class LayoutBlockBuilder extends LayoutBlock implements BlockBuilderInterface
{
    /**
     * Creates the block.
     *
     * @return BlockInterface
     */
    public function getBlock()
    {
        return $this;
    }
}
