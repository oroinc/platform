<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception;

interface LayoutRendererRegistryInterface
{
    /**
     * Returns a renderer by name
     *
     * @param string|null $name The name of a renderer
     *                          If the name is not specified a default renderer is returned
     *
     * @return LayoutRendererInterface
     *
     * @throws Exception\LogicException if a renderer does not exist
     */
    public function getRenderer($name = null);

    /**
     * Checks whether the given renderer is supported
     *
     * @param string $name The name of a renderer
     *
     * @return bool Whether the renderer is supported
     */
    public function hasRenderer($name);
}
