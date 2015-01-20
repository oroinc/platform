<?php

namespace Oro\Component\Block;

interface BlockRegistryInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getType($name);

    /**
     * Returns whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool Whether the block type is supported
     *
     * @throws \InvalidArgumentException
     */
    public function hasType($name);
}
