<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for managing layout renderers by name.
 *
 * This registry provides access to layout renderers by name and supports querying for renderer availability,
 * with support for a default renderer when no specific renderer is requested.
 */
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
