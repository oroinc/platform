<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Bundle\LayoutBundle\Layout\Extension\Context\RouteContextConfigurator;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\SimpleContextValueComparisonCondition;

class ResourceFactory implements ResourceFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($path, $filename)
    {
        $resource = new FileResource($filename);

        $pathArray = explode(self::PATH_DELIMITER, $path);
        $routeName = reset($pathArray);

        // takes 2nd nesting level as route and adds additional condition
        if (count($pathArray) === 2 && !is_numeric($routeName)) {
            $resource->getConditions()->append(
                new SimpleContextValueComparisonCondition(RouteContextConfigurator::PARAM_ROUTE_NAME, '===', $routeName)
            );
        }

        return $resource;
    }
}
