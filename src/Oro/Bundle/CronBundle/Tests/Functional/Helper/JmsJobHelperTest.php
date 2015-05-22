<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Helper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use JMS\JobQueueBundle\Entity\Job;

/**
 * @dbIsolation
 */
class JmsJobHelperTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\CronBundle\Tests\Functional\DataFixtures\LoadJmsJobs'
        ]);
    }

    /**
     * Test getPendingJobsCount
     *
     * @dataProvider jobStatusProvider
     */
    public function testGetPendingJobsCount($state, $expectedCount)
    {
        $helper = $this->getContainer()->get('oro_cron.jms_job_helper');

        $this->assertEquals($expectedCount, $helper->getPendingJobsCount($state));
    }

    /**
     * @return array
     */
    public function jobStatusProvider()
    {
        return [
            [Job::STATE_NEW,        5],
            [Job::STATE_TERMINATED, 0],
        ];
    }
}
