<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Job\Extension;

use Oro\Component\MessageQueue\Job\Extension\JobExtension;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Log\ConsumerState;

class JobExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPreRunUnique()
    {
        $job = new Job();

        $consumerState = new ConsumerState();

        $extension = new JobExtension($consumerState);
        $extension->onPreRunUnique($job);

        $this->assertSame($job, $consumerState->getJob());
    }

    public function testOnPostRunUnique()
    {
        $job = new Job();

        $consumerState = new ConsumerState();
        $consumerState->setJob(new Job());

        $extension = new JobExtension($consumerState);
        $extension->onPostRunUnique($job, true);

        $this->assertNull($consumerState->getJob());
    }

    public function testOnPreRunDelayed()
    {
        $job = new Job();

        $consumerState = new ConsumerState();

        $extension = new JobExtension($consumerState);
        $extension->onPreRunDelayed($job);

        $this->assertSame($job, $consumerState->getJob());
    }

    public function testOnPostRunDelayed()
    {
        $job = new Job();

        $consumerState = new ConsumerState();
        $consumerState->setJob(new Job());

        $extension = new JobExtension($consumerState);
        $extension->onPostRunDelayed($job, true);

        $this->assertNull($consumerState->getJob());
    }

    public function testOnCancel()
    {
        $job = new Job();

        $consumerState = new ConsumerState();
        $consumerState->setJob(new Job());

        $extension = new JobExtension($consumerState);
        $extension->onCancel($job);

        $this->assertNull($consumerState->getJob());
    }
}
