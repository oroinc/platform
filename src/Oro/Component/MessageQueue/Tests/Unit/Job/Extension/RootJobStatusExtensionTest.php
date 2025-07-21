<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Extension;

use Oro\Component\MessageQueue\Job\Extension\RootJobStatusExtension;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RootJobStatusExtensionTest extends TestCase
{
    private RootJobStatusCalculator&MockObject $rootJobStatusCalculator;
    private RootJobStatusExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->rootJobStatusCalculator = $this->createMock(RootJobStatusCalculator::class);
        $this->extension = new RootJobStatusExtension($this->rootJobStatusCalculator);
    }

    public function testOnPreRunUnique(): void
    {
        $job = new Job();
        $this->rootJobStatusCalculator->expects($this->once())
            ->method('calculate')
            ->with($job);

        $this->extension->onPreRunUnique($job);
    }

    public function testOnPostRunUnique(): void
    {
        $job = new Job();
        $this->rootJobStatusCalculator->expects($this->once())
            ->method('calculate')
            ->with($job);

        $this->extension->onPostRunUnique($job, '');
    }

    public function testOnPostRunDelayed(): void
    {
        $job = new Job();
        $this->rootJobStatusCalculator->expects($this->once())
            ->method('calculate')
            ->with($job);

        $this->extension->onPostRunUnique($job, '');
    }

    public function testOnError(): void
    {
        $job = new Job();
        $this->rootJobStatusCalculator->expects($this->once())
            ->method('calculate')
            ->with($job);

        $this->extension->onError($job);
    }
}
