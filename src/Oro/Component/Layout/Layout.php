<?php

namespace Oro\Component\Layout;

use Symfony\Component\Templating\TemplateNameParser;
use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Responsible for setting a renderer to be used to render this layout as well as blockThemes
 */
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

    /** @var TemplateNameParser */
    private static $templateNameParser;

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
     */
    public function render(): string
    {
        $renderer = $this->rendererRegistry->getRenderer($this->rendererName);
        foreach ($this->themes as $theme) {
            $renderer->setBlockTheme($theme[0], $this->prepareThemes($theme[1]));
        }
        $renderer->setFormTheme($this->prepareThemes($this->formThemes));

        return $renderer->renderBlock($this->view);
    }

    /**
     * @param string|string[] $themes
     * @return string|string[]|TemplateReferenceInterface|TemplateReferenceInterface[]
     */
    private function prepareThemes($themes)
    {
        if (\is_array($themes)) {
            foreach ($themes as &$theme) {
                if ($this->isAbsolutePath($theme)) {
                    $theme = $this->getTemplateNameParser()->parse($theme);
                }
            }
        } else {
            if ($this->isAbsolutePath($themes)) {
                $themes = $this->getTemplateNameParser()->parse($themes);
            }
        }

        return $themes;
    }

    private function isAbsolutePath(string $file): bool
    {
        return (bool) preg_match('#^(?:/|[a-zA-Z]:)#', $file);
    }

    private function getTemplateNameParser(): TemplateNameParser
    {
        if (!static::$templateNameParser) {
            static::$templateNameParser = new TemplateNameParser();
        }

        return static::$templateNameParser;
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
