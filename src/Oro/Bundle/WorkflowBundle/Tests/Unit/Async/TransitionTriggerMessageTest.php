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
                TransitionTriggerMessage::MAIN_ENTITY => $mainEntityId
            ],
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId)->toArray()
        );
    }

    /**
     * @dataProvider createFromJsonExceptionProvider
     *
     * @param mixed $json
     * @param string $expectedMessage
     */
    public function testCreateFromJsonException($json, $expectedMessage)
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage($expectedMessage);

        TransitionTriggerMessage::createFromJson($json);
    }

    /**
     * @return array
     */
    public function createFromJsonExceptionProvider()
    {
        return [
            [
                'json' => null,
                'expectedMessage' => 'Accept only string argument but got: "NULL"'
            ],
            [
                'json' => new \stdClass(),
                'expectedMessage' => 'Accept only string argument but got: "stdClass"'
            ],
            [
                'json' => 'data',
                'expectedMessage' => 'The malformed json given'
            ],
            [
                'json' => '',
                'expectedMessage' => 'Given json should not be empty'
            ]
        ];
    }

    public function testCreateFromJson()
    {
        $triggerId = 42;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId),
            TransitionTriggerMessage::createFromJson($this->getJson($triggerId, $mainEntityId))
        );
        $this->assertEquals(
            $this->getTransitionTriggerMessage(null, null),
            TransitionTriggerMessage::createFromJson('{"test":"data"}')
        );
    }

    public function testCreate()
    {
        $triggerId = 42;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionTriggerMessage($triggerId, $mainEntityId),
            TransitionTriggerMessage::create($this->getEventTriggerMock($triggerId), $mainEntityId)
        );
    }

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->getTransitionTriggerMessage(null, null),
            [
                ['triggerId', 5, 0],
                ['mainEntityId', ['id' => 105]]
            ]
        );
    }

    /**
     * @param $id
     * @return \PHPUnit\Framework\MockObject\MockObject|BaseTransitionTrigger
     */
    protected function getEventTriggerMock($id)
    {
        $mock = $this->createMock(BaseTransitionTrigger::class);
        $mock->expects($this->any())->method('getId')->willReturn($id);

        return $mock;
    }

    /**
     * @param int $triggerId
     * @param mixed $mainEntityId
     * @return TransitionTriggerMessage
     */
    protected function getTransitionTriggerMessage($triggerId, $mainEntityId)
    {
        return $this->getEntity(
            TransitionTriggerMessage::class,
            ['triggerId' => $triggerId, 'mainEntityId' => $mainEntityId]
        );
    }

    /**
     * @param int $triggerId
     * @param mixed $mainEntityId
     * @return string
     */
    protected function getJson($triggerId, $mainEntityId)
    {
        return sprintf(
            '{"%s":%d,"%s":%s}',
            TransitionTriggerMessage::TRANSITION_TRIGGER,
            $triggerId,
            TransitionTriggerMessage::MAIN_ENTITY,
            json_encode($mainEntityId)
        );
    }
}
