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

    public function testOnPreLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $this->listenerManager->expects(self::never())
            ->method('disableListener');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
        $this->listenerManager->expects(self::once())
            ->method('disableListener')
            ->with(ReindexDemoDataFixturesListener::INDEX_LISTENER);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(false);
        $event->expects(self::never())
            ->method('log');
        $this->listenerManager->expects(self::never())
            ->method('enableListener');
        $this->searchIndexer->expects(self::never())
            ->method('reindex');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('isDemoFixtures')
            ->willReturn(true);
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
