<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Tools;

use Oro\Bundle\WorkflowBundle\Model\Tools\StartedWorkflowsBag;

class StartedWorkflowsBagTest extends \PHPUnit\Framework\TestCase
{
    const WORKFLOW_NAME = 'test_flow';

    /** @var StartedWorkflowsBag */
    private $startedWorkflowsBag;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->startedWorkflowsBag = new StartedWorkflowsBag();
    }

    public function testGetWorkflowEntities()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity1);
        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity2);

        $this->assertSame(
            [$entity1, $entity2],
            $this->startedWorkflowsBag->getWorkflowEntities(self::WORKFLOW_NAME)
        );
    }

    /**
     * @dataProvider entityWorkflowProvider
     *
     * @param array $entityWorkflow
     * @param $workflow
     * @param bool $expected
     */
    public function testHasWorkflowEntities(array $entityWorkflow, $workflow, $expected)
    {
        foreach ($entityWorkflow as $item) {
            $this->startedWorkflowsBag->addWorkflowEntity($item['workflowName'], $item['entity']);
        }

        $this->assertSame(
            $this->startedWorkflowsBag->hasWorkflowEntity($workflow),
            $expected
        );
    }

    /**
     * @return \Generator
     */
    public function entityWorkflowProvider()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        yield 'test empty' => [
            'entityWorkflow' => [],

            'workflow' => 'flow1',
            'expected' => false
        ];

        yield 'test one workflow when not has' => [
            'entityWorkflow' => [
                ['workflowName' => 'flow1', 'entity' => $entity2],
                ['workflowName' => 'flow1', 'entity' => $entity2]
            ],
            'workflow' => 'flow2',
            'expected' => false
        ];

        yield 'test one workflow when has' => [
            'entityWorkflow' => [
                ['workflowName' => 'flow1', 'entity' => $entity1],
            ],
            'workflow' => 'flow1',
            'expected' => true
        ];

        yield 'test two workflows' => [
            'entityWorkflow' => [
                ['workflowName' => 'flow1', 'entity' => $entity1],
                ['workflowName' => 'flow2', 'entity' => $entity1],
            ],
            'workflow' => 'flow1',
            'expected' => true
        ];
    }

    public function testRemove()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity1);
        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity2);

        $this->assertCount(2, $this->startedWorkflowsBag->getWorkflowEntities(self::WORKFLOW_NAME));
        $this->startedWorkflowsBag->removeWorkflowEntity(self::WORKFLOW_NAME, $entity2);
        $this->assertCount(1, $this->startedWorkflowsBag->getWorkflowEntities(self::WORKFLOW_NAME));
    }

    public function testRemoveWorkflow()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity1);
        $this->startedWorkflowsBag->addWorkflowEntity(self::WORKFLOW_NAME, $entity2);

        $this->assertCount(2, $this->startedWorkflowsBag->getWorkflowEntities(self::WORKFLOW_NAME));
        $this->startedWorkflowsBag->removeWorkflow(self::WORKFLOW_NAME);
        $this->assertCount(0, $this->startedWorkflowsBag->getWorkflowEntities(self::WORKFLOW_NAME));
    }
}
