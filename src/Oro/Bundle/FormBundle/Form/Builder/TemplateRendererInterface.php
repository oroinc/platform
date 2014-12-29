<?php

namespace Oro\Bundle\FormBundle\Form\Builder;

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
