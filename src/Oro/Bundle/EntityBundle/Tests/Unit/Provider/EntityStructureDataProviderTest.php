<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityStructureDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var EntityWithFieldsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityWithFieldsProvider;

    /** @var EntityStructureDataProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityWithFieldsProvider = $this->createMock(EntityWithFieldsProvider::class);
        $this->provider = new EntityStructureDataProvider($this->eventDispatcher, $this->entityWithFieldsProvider);
    }

    public function testGetData()
    {
        $event = new EntityStructureOptionsEvent();

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(EntityStructureDataProvider::EVENT_OPTIONS, $event)
            ->willReturn($event);

        $this->entityWithFieldsProvider
            ->expects($this->once())
            ->method('getFields')
            ->with(true, true, true, false, true, true)
            ->willReturn([]);

        self::assertEquals([], $this->provider->getData());
    }
}
