<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionRepositoryCacheInvalidationListener;

class WorkflowDefinitionRepositoryCacheInvalidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowDefinitionRepositoryCacheInvalidationListener */
    protected $listener;

    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new WorkflowDefinitionRepositoryCacheInvalidationListener($this->doctrineHelper);
    }

    public function testInvalidateCache()
    {
        $repo = $this->createMock(WorkflowDefinitionRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(WorkflowDefinition::class)
            ->willReturn($repo);

        $repo->expects($this->once())->method('invalidateCache');

        $this->listener->invalidateCache();
    }
}
