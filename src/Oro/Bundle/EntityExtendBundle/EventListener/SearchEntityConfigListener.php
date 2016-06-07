<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Entity\UpdateEntity;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;

class SearchEntityConfigListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var SearchMappingProvider */
    protected $searchMappingProvider;

    /**
     * @var IndexerInterface
     */
    protected $searchIndexer;

    /**
     * @var string
     */
    protected $classNames = [];

    /**
     * @param ManagerRegistry       $registry
     * @param SearchMappingProvider $searchMappingProvider
     * @param IndexerInterface      $searchIndexer
     */
    public function __construct(
        ManagerRegistry $registry,
        SearchMappingProvider $searchMappingProvider,
        IndexerInterface $searchIndexer
    ) {
        $this->registry              = $registry;
        $this->searchMappingProvider = $searchMappingProvider;
        $this->searchIndexer = $searchIndexer;
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

        $this->classNames[] = $config->getId()->getClassName();
    }

    /**
     * @param PostFlushConfigEvent $event
     */
    public function postFlush(PostFlushConfigEvent $event)
    {
        if ($this->classNames) {
            $this->searchIndexer->reindex($this->classNames);

            $this->classNames = [];
        }
    }
}
