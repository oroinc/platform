<?php

namespace Oro\Bundle\UIBundle\Layout\Extension\Theme;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;

/**
 * Provides layout theme paths for widget containers.
 *
 * Extends the standard theme paths by adding widget-specific paths based on the current
 * widget container context. This allows layouts to have widget-specific theme overrides
 * while maintaining fallback to the default page paths.
 */
class WidgetPathProvider implements PathProviderInterface, ContextAwareInterface
{
    /** @var ContextInterface */
    protected $context;

    #[\Override]
    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
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
