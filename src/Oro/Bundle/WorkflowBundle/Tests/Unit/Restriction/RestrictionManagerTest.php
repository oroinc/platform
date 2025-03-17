<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Restriction;

use Doctrine\Common\Collections\ArrayCollection;
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
    private WorkflowRegistry&MockObject $workflowRegistry;
    private DoctrineHelper&MockObject $doctrineHelper;
    private WorkflowRestrictionRepository&MockObject $repository;
    private RestrictionManager $restrictionManager;

    #[\Override]
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

    public function testHasEntityClassRestrictionsWithoutActiveWorkflows(): void
    {
        $class = 'stdClass';
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(WorkflowRestriction::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
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
        $workflow->expects(self::once())
            ->method('getName')
            ->willReturn('test_workflow');

        $this->workflowRegistry->expects(self::once())
            ->method('getActiveWorkflowsByEntityClass')
            ->with('DateTime')
            ->willReturn(new ArrayCollection([$workflow]));

        self::assertTrue($this->restrictionManager->hasEntityClassRestrictions($class));
    }
}
