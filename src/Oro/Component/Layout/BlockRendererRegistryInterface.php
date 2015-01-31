<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception;

interface BlockRendererRegistryInterface
{
    /**
     * Returns a block renderer by name
     *
     * @param string|null $name The name of the block renderer
     *                          If the name is not specified a default renderer is returned
     *
     * @return BlockRendererInterface
     *
     * @throws Exception\LogicException if a renderer does not exist
     */
    public function getRenderer($name = null);

    /**
     * Returns whether the given block renderer is supported
     *
     * @param string $name The name of the block renderer
     *
     * @return bool Whether the block renderer is supported
     */
    public function hasRenderer($name);
}
