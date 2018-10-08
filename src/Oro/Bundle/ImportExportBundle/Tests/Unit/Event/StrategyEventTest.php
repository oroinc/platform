<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

class StrategyEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var StrategyInterface;
     */
    protected $strategy;

    /**
     * @var \stdClass
     */
    protected $entity;

    /**
     * @var StrategyEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->strategy = $this->createMock('Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface');
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $this->entity = new \stdClass();
        $this->entity->id = 1;

        $this->event = new StrategyEvent($this->strategy, $this->entity, $context);
    }

    public function testGetStrategy()
    {
        $this->assertEquals($this->strategy, $this->event->getStrategy());
    }

    public function testSetGetEntity()
    {
        $this->assertEquals($this->entity, $this->event->getEntity());

        $alteredEntity = new \stdClass();
        $alteredEntity->id = 2;

        $this->event->setEntity($alteredEntity);
        $this->assertEquals($alteredEntity, $this->event->getEntity());
    }
}
