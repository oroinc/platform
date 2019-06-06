<?php

namespace Oro\Bundle\FormBundle\Form\Twig;

use Oro\Bundle\FormBundle\Form\Builder\TemplateRendererInterface;
use Twig\Environment;

/**
 * Renders a template
 */
class TwigTemplateRenderer implements TemplateRendererInterface
{
    /** @var Environment */
    protected $env;

    /** @var array */
    protected $context;

    /**
     * @param Environment $env
     * @param array       $context
     */
    public function __construct(Environment $env, $context)
    {
        $this->env     = $env;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function render($template)
    {
        $templateWrapper = $this->env->createTemplate($template);

        return $templateWrapper->render($this->context);
    }
}
