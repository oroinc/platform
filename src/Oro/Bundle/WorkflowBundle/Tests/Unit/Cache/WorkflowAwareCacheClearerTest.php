<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Oro\Bundle\WorkflowBundle\Cache\WorkflowAwareCacheClearer;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;

class WorkflowAwareCacheClearerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowAwareCache|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowAwareCache;

    /** @var WorkflowAwareCacheClearer */
    private $clearer;

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
