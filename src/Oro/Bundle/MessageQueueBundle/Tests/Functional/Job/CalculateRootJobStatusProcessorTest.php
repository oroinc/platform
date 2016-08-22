<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\CalculateRootJobStatusProcessor;

class CalculateRootJobStatusProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_message_queue.job.calculate_root_job_status_processor');

        $this->assertInstanceOf(CalculateRootJobStatusProcessor::class, $instance);
    }
}
