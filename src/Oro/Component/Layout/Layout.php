<?php

namespace Oro\Component\Layout;

class Layout
{
    /** @var BlockView */
    protected $view;

    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /** @var string */
    protected $rendererName;

    /** @var array */
    protected $themes = [];

    /** @var array */
    protected $formThemes = [];

    /**
     * @param BlockView                       $view
     * @param LayoutRendererRegistryInterface $rendererRegistry
     */
    public function __construct(BlockView $view, LayoutRendererRegistryInterface $rendererRegistry)
    {
        $this->view             = $view;
        $this->rendererRegistry = $rendererRegistry;
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
        $renderer = $this->rendererRegistry->getRenderer($this->rendererName);
        foreach ($this->themes as $theme) {
            $renderer->setBlockTheme($theme[0], $theme[1]);
        }
        $renderer->setFormTheme($this->formThemes);

        return $renderer->renderBlock($this->view);
    }

    /**
     * Sets a renderer to be used to render this layout
     *
     * @param string $name The name of a layout renderer
     *
     * @return self
     */
    public function setRenderer($name)
    {
        $this->rendererName = $name;

        return $this;
    }

    /**
     * Sets the theme(s) to be used for rendering a block and its children
     *
     * @param string|string[] $themes  The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     * @param string|null     $blockId The id of a block to assign the theme(s) to
     *
     * @return self
     */
    public function setBlockTheme($themes, $blockId = null)
    {
        $view = $blockId
            ? $this->view[$blockId]
            : $this->view;

        $this->themes[] = [$view, $themes];

        return $this;
    }

    /**
     * Sets the theme(s) to be used for rendering forms
     *
     * @param string|string[] $themes  The theme(s). For example 'MyBundle:Layout:my_theme.html.twig'
     *
     * @return self
     */
    public function setFormTheme($themes)
    {
        $themes = is_array($themes) ? $themes : [$themes];
        $this->formThemes = array_merge($this->formThemes, $themes);

        return $this;
    }
}
