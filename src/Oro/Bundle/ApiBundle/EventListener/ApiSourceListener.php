<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;

use Oro\Bundle\ApiBundle\ApiDoc\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

class ApiSourceListener
{
    /** @var ResourcesCache */
    protected $resourcesCache;

    /** @var ApiDocExtractor */
    protected $apiDocExtractor;

    /** @var string[] */
    protected $apiDocViews;

    /**
     * @param ResourcesCache  $resourcesCache
     * @param ApiDocExtractor $apiDocExtractor
     * @param string[]        $apiDocViews
     */
    public function __construct(
        ResourcesCache $resourcesCache,
        ApiDocExtractor $apiDocExtractor,
        array $apiDocViews
    ) {
        $this->resourcesCache = $resourcesCache;
        $this->apiDocExtractor = $apiDocExtractor;
        $this->apiDocViews = $apiDocViews;
    }

    public function clearCache()
    {
        // clear the cache for API resources
        $this->resourcesCache->clear();
        // clear the cache for API documentation
        if ($this->apiDocExtractor instanceof CachingApiDocExtractor) {
            foreach ($this->apiDocViews as $view) {
                $this->apiDocExtractor->clear($view);
            }
        }
    }
}
