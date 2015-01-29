<?php

namespace Oro\Component\Layout;

class Layout
{
    /** @var BlockView */
    protected $view;

    /** @var BlockRendererInterface */
    protected $renderer;

    /**
     * @param BlockView              $view
     * @param BlockRendererInterface $renderer
     */
    public function __construct(BlockView $view, BlockRendererInterface $renderer)
    {
        $this->view     = $view;
        $this->renderer = $renderer;
    }

    /**
     * @return BlockView
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Renders the layout
     *
     * @return string
     */
    public function render()
    {
        return $this->renderer->renderBlock($this->view);
    }

    /**
     * Sets the theme(s) to be used for rendering a block and its children
     *
     * @param string|string[] $themes  The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     * @param string|null     $blockId The id of a block to assign the theme(s) to
     */
    public function setBlockTheme($themes, $blockId = null)
    {
        $view = $blockId
            ? $this->view[$blockId]
            : $this->view;

        $this->renderer->setTheme($view, $themes);
    }
}
