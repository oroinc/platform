<?php

namespace Oro\Bundle\UIBundle\Layout\Extension\Theme;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

class WidgetPathProvider implements PathProviderInterface, ContextAwareInterface
{
    /** @var ContextInterface */
    protected $context;

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaths(array $existingPaths)
    {
        $widgetName = $this->context->getOr('widget_container');
        if (!$widgetName) {
            $widgetName = 'page';
        }

        $paths = [];
        foreach ($existingPaths as $path) {
            $paths[] = $path;
            $paths[] = implode(self::DELIMITER, [$path, $widgetName]);
        }

        return $paths;
    }
}
