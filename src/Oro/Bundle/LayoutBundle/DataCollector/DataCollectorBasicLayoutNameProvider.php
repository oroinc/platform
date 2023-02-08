<?php

declare(strict_types=1);

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Oro\Component\Layout\ContextInterface;

/**
 * Provides the layout name for data collector taking into account context vars:
 * - action
 * - route_name
 * - widget_container
 */
class DataCollectorBasicLayoutNameProvider implements DataCollectorLayoutNameProviderInterface
{
    public function getNameByContext(ContextInterface $context): string
    {
        $name = 'Request';
        if ($context->getOr('widget_container')) {
            $name = 'Widget';
            $details = $context->getOr('widget_container');
        } elseif ($context->getOr('action')) {
            $name = 'Action';
            $details = $context->getOr('action');
        } elseif ($context->getOr('route_name')) {
            $name = 'Route';
            $details = $context->getOr('route_name');
        }

        if (!empty($details)) {
            $name .= ': ' . $details;
        }

        return $name;
    }
}
