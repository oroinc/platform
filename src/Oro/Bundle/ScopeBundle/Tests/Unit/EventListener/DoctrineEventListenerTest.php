<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Oro\Bundle\ScopeBundle\EventListener\DoctrineEventListener;
use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;

class DoctrineEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeEntityStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityStorage;

    /**
     * @var DoctrineEventListener
     */
    private $listener;

    protected function setUp()
    {
        $this->entityStorage = $this->getMockBuilder(ScopeEntityStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new DoctrineEventListener($this->entityStorage);
    }

    public function testPreFlush()
    {
        $this->entityStorage->expects($this->once())
            ->method('persistScheduledForInsert');
        $this->entityStorage->expects($this->once())
            ->method('clear');

        $this->listener->preFlush();
    }

    public function testOnClear()
    {
        $this->entityStorage->expects($this->once())
            ->method('clear');

        $this->listener->onClear();
    }
}
