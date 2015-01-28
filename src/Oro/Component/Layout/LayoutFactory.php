<?php

namespace Oro\Component\Layout;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var BlockRendererInterface */
    protected $renderer;

    /**
     * @param BlockRendererInterface $renderer
     */
    public function __construct(BlockRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function createLayout(BlockView $view)
    {
        return new Layout($view, $this->renderer);
    }
}
