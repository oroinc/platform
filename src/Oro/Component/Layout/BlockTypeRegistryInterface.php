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
     * @throws Exception\ExceptionInterface
     */
    public function getBlockType($name);

    /**
     * Returns whether the given block type is supported.
     *
     * @param string $name The name of the block type
     *
     * @return bool Whether the block type is supported
     *
     * @throws Exception\ExceptionInterface
     */
    public function hasBlockType($name);
}
