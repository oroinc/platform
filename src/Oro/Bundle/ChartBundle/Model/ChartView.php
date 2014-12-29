<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

class ChartView
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * Chart template
     *
     * @var string
     */
    protected $template;

    /**
     * Chart view data
     *
     * @var DataInterface
     */
    protected $data;

    /**
     * Chart view variables
     *
     * @var array
     */
    protected $vars;

    /**
     * @param \Twig_Environment $twig
     * @param DataInterface $data
     * @param string $template
     * @param array $vars Chart view vars
     */
    public function __construct(\Twig_Environment $twig, $template, DataInterface $data, array $vars)
    {
        $this->twig = $twig;
        $this->template = $template;
        $this->data = $data;
        $this->vars = $vars;
    }

    /**
     * Render chart
     *
     * @return string
     */
    public function render()
    {
        $context = $this->vars;
        $context['data'] = $this->data->toArray();

        return $this->twig->render($this->template, $context);
    }
}
