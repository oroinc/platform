<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

/**
 * The event listener that can be used to clear ApiDoc cache.
 */
class ApiSourceListener
{
    /** @var ResourcesCache */
    private $resourcesCache;

    /** @var ApiDocExtractor */
    private $apiDocExtractor;

    /** @var string[] */
    private $apiDocViews;

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
