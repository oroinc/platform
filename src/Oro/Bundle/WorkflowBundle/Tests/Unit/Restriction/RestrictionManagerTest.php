<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Restriction\RestrictionManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class RestrictionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var WorkflowRestrictionRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder(WorkflowRestrictionRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->restrictionManager = new RestrictionManager(
            $this->workflowRegistry,
            $this->doctrineHelper
        );
    }

    public function testHasEntityClassRestrictionsWithoutActiveWorkflows()
    {
        $class = 'stdClass';
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('OroWorkflowBundle:WorkflowRestriction')
            ->willReturn($this->repository);

        $this->repository->expects($this->once())
            ->method('getClassRestrictions')
            ->with($class)
            ->willReturn([
                [
                    'id' => 5,
                    'workflowName' => 'test_workflow',
                    'relatedEntity' => 'DateTime'
                ]
            ]);

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('getName')->willReturn('test_workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('DateTime')
            ->willReturn([$workflow]);

        $this->assertTrue($this->restrictionManager->hasEntityClassRestrictions($class));
    }
}
