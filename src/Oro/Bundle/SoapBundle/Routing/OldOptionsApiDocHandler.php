<?php

namespace Oro\Bundle\SoapBundle\Routing;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

/**
 * As FOSRestBundle v1.7.1 generates a plural path for OPTIONS routes,
 * we need to add a single path to avoid BC break.
 * The single path is marked as deprecated.
 *
 * @deprecated since 1.8. Will be removed in 2.0
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
        $annotation->setDocumentation(
            $annotation->getDocumentation()
            . "\n\nDeprecated since v1.8. Will be removed in v2.0"
        );
    }
}
