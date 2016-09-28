<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async\Model;

use Oro\Bundle\WorkflowBundle\Async\Model\TransitionEventTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionEventTriggerMessageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    use EntityTestCaseTrait;

    public function testJsonSerialize()
    {
        $triggerId = 42;
        $workflowItemId = 142;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getJson($triggerId, $workflowItemId, $mainEntityId),
            JSON::encode($this->getTransitionEventTriggerMessage($triggerId, $workflowItemId, $mainEntityId))
        );
    }

    public function testToArray()
    {
        $triggerId = 42;
        $workflowItemId = 142;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            [
                TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER => $triggerId,
                TransitionEventTriggerMessage::WORKFLOW_ITEM => $workflowItemId,
                TransitionEventTriggerMessage::MAIN_ENTITY => $mainEntityId
            ],
            $this->getTransitionEventTriggerMessage($triggerId, $workflowItemId, $mainEntityId)->toArray()
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
        $this->setExpectedException('\InvalidArgumentException', $expectedMessage);

        TransitionEventTriggerMessage::createFromJson($json);
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
                'expectedMessage' => 'The malformed json given. Error 4 and message Syntax error'
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
        $workflowItemId = 142;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionEventTriggerMessage($triggerId, $workflowItemId, $mainEntityId),
            TransitionEventTriggerMessage::createFromJson($this->getJson($triggerId, $workflowItemId, $mainEntityId))
        );
        $this->assertEquals(
            $this->getTransitionEventTriggerMessage(null, null, null),
            TransitionEventTriggerMessage::createFromJson('{"test":"data"}')
        );
    }

    public function testCreate()
    {
        $triggerId = 42;
        $workflowItemId = 142;
        $mainEntityId = ['id' => 105];

        $this->assertEquals(
            $this->getTransitionEventTriggerMessage($triggerId, $workflowItemId, $mainEntityId),
            TransitionEventTriggerMessage::create(
                $this->getEventTriggerMock($triggerId),
                $this->getEntity(WorkflowItem::class, ['id' => $workflowItemId]),
                $mainEntityId
            )
        );
    }

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->getTransitionEventTriggerMessage(null, null, null),
            [
                ['triggerId', 5, 0],
                ['workflowItemId', 10, 0],
                ['mainEntityId', ['id' => 105]]
            ]
        );
    }

    /**
     * @param $id
     * @return \PHPUnit_Framework_MockObject_MockObject|EventTriggerInterface
     */
    protected function getEventTriggerMock($id)
    {
        $mock = $this->getMock(EventTriggerInterface::class);
        $mock->expects($this->any())->method('getId')->willReturn($id);

        return $mock;
    }

    /**
     * @param int $triggerId
     * @param int $workflowItemId
     * @param mixed $mainEntityId
     * @return TransitionEventTriggerMessage
     */
    protected function getTransitionEventTriggerMessage($triggerId, $workflowItemId, $mainEntityId)
    {
        return $this->getEntity(
            TransitionEventTriggerMessage::class,
            ['triggerId' => $triggerId, 'workflowItemId' => $workflowItemId, 'mainEntityId' => $mainEntityId]
        );
    }

    /**
     * @param int $triggerId
     * @param int $workflowItemId
     * @param mixed $mainEntityId
     * @return string
     */
    protected function getJson($triggerId, $workflowItemId, $mainEntityId)
    {
        return sprintf(
            '{"%s":%d,"%s":%s,"%s":%s}',
            TransitionEventTriggerMessage::TRANSITION_EVENT_TRIGGER,
            $triggerId,
            TransitionEventTriggerMessage::WORKFLOW_ITEM,
            $workflowItemId,
            TransitionEventTriggerMessage::MAIN_ENTITY,
            json_encode($mainEntityId)
        );
    }
}
