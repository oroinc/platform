<?php

namespace Oro\Component\Layout;

/**
 * Registry for managing layout renderers by name with a default renderer.
 *
 * This registry stores layout renderers indexed by name and maintains a default renderer
 * that is used when no specific renderer is requested.
 */
class LayoutRendererRegistry implements LayoutRendererRegistryInterface
{
    /** @var LayoutRendererInterface[] */
    protected $renderers;

    /** @var string */
    protected $defaultRendererName = '';

    #[\Override]
    public function getRenderer($name = null)
    {
        if (!$name) {
            $name = $this->defaultRendererName;
        }
        if (!isset($this->renderers[$name])) {
            throw new Exception\LogicException(
                sprintf('The layout renderer named "%s" was not found.', $name)
            );
        }

        return $this->renderers[$name];
    }

    #[\Override]
    public function hasRenderer($name)
    {
        return isset($this->renderers[$name]);
    }

    /**
     * Registers a layout renderer
     *
     * @param string                  $name     The name of the renderer
     * @param LayoutRendererInterface $renderer A layout renderer
     */
    public function addRenderer($name, LayoutRendererInterface $renderer)
    {
        $this->renderers[$name] = $renderer;
    }

    /**
     * Sets the default renderer
     *
     * @param string $name The name of a renderer
     */
    public function setDefaultRenderer($name)
    {
        $this->defaultRendererName = $name;
    }
}
