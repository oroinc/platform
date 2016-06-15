<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor as BaseExtractor;

use Symfony\Component\Routing\Route;

use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;

class CachingApiDocExtractor extends BaseExtractor implements RouteOptionsResolverAwareInterface
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
}
