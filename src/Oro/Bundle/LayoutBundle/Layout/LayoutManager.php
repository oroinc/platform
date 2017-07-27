<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\DataCollector\LayoutDataCollector;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutManager as BaseLayoutManager;

class LayoutManager extends BaseLayoutManager
{
    /** @var LayoutContextHolder */
    protected $contextHolder;

    /** @var LayoutDataCollector */
    protected $layoutDataCollector;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     * @param LayoutContextHolder $contextHolder
     * @param LayoutDataCollector $layoutDataCollector
     */
    public function __construct(
        LayoutFactoryBuilderInterface $layoutFactoryBuilder,
        LayoutContextHolder $contextHolder,
        LayoutDataCollector $layoutDataCollector
    ) {
        parent::__construct($layoutFactoryBuilder);
        $this->contextHolder = $contextHolder;
        $this->layoutDataCollector = $layoutDataCollector;
    }

    /**
     * @param ContextInterface $context
     * @param string|null      $rootId
     * @return Layout
     */
    public function getLayout(ContextInterface $context, $rootId = null)
    {
        $layoutBuilder = $this->getLayoutBuilder();

        // TODO discuss adding root automatically
        $layoutBuilder->add('root', null, 'root');

        $this->contextHolder->setContext($context);

        $layout = $layoutBuilder->getLayout($context, $rootId);
        $this->layoutDataCollector->setNotAppliedActions($layoutBuilder->getNotAppliedActions());

        return $layout;
    }

    /**
     * @param array         $parameters
     * @param null|string[] $vars
     * @return string
     */
    public function render(array $parameters, $vars = [])
    {
        $layoutContext = new LayoutContext($parameters, $vars);

        return $this->getLayout($layoutContext)->render();
    }
}
