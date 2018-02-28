<?php

namespace Oro\Component\Routing\Matcher;

use Oro\Component\Routing\RouteCollectionUtil;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper as BaseDumper;
use Symfony\Component\Routing\RouteCollection;

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
