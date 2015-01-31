<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Exception;

class BlockRendererRegistry implements BlockRendererRegistryInterface
{
    /** @var BlockRendererInterface[] */
    protected $renderers;

    /** @var string */
    protected $defaultRendererName = '';

    /**
     * {@inheritdoc}
     */
    public function getRenderer($name = null)
    {
        if (!$name) {
            $name = $this->defaultRendererName;
        }
        if (!isset($this->renderers[$name])) {
            throw new Exception\LogicException(
                sprintf('The block renderer named "%s" was not found.', $name)
            );
        }

        return $this->renderers[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasRenderer($name)
    {
        return isset($this->renderers[$name]);
    }

    /**
     * Registers a block renderer
     *
     * @param string                 $name     The name of the block renderer
     * @param BlockRendererInterface $renderer A block renderer
     */
    public function addRenderer($name, BlockRendererInterface $renderer)
    {
        $this->renderers[$name] = $renderer;
    }

    /**
     * Sets the default block renderer
     *
     * @param string $name The name of the block renderer
     */
    public function setDefaultRenderer($name)
    {
        $this->defaultRendererName = $name;
    }
}
