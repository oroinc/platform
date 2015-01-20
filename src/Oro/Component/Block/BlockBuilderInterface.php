<?php

namespace Oro\Component\Block;

interface BlockBuilderInterface
{
    /**
     * Creates the block.
     *
     * @return BlockInterface
     */
    public function getBlock();
}
