<?php

namespace Oro\Bundle\UIBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

use Oro\Component\Layout\Extension\Theme\Loader\PathProviderInterface;

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
            return $existingPaths;
        }

        $paths = [];
        foreach ($existingPaths as $path) {
            $paths[] = $path;
            if (false !== strpos($path, self::DELIMITER)) {
                $paths[] = implode(self::DELIMITER, [$path, $widgetName]);
            }
        }

        return $paths;
    }
}
