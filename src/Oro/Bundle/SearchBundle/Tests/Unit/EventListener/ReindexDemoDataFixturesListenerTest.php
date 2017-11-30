<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface as SearchIndexerInterface;
use Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener;

class ReindexDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    const LISTENERS = [
        'test_listener_1',
        'test_listener_2',
    ];

    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var SearchIndexerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $searchIndexer;

    /** @var ReindexDemoDataFixturesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->searchIndexer = $this->createMock(SearchIndexerInterface::class);

        $this->listener = new ReindexDemoDataFixturesListener($this->listenerManager, $this->searchIndexer);
        $this->listener->disableListener(self::LISTENERS[0]);
        $this->listener->disableListener(self::LISTENERS[1]);
    }

    public function testOnPreLoad()
    {
        $this->listenerManager->expects(self::once())
            ->method('disableListeners')
            ->with(self::LISTENERS);

        $this->listener->onPreLoad($this->createMock(MigrationDataFixturesEvent::class));
    }

    public function testOnPostLoad()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);

        $event->expects(self::once())
            ->method('log')
            ->with('running full reindexation of search index');
        $this->listenerManager->expects(self::once())
            ->method('enableListeners')
            ->with(self::LISTENERS);
        $this->searchIndexer->expects(self::once())
            ->method('reindex');

        $this->listener->onPostLoad($event);
    }
}
