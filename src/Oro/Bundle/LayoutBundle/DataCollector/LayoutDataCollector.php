<?php

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\ContextItemInterface;

use Oro\Bundle\LayoutBundle\EventListener\LayoutListener;

class LayoutDataCollector extends DataCollector
{
    const NAME = 'layout';

    /**
     * @var array
     */
    protected $views = [];

    /**
     * @var array
     */
    protected $contextItems = [];

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['views'] = $this->views;
        $this->data['items'] = $this->contextItems;
    }

    /**
     * @param BlockView $rootView
     */
    public function collectViews(BlockView $rootView)
    {
        $this->buildFinalViewTree($rootView);
    }

    /**
     * @param LayoutContext $context
     */
    public function collectContextItems(LayoutContext $context)
    {
        $class = new \ReflectionClass(LayoutContext::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        foreach ($property->getValue($context) as $key => $value) {
            if (is_array($value)) {
                $this->contextItems[$key] = json_encode($value);
            } elseif ($value instanceof ContextItemInterface) {
                $this->contextItems[$key] = $value->toString();
            } else {
                $this->contextItems[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getViews()
    {
        return $this->data['views'];
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->data['items'];
    }

    /**
     * @param BlockView $view
     */
    protected function buildFinalViewTree(BlockView $view)
    {
        $this->views[$view->vars['id']] = [];

        $this->recursiveBuildFinalViewTree($view, $this->views[$view->vars['id']]);
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
        }
    }
}
