<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class SearchEntityConfigListener
{
    /** @var SearchMappingProvider */
    protected $searchMappingProvider;


    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @param SearchMappingProvider $searchMappingProvider
     * @param MessageProducerInterface $producer
     */
    public function __construct(
        SearchMappingProvider $searchMappingProvider,
        MessageProducerInterface $producer
    ) {
        $this->searchMappingProvider = $searchMappingProvider;
        $this->producer = $producer;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $config = $event->getConfig('search');
        if (null === $config) {
            return;
        }

        $configManager = $event->getConfigManager();
        $changeSet     = $configManager->getConfigChangeSet($config);
        if (!isset($changeSet['searchable'])) {
            return;
        }

        /**
         * On any configuration changes related to search the search mapping cache should be cleaned.
         */
        $this->searchMappingProvider->clearMappingCache();

        $this->reindex($config->getId()->getClassName());
    }


    /**
     * @param string $entityClass
     */
    protected function reindex($entityClass)
    {
        $this->producer->send(Topics::REINDEX, [$entityClass]);
    }
}
