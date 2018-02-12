<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\EventListener\AbstractDemoDataFixturesListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface as SearchIndexerInterface;

/**
 * Disables search index listener during loading of main and demo data
 */
class ReindexDemoDataFixturesListener extends AbstractDemoDataFixturesListener
{
    /** @var SearchIndexerInterface */
    protected $searchIndexer;

    /**
     * @param OptionalListenerManager $listenerManager
     * @param SearchIndexerInterface  $searchIndexer
     */
    public function __construct(OptionalListenerManager $listenerManager, SearchIndexerInterface $searchIndexer)
    {
        parent::__construct($listenerManager);

        $this->searchIndexer = $searchIndexer;
    }

    /**
     * {@inheritDoc}
     */
    public function onPreLoad(MigrationDataFixturesEvent $event)
    {
        $this->beforeDisableListeners($event);
        $this->listenerManager->disableListeners($this->listeners);
        $this->afterDisableListeners($event);
    }

    /**
     * {@inheritDoc}
     */
    public function onPostLoad(MigrationDataFixturesEvent $event)
    {
        $this->beforeEnableListeners($event);
        $this->listenerManager->enableListeners($this->listeners);
        $this->afterEnableListeners($event);
    }

    /**
     * {@inheritDoc}
     */
    protected function afterEnableListeners(MigrationDataFixturesEvent $event)
    {
    }
}
