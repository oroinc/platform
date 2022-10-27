<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class PrepareResultItemEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var Item|\PHPUnit\Framework\MockObject\MockObject */
    private $resultItem;

    /** @var PrepareResultItemEvent */
    private $event;

    protected function setUp(): void
    {
        $this->resultItem = $this->createMock(Item::class);

        $this->event = new PrepareResultItemEvent($this->resultItem);
    }

    public function testGetResultItem()
    {
        $this->assertEquals($this->resultItem, $this->event->getResultItem());
    }

    public function testGetEntityObject()
    {
        $testObject = new \stdClass();

        $this->event = new PrepareResultItemEvent($this->resultItem, $testObject);
        $this->assertEquals($testObject, $this->event->getEntity());
    }
}
