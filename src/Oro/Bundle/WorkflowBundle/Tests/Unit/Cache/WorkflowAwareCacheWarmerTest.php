<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Oro\Bundle\WorkflowBundle\Cache\WorkflowAwareCacheWarmer;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowAwareCacheWarmerTest extends TestCase
{
    private WorkflowAwareCache&MockObject $workflowAwareCache;
    private WorkflowAwareCacheWarmer $warmer;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowAwareCache = $this->createMock(WorkflowAwareCache::class);

        $this->warmer = new WorkflowAwareCacheWarmer($this->workflowAwareCache);
    }

    public function testWarmUp(): void
    {
        $this->workflowAwareCache->expects($this->once())
            ->method('build');

        $this->warmer->warmUp('test');
    }

    public function testIsOptional(): void
    {
        $this->assertTrue($this->warmer->isOptional());
    }
}
