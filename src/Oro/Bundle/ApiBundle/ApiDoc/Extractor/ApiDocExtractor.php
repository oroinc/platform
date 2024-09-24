<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetectorAwareInterface;
use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;

/**
 * The optimized and adapted version of Nelmio ApiDocExtractor.
 */
class ApiDocExtractor extends BaseExtractor implements
    RouteOptionsResolverAwareInterface,
    RestDocViewDetectorAwareInterface
{
    use ApiDocExtractorTrait;

    #[\Override]
    public function getRoutes()
    {
        return $this->processRoutes(parent::getRoutes());
    }

    #[\Override]
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        return parent::all($this->resolveView($view));
    }

    #[\Override]
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        return $this->doExtractAnnotations(
            $routes,
            $view,
            $this->container->getParameter('nelmio_api_doc.exclude_sections')
        );
    }
}
