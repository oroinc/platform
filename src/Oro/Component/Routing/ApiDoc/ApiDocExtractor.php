<?php

namespace Oro\Component\Routing\ApiDoc;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseExtractor;
use Oro\Component\Routing\RouteCollectionUtil;

class ApiDocExtractor extends BaseExtractor
{
    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return RouteCollectionUtil::filterHidden(parent::getRoutes());
    }
}
