<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception;

interface BlockTypeRegistryInterface
{
    /**
     * Returns a block type by name.
     *
     * @param string $name The name of the block type
     *
     * @return BlockTypeInterface
     *
     * @throws Exception\UnexpectedTypeException
     * @throws Exception\InvalidArgumentException
     */
    public function getBlockType($name);

    /**
     * Returns whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool Whether the block type is supported
     */
    public function hasBlockType($name);
}
