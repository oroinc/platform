<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionRepositoryCacheInvalidationListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowDefinitionRepositoryCacheInvalidationListenerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private WorkflowDefinitionRepositoryCacheInvalidationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new WorkflowDefinitionRepositoryCacheInvalidationListener($this->doctrineHelper);
    }

    public function testInvalidateCache(): void
    {
        $repo = $this->createMock(WorkflowDefinitionRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($repo);

        $repo->expects($this->once())
            ->method('invalidateCache');

        $this->listener->invalidateCache();
    }
}
