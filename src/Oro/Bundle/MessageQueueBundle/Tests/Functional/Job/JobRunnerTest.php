<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobRunner;

class JobRunnerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_message_queue.job.runner');

        $this->assertInstanceOf(JobRunner::class, $instance);
    }
}
