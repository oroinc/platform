<?php

namespace Oro\Component\Layout;

interface BlockTypeFactoryInterface
{
    /**
     * Creates a block type
     *
     * @param $name string Name of block type
     *
     * @return BlockTypeInterface
     */
    public function createBlockType($name);
}
