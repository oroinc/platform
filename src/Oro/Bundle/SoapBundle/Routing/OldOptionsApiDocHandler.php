<?php

namespace Oro\Bundle\SoapBundle\Routing;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Symfony\Component\Routing\Route;

/**
 * Marks routes with "old_options" option as deprecated.
 * @see \Oro\Bundle\SoapBundle\Routing\OldOptionsRouteOptionsResolver
 */
class OldOptionsApiDocHandler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if (!$route->getOption('old_options')) {
            return;
        }

        $annotation->setDeprecated(true);
        $annotation->setDocumentation($annotation->getDocumentation() . "\n\nDeprecated since v1.8");
    }
}
