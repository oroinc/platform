<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionEventTriggerHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionEventTrigger */
    protected $trigger;

    /** @var TransitionEventTriggerHelper */
    protected $helper;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->helper = new TransitionEventTriggerHelper($this->workflowManager);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test_workflow')->setRelatedEntity('stdClass');

        $this->trigger = new TransitionEventTrigger();
        $this->trigger->setWorkflowDefinition($workflowDefinition)->setTransitionName('test_transition');
    }

    /**
     * @dataProvider checkRequireDataProvider
     *
     * @param string $require
     * @param bool $expected
     */
    public function testIsRequirePass($require, $expected)
    {
        $entity1 = new \stdClass();
        $entity1->testField1 = 'test value 1';

        $entity2 = new \stdClass();
        $entity2->testField2 = 'test value 2';
        $entity2->mainEntity = $entity1;

        $this->trigger->setRequire($require)->setRelation('mainEntity');

        $this->assertEquals($expected, $this->helper->isRequirePass($this->trigger, $entity2));
    }

    /**
     * @return array
     */
    public function checkRequireDataProvider()
    {
        return [
            'for entity right' => [
                'require' => 'entity.testField2 == "test value 2"',
                'expected' => true,
            ],
            'for entity wrong' => [
                'require' => 'entity.testField2 == "test value 3"',
                'expected' => false,
            ],
            'for mainEntity right' => [
                'require' => 'mainEntity.testField1 == "test value 1"',
                'expected' => true,
            ],
            'for mainEntity wrong' => [
                'require' => 'mainEntity.testField1 == "test value 3"',
                'expected' => false,
            ],
            'for both right' => [
                'require' => '(mainEntity.testField1 == "test value 1") && (entity.testField2 == "test value 2")',
                'expected' => true,
            ],
            'for both wrong' => [
                'require' => '(mainEntity.testField1 == "test value 3") && (entity.testField2 == "test value 2")',
                'expected' => false,
            ],
        ];
    }

    public function testIsRequirePassWrongEntity()
    {
        $entity = new \stdClass();
        $entity->testField2 = 'test value';
        $entity->mainEntity = null;

        $this->trigger->setRequire('mainEntity.field')->setRelation('mainEntity');

        $this->assertFalse($this->helper->isRequirePass($this->trigger, $entity));
    }

    public function testGetMainEntityWrongEntity()
    {
        $entity = new \stdClass();
        $entity->testField2 = 'test value 2';
        $entity->mainEntity = null;

        $this->trigger
            ->setRequire('testField2 == "test value 2"')
            ->setRelation('mainEntity');

        $this->assertNull($this->helper->getMainEntity($this->trigger, $entity));
    }

    /**
     * @dataProvider buildContextValuesProvider
     *
     * @param array $expected
     * @param array $arguments
     */
    public function testBuildContextValues(array $expected, array $arguments)
    {
        $this->assertSame(
            $expected,
            call_user_func_array([$this->helper, 'buildContextValues'], $arguments)
        );
    }

    /**
     * @return array
     */
    public function buildContextValuesProvider()
    {
        $item = new WorkflowItem();
        $definition = new WorkflowDefinition();
        $triggerEntity = new \stdClass();
        $workflowEntity = new \stdClass();

        return [
            'emptyness' => [
                [
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_DEFINITION => null,
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_ITEM => null,
                    TransitionEventTriggerHelper::TRIGGER_ENTITY => null,
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_ENTITY => null
                ],
                [
                    null,
                    null,
                    null,
                    null
                ]
            ],
            'types' => [
                [
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_DEFINITION => $definition,
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_ITEM => $item,
                    TransitionEventTriggerHelper::TRIGGER_ENTITY => $triggerEntity,
                    TransitionEventTriggerHelper::TRIGGER_WORKFLOW_ENTITY => $workflowEntity
                ],
                [
                    $definition, $triggerEntity, $workflowEntity, $item
                ]
            ]
        ];
    }
}
