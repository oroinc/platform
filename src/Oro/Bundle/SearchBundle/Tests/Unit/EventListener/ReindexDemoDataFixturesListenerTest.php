<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface as SearchIndexerInterface;
use Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener;

class ReindexDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $searchIndexer;

    /** @var ReindexDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->searchIndexer = $this->createMock(SearchIndexerInterface::class);

        $this->listener = new ReindexDemoDataFixturesListener(
            $this->listenerManager,
            $this->searchIndexer
        );
    }

    public function testOnPreLoad()
    {
        $this->listenerManager->expects(self::once())
            ->method('disableListener')
            ->with(ReindexDemoDataFixturesListener::INDEX_LISTENER);

        $this->listener->onPreLoad($this->createMock(MigrationDataFixturesEvent::class));
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('log')
            ->with('running full reindexation of search index');
        $this->listenerManager->expects(self::once())
            ->method('enableListener')
            ->with(ReindexDemoDataFixturesListener::INDEX_LISTENER);
        $this->searchIndexer->expects(self::once())
            ->method('reindex');

        $this->listener->onPostLoad($event);
    }
}
