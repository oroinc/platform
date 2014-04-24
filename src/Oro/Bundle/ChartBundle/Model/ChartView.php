<?php

namespace Oro\Bundle\ChartBundle\Model;

class ChartView implements ChartViewInterface
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Chart view variables
     *
     * @var array
     */
    protected $vars;

    /**
     * Chart template
     *
     * @var string
     */
    protected $template;

    /**
     * @param \Twig_Environment $twig
     * @param array $vars Chart view vars
     * @param string $template
     */
    public function __construct(\Twig_Environment $twig, array $vars, $template)
    {
        $this->twig = $twig;
        $this->vars = $vars;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->twig->render(
            $this->template,
            $this->vars
        );
    }
}
