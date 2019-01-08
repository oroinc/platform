<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

/**
 * Listener for oro.entity_config.pre_flush and oro.entity_config.post_flush events.
 * On pre_flush it decides which entities should be reindexed and reindexes them on post_flush.
 */
class SearchEntityConfigListener
{
    /** @var SearchMappingProvider */
    protected $searchMappingProvider;

    /** @var IndexerInterface */
    protected $searchIndexer;

    /** @var string[] */
    protected $classNames = [];

    /**
     * @param SearchMappingProvider $searchMappingProvider
     * @param IndexerInterface      $searchIndexer
     */
    public function __construct(
        SearchMappingProvider $searchMappingProvider,
        IndexerInterface $searchIndexer
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchIndexer = $searchIndexer;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        if ($this->isReindexRequired($event)) {
            $entityClass = $event->getClassName();
            if (!in_array($entityClass, $this->classNames, true)) {
                $this->classNames[] = $entityClass;
            }
        }
    }

    /**
     * @param PostFlushConfigEvent $event
     */
    public function postFlush(PostFlushConfigEvent $event)
    {
        if ($this->classNames) {
            $this->searchMappingProvider->clearCache();
            $this->searchIndexer->reindex($this->classNames);

            $this->classNames = [];
        }
    }

    /**
     * @param PreFlushConfigEvent $event
     *
     * @return bool
     */
    protected function isReindexRequired(PreFlushConfigEvent $event)
    {
        $searchConfig = $event->getConfig('search');
        if (null === $searchConfig) {
            return false;
        }

        $configManager = $event->getConfigManager();
        $searchChangeSet = $configManager->getConfigChangeSet($searchConfig);
        if (!isset($searchChangeSet['searchable'])) {
            return false;
        }

        $extendConfig = $event->getConfig('extend');

        $searchMapping = $this->searchMappingProvider->getEntityConfig($event->getClassName());

        return
            null !== $extendConfig
            && $extendConfig->is('state', ExtendScope::STATE_ACTIVE)
            && !empty($searchMapping);
    }
}
