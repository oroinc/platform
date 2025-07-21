<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Oro\Bundle\WorkflowBundle\Cache\WorkflowAwareCacheClearer;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowAwareCacheClearerTest extends TestCase
{
    private WorkflowAwareCache&MockObject $workflowAwareCache;
    private WorkflowAwareCacheClearer $clearer;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->clearer = new WorkflowAwareCacheClearer($this->workflowAwareCache);
    }

    public function testClear(): void
    {
        $this->workflowAwareCache->expects($this->once())
            ->method('invalidateRelated');
        $this->workflowAwareCache->expects($this->once())
            ->method('invalidateActiveRelated');

        $this->clearer->clear('test');
    }
}
