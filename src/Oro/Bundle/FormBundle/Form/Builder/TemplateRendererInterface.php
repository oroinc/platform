<?php

namespace Oro\Bundle\FormBundle\Form\Builder;

/**
 * Defines the contract for rendering template expressions.
 *
 * Implementations of this interface are responsible for processing and rendering
 * template expressions, typically used in form building to dynamically generate
 * form field configurations or HTML output.
 */
interface TemplateRendererInterface
{
    /**
     * Renders a template.
     *
     * @param string $template The template expression
     *
     * @return string The rendered template
     */
    public function render($template);
}
