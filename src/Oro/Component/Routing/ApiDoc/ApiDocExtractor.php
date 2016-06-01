<?php

namespace Oro\Component\Routing\ApiDoc;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseExtractor;

use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;

class ApiDocExtractor extends BaseExtractor implements RouteOptionsResolverAwareInterface
{
    use ApiDocExtractorTrait;

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return $this->processRoutes(parent::getRoutes());
    }
}
