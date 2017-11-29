<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface as SearchIndexerInterface;

/**
 * Disables search index listener during loading of demo data
 * and triggers full reindexation of search index after demo data are loaded.
 */
class ReindexDemoDataFixturesListener
{
    /**
     * This listener is disabled to prevent a lot of reindex messages
     */
    const INDEX_LISTENER = 'oro_search.index_listener';

    /** @var OptionalListenerManager */
    private $listenerManager;

    /** @var SearchIndexerInterface */
    private $searchIndexer;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param SearchIndexerInterface  $searchIndexer
     */
    public function __construct(
        OptionalListenerManager $listenerManager,
        SearchIndexerInterface $searchIndexer
    ) {
        $this->listenerManager = $listenerManager;
        $this->searchIndexer = $searchIndexer;
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        $this->listenerManager->disableListener(self::INDEX_LISTENER);
    }

    /**
     * @param MigrationDataFixturesEvent $event
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        $this->listenerManager->enableListener(self::INDEX_LISTENER);

        $event->log('running full reindexation of search index');

        $this->searchIndexer->reindex();
    }
}
