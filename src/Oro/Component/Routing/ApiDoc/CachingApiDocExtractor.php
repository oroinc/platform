<?php

namespace Oro\Component\Routing\ApiDoc;

use Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor as BaseExtractor;
use Oro\Component\Routing\RouteCollectionUtil;

class CachingApiDocExtractor extends BaseExtractor
{
    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return RouteCollectionUtil::filterHidden(parent::getRoutes());
    }
}
