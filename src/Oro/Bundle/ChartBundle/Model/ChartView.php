<?php

namespace Oro\Bundle\ChartBundle\Model;

use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Twig\Environment;

/**
 * Represent chart view.
 * Purifies data to be HTML safe.
 */
class ChartView
{
    /**
     * @var Environment
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
     * @param Environment $twig
     * @param DataInterface $data
     * @param string $template
     * @param array $vars Chart view vars
     */
    public function __construct(Environment $twig, $template, DataInterface $data, array $vars)
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
        $context['data'] = $this->getSafeHtmlData($this->data->toArray());

        return $this->twig->render($this->template, $context);
    }

    private function getSafeHtmlData(array $data): array
    {
        foreach ($data as &$item) {
            if (\is_string($item)) {
                $item = htmlentities($item);
            } elseif (\is_array($item)) {
                $item = $this->getSafeHtmlData($item);
            }
        }

        return $data;
    }
}
