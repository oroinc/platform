<?php

namespace Oro\Bundle\SearchBundle\Provider;

use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class SearchMappingProvider extends AbstractSearchMappingProvider
{
    const CACHE_KEY = 'oro_search.mapping_config';

    /**
     * {@inheritdoc}
     */
    public function getMappingConfig()
    {
        if (!$this->isCollected) {
            $this->isCollected = true;

            if ($this->cacheDriver->contains(static::CACHE_KEY)) {
                $this->mappingConfig = $this->cacheDriver->fetch(static::CACHE_KEY);
            } else {
                /**
                 *  dispatch oro_search.search_mapping_collect event
                 */
                $event = new SearchMappingCollectEvent($this->mappingConfig);
                $this->dispatcher->dispatch(SearchMappingCollectEvent::EVENT_NAME, $event);
                $this->mappingConfig = $event->getMappingConfig();
                $this->cacheDriver->save(static::CACHE_KEY, $this->mappingConfig);
            }
        }

        return $this->mappingConfig;
    }
}
