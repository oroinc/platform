<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager as BaseLayoutManager;

/**
 * Main entry point for managing layouts.
 */
class LayoutManager extends BaseLayoutManager
{
    public function getLayout(ContextInterface $context, ?string $rootId = null): Layout
    {
        $layoutBuilder = $this->getLayoutBuilder();

        $layoutBuilder->add('root', null, 'root');

        return $layoutBuilder->getLayout($context, $rootId);
    }

    /**
     * @param array $parameters
     * @param string[] $vars
     *
     * @return string
     */
    public function render(array $parameters, array $vars = []): string
    {
        $layoutContext = new LayoutContext($parameters, $vars);

        return $this->getLayout($layoutContext)->render();
    }
}
