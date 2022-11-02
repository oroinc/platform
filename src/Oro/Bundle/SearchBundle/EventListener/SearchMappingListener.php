<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * Clears the "mapping config" cache each time a new entity is created.
 */
class SearchMappingListener
{
    /**
     * @var SearchMappingProvider
     */
    private $searchMappingProvider;

    public function __construct(SearchMappingProvider $searchMappingProvider)
    {
        $this->searchMappingProvider = $searchMappingProvider;
    }

    public function invalidateCache(): void
    {
        $this->searchMappingProvider->warmUpCache();
    }
}
