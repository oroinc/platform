<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use PHPUnit\Framework\TestCase;

class StrategyEventTest extends TestCase
{
    private StrategyInterface $strategy;
    private \stdClass $entity;
    private StrategyEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->strategy = $this->createMock(StrategyInterface::class);
        $context = $this->createMock(ContextInterface::class);

        $this->entity = new \stdClass();
        $this->entity->id = 1;

        $this->event = new StrategyEvent($this->strategy, $this->entity, $context);
    }

    public function testGetStrategy(): void
    {
        $this->assertEquals($this->strategy, $this->event->getStrategy());
    }

    public function testSetGetEntity(): void
    {
        $this->assertEquals($this->entity, $this->event->getEntity());

        $alteredEntity = new \stdClass();
        $alteredEntity->id = 2;

        $this->event->setEntity($alteredEntity);
        $this->assertEquals($alteredEntity, $this->event->getEntity());
    }
}
