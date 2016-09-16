<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Helper;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Helper\TransitionTriggerEventHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class TransitionTriggerEventHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var TransitionTriggerEvent */
    protected $trigger;

    /** @var TransitionTriggerEventHelper */
    protected $helper;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->helper = new TransitionTriggerEventHelper($this->workflowManager);

        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setName('test_workflow')->setRelatedEntity('stdClass');

        $this->trigger = new TransitionTriggerEvent();
        $this->trigger->setWorkflowDefinition($workflowDefinition)->setTransitionName('test_transition');
    }

    /**
     * @dataProvider checkRequireDataProvider
     *
     * @param string $require
     * @param bool $expected
     */
    public function testCheckRequire($require, $expected)
    {
        $entity1 = new \stdClass();
        $entity1->testField1 = 'test value 1';

        $entity2 = new \stdClass();
        $entity2->testField2 = 'test value 2';
        $entity2->mainEntity = $entity1;

        $this->trigger->setRequire($require)->setRelation('mainEntity');

        $this->assertEquals($expected, $this->helper->checkRequire($this->trigger, $entity2));
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't get main entity using relation "mainEntity"
     */
    public function testCheckRequireWrongEntity()
    {
        $entity = new \stdClass();
        $entity->testField2 = 'test value 2';
        $entity->mainEntity = null;

        $this->trigger
            ->setRequire('testField2 == "test value 2"')
            ->setRelation('mainEntity');

        $this->helper->checkRequire($this->trigger, $entity);
    }
}
