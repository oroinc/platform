<?php

namespace Oro\Component\Routing\Matcher;

use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper as BaseDumper;
use Symfony\Component\Routing\RouteCollection;
use Oro\Component\Routing\RouteCollectionUtil;

class PhpMatcherDumper extends BaseDumper
{
    /**
     * @param RouteCollection $routes
     */
    public function __construct(RouteCollection $routes)
    {
        parent::__construct(RouteCollectionUtil::cloneWithoutHidden($routes));
    }
}
