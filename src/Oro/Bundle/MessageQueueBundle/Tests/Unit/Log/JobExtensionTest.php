<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Job;

use Oro\Component\MessageQueue\Job\Job;

use Oro\Bundle\MessageQueueBundle\Log\ConsumerState;
use Oro\Bundle\MessageQueueBundle\Log\JobExtension;

class JobExtensionTest extends \PHPUnit_Framework_TestCase
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
}
