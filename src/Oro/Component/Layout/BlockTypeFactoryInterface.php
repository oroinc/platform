<?php

namespace Oro\Component\Layout;

interface BlockTypeFactoryInterface
{
    /**
     * Creates a block type
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface|null
     */
    public function createBlockType($name);
}
