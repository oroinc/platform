<?php

namespace Oro\Component\Block;

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
