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
        /**
         * disabling the garbage collector gives a significant performance gain (about 2 times)
         * because a lot of config and metadata objects with short lifetime are used
         * this happens because we work with clones of these objects
         * @see Oro\Bundle\ApiBundle\Provider\ConfigProvider::getConfig
         * @see Oro\Bundle\ApiBundle\Provider\RelationConfigProvider::getRelationConfig
         * @see Oro\Bundle\ApiBundle\Provider\MetadataProvider::getMetadata
         */
        gc_disable();
        $result = parent::all($this->resolveView($view));
        gc_enable();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        return $this->doExtractAnnotations(
            $routes,
            $view,
            $this->container->getParameter('nelmio_api_doc.exclude_sections')
        );
    }
}
