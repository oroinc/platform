<?php

namespace Oro\Component\Layout;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /**
     * @param LayoutRendererRegistryInterface $rendererRegistry
     */
    public function __construct(LayoutRendererRegistryInterface $rendererRegistry)
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
