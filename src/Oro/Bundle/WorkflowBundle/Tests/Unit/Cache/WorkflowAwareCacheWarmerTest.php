<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Oro\Bundle\WorkflowBundle\Cache\WorkflowAwareCacheWarmer;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowAwareCache;

class WorkflowAwareCacheWarmerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowAwareCache|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowAwareCache;

    /** @var WorkflowAwareCacheWarmer */
    private $warmer;

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
