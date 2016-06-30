<?php

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;

class LayoutDataCollector extends DataCollector
{
    const NAME = 'layout';

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param $layout
     */
    public function setLayout(Layout $layout)
    {
        $this->layout = $layout;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['views'] = [];

        if ($this->layout) {
            $this->buildFinalViewTree($this->layout->getView());
        }
    }

    /**
     * @return array
     */
    public function getTree()
    {
        $data = fopen('php://memory', 'r+b');

        $dumper = new HtmlDumper($data, 'UTF-8');
        $dumper->setStyles([
            'compact' => 'display: inline;',
            'note' => 'display: none;',
            'ref' => 'display: none;'
        ]);

        $cloner = new VarCloner();
        $dumper->dump($cloner->cloneVar($this->data['views']));

        rewind($data);

        return [
            ['data' => stream_get_contents($data)]
        ];
    }

    /**
     * @param BlockView $view
     */
    protected function buildFinalViewTree(BlockView $view)
    {
        $this->data['views'][$view->vars['id']] = [];

        $this->recursiveBuildFinalViewTree($view, $this->data['views'][$view->vars['id']]);
    }

    /**
     * @param BlockView $view
     * @param array $output
     */
    protected function recursiveBuildFinalViewTree(BlockView $view, array &$output = [])
    {
        if ($view->children) {
            foreach ($view->children as $childView) {
                $output[$childView->vars['id']] = [];
                $this->recursiveBuildFinalViewTree($childView, $output[$childView->vars['id']]);
            }
        } else {
            $output = "~";
        }
    }
}
