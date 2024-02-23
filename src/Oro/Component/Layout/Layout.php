<?php

namespace Oro\Component\Layout;

/**
 * Responsible for setting a renderer to be used to render this layout as well as blockThemes
 */
class Layout
{
    protected BlockView $view;

    protected LayoutRendererRegistryInterface $rendererRegistry;

    protected ContextInterface $context;

    protected LayoutContextStack $layoutContextStack;

    protected ?string $rendererName = null;

    protected array $themes = [];

    protected array $formThemes = [];

    public function __construct(
        BlockView $view,
        LayoutRendererRegistryInterface $rendererRegistry,
        ContextInterface $context,
        LayoutContextStack $layoutContextStack
    ) {
        $this->view = $view;
        $this->context = $context;
        $this->rendererRegistry = $rendererRegistry;
        $this->layoutContextStack = $layoutContextStack;
    }

    public function getView(): BlockView
    {
        return $this->view;
    }

    /**
     * Renders the layout
     */
    public function render(): string
    {
        try {
            $this->layoutContextStack->push($this->context);

            $twigLayoutRenderer = $this->rendererRegistry->getRenderer($this->rendererName);
            foreach ($this->themes as $theme) {
                $twigLayoutRenderer->setBlockTheme($theme[0], $theme[1]);
            }
            $twigLayoutRenderer->setFormTheme($this->formThemes);

            return $twigLayoutRenderer->renderBlock($this->view);
        } finally {
            $this->layoutContextStack->pop();
        }
    }

    /**
     * Sets a renderer to be used to render this layout
     *
     * @param string|null $name The name of a layout renderer
     *
     * @return self
     */
    public function setRenderer(?string $name): self
    {
        $this->rendererName = $name;

        return $this;
    }

    /**
     * Sets the theme(s) to be used for rendering a block and its children
     *
     * @param string|string[] $themes  The theme(s). For example '@My/Layout/my_theme.html.twig'
     * @param string|null     $blockId The id of a block to assign the theme(s) to
     *
     * @return self
     */
    public function setBlockTheme(array|string $themes, ?string $blockId = null): self
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
     * @param string|string[] $themes  The theme(s). For example '@My/Layout/my_theme.html.twig'
     *
     * @return self
     */
    public function setFormTheme(array|string $themes): self
    {
        $themes = is_array($themes) ? $themes : [$themes];
        $this->formThemes = array_merge($this->formThemes, $themes);

        return $this;
    }

    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}
