<?php

namespace Oro\Component\Layout;

use Symfony\Component\Templating\TemplateReferenceInterface;

/**
 * Renders the block and sets the theme(s) to be used for rendering a view and its children
 */
interface LayoutRendererInterface
{
    /**
     * Renders the block
     *
     * @param BlockView $view The view for which to render the block
     *
     * @return string
     */
    public function renderBlock(BlockView $view);

    /**
     * Sets the theme(s) to be used for rendering a view and its children
     *
     * @param BlockView $view The view to assign the theme(s) to
     * @param string|string[]|TemplateReferenceInterface[] $themes For example '@My/Layout/my_theme.html.twig'
     */
    public function setBlockTheme(BlockView $view, $themes);

    /**
     * Sets the theme(s) to be used for rendering forms
     *
     * @param string|string[]|TemplateReferenceInterface[] $themes For example '@My/Layout/my_theme.html.twig'
     */
    public function setFormTheme($themes);
}
