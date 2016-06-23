<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor as BaseExtractor;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;

class CachingApiDocExtractor extends BaseExtractor implements
    RouteOptionsResolverAwareInterface,
    RestDocViewDetectorAwareInterface
{
    use ApiDocExtractorTrait;

    /** @var Route[]|null */
    protected $routes;

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        if (null === $this->routes) {
            $this->routes = $this->processRoutes(parent::getRoutes());
        }

        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        return parent::all($this->resolveView($view));
    }
}
