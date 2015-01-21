<?php

namespace Oro\Component\Layout;

interface BlockBuilderInterface
{
    /**
     * Creates the block.
     *
     * @return BlockInterface
     */
    public function getBlock();
}
