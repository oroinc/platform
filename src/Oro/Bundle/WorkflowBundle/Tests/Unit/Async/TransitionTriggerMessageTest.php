<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionTriggerMessageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testToArray()
    {
        $triggerId = 42;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            [
                TransitionTriggerMessage::TRANSITION_TRIGGER => $triggerId,
                TransitionTriggerMessage::MAIN_ENTITY => $mainEntityId,
            ],
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId)->toArray()
        );
    }

    public function testCreateFromArray()
    {
        $triggerId = 42;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId),
            TransitionTriggerMessage::createFromArray($this->getArray($triggerId, $mainEntityId))
        );
        $this->assertEquals(
            $this->getTransitionTriggerMessage(null, null),
            TransitionTriggerMessage::createFromArray([])
        );
    }

    public function testCreate()
    {
        $triggerId = 42;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId),
            TransitionTriggerMessage::create($this->getEventTrigger($triggerId), $mainEntityId)
        );
    }

    private function getEventTrigger(int $id): BaseTransitionTrigger
    {
        $transitionTrigger = $this->createMock(BaseTransitionTrigger::class);
        $transitionTrigger->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $transitionTrigger;
    }

    private function getTransitionTriggerMessage(?int $triggerId, mixed $mainEntityId): TransitionTriggerMessage
    {
        return $this->getEntity(
            TransitionTriggerMessage::class,
            ['triggerId' => $triggerId, 'mainEntityId' => $mainEntityId]
        );
    }

    private function getArray(int $triggerId, mixed $mainEntityId): array
    {
        return [
            TransitionTriggerMessage::TRANSITION_TRIGGER => $triggerId,
            TransitionTriggerMessage::MAIN_ENTITY => $mainEntityId,
        ];
    }
}
