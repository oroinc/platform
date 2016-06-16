<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Restriction\RestrictionManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class RestrictionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var RestrictionManager */
    protected $restrictionManager;

    /** @var WorkflowRestrictionRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder(WorkflowRestrictionRepository::class)->disableOriginalConstructor()->getMock();

        $this->restrictionManager = new RestrictionManager(
            $this->workflowManager,
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

        /** \Oro\Bundle\WorkflowBundle\Model\Workflow|\PHPUnit_Framework_MockObject_MockObject */
        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->once())->method('getName')->willReturn('test_workflow');

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflowsByEntityClass')
            ->with('DateTime')
            ->willReturn([$workflow]);

        $this->assertTrue($this->restrictionManager->hasEntityClassRestrictions($class));
        
    }
}
