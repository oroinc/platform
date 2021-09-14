<?php

namespace Oro\Component\Routing\Matcher;

use Oro\Component\Routing\RouteCollectionUtil;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper as BaseDumper;
use Symfony\Component\Routing\RouteCollection;

/**
 * The same as CompiledUrlMatcherDumper, but uses visible routes collection only
 */
class PhpMatcherDumper extends BaseDumper
{
    public function __construct(RouteCollection $routes)
    {
        parent::__construct(RouteCollectionUtil::cloneWithoutHidden($routes));
    }
}
