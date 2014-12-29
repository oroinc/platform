<?php

namespace Oro\Bundle\FormBundle\Form\Twig;

use Oro\Bundle\FormBundle\Form\Builder\TemplateRendererInterface;

class TwigTemplateRenderer implements TemplateRendererInterface
{
    /** @var \Twig_Environment */
    protected $env;

    /** @var array */
    protected $context;

    /**
     * @param \Twig_Environment $env
     * @param array             $context
     */
    public function __construct(\Twig_Environment $env, $context)
    {
        $this->env     = $env;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function render($template)
    {
        return $this->env->render($template, $this->context);
    }
}
