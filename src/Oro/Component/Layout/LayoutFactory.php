<?php

namespace Oro\Component\Layout;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var BlockRendererRegistryInterface */
    protected $rendererRegistry;

    /**
     * @param BlockRendererRegistryInterface $rendererRegistry
     */
    public function __construct(BlockRendererRegistryInterface $rendererRegistry)
    {
        $this->rendererRegistry = $rendererRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function createLayout(BlockView $view)
    {
        return new Layout($view, $this->rendererRegistry);
    }
}
