<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseExtractor;

use Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface;

class ApiDocExtractor extends BaseExtractor implements
    RouteOptionsResolverAwareInterface,
    RestDocViewDetectorAwareInterface
{
    use ApiDocExtractorTrait;

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return $this->processRoutes(parent::getRoutes());
    }

    /**
     * {@inheritdoc}
     */
    public function all($view = ApiDoc::DEFAULT_VIEW)
    {
        if ($this->docViewDetector) {
            $detectedView = $this->docViewDetector->getView();
            if ($detectedView) {
                $view = $detectedView;
            }
        }

        return parent::all($view);
    }
}
