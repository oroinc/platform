<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

class PrepareResultItemEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PrepareResultItemEvent
     */
    protected $event;

    /**
     * @var \Oro\Bundle\SearchBundle\Query\Result\Item
     */
    protected $resultItem;

    /**
     * Set Up test environment
     */
    protected function setUp()
    {
        $this->resultItem = $this->getMockBuilder('Oro\Bundle\SearchBundle\Query\Result\Item')
            ->disableOriginalConstructor()
            ->getMock();
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
