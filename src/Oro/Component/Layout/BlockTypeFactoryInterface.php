<?php

namespace Oro\Component\Layout;

interface BlockTypeFactoryInterface
{
    /**
     * Creates a block type
     *
     * @param $name string The name of the block type
     *
     * @return BlockTypeInterface|null
     */
    public function createBlockType($name);
}
