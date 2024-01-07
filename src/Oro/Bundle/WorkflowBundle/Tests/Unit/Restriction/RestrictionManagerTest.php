<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Restriction;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowRestrictionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestrictionManagerTest extends TestCase
{
    /** @var WorkflowRegistry|MockObject */
    private $workflowRegistry;

    /** @var DoctrineHelper|MockObject */
    private $doctrineHelper;

    /** @var RestrictionManager */
    private $restrictionManager;

    /** @var WorkflowRestrictionRepository|MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(WorkflowRestrictionRepository::class);

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
            ->with(WorkflowRestriction::class)
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

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getName')
            ->willReturn('test_workflow');

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('DateTime')
            ->willReturn([$workflow]);

        $this->assertTrue($this->restrictionManager->hasEntityClassRestrictions($class));
    }
}
