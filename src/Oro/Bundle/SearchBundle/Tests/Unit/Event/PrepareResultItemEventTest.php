<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrepareResultItemEventTest extends TestCase
{
    private Item&MockObject $resultItem;
    private PrepareResultItemEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->resultItem = $this->createMock(Item::class);

        $this->event = new PrepareResultItemEvent($this->resultItem);
    }

    public function testGetResultItem(): void
    {
        $this->assertEquals($this->resultItem, $this->event->getResultItem());
    }

    public function testGetEntityObject(): void
    {
        $testObject = new \stdClass();

        $this->event = new PrepareResultItemEvent($this->resultItem, $testObject);
        $this->assertEquals($testObject, $this->event->getEntity());
    }
}
