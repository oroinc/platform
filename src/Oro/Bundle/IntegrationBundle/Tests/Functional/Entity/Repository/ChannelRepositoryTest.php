<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Status;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ChannelRepositoryTest extends WebTestCase
{
    /**
     * @var ChannelRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadStatusData',
                'Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadJobData'
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')->getRepository('OroIntegrationBundle:Channel');
    }

    public function testRepositoryIsRegistered()
    {
        $this->assertInstanceOf('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository', $this->repository);
    }

    public function testGetLastStatusForConnectorWorks()
    {
        $fooIntegration = $this->getReference('oro_integration:foo_integration');

        $this->assertSame(
            $this->getReference('oro_integration:foo_first_connector_second_status_completed'),
            $this->repository->getLastStatusForConnector($fooIntegration, 'first_connector', Status::STATUS_COMPLETED)
        );

        $this->assertSame(
            $this->getReference('oro_integration:foo_first_connector_third_status_failed'),
            $this->repository->getLastStatusForConnector($fooIntegration, 'first_connector', Status::STATUS_FAILED)
        );

        $barIntegration = $this->getReference('oro_integration:bar_integration');

        $this->assertSame(
            $this->getReference('oro_integration:bar_first_connector_first_status_completed'),
            $this->repository->getLastStatusForConnector($barIntegration, 'first_connector', Status::STATUS_COMPLETED)
        );
    }

    /**
     * @dataProvider getRunningSyncJobsCountDataProvider
     *
     * @param string        $command
     * @param int           $expectedCount
     * @param null|string   $integration
     */
    public function testGetRunningSyncJobsCount($command, $expectedCount, $integration = null)
    {
        $integration = $integration ? $this->getReference($integration)->getId() : null;
        $actual = $this->repository->getRunningSyncJobsCount($command, $integration);

        $this->assertEquals($expectedCount, $actual);
    }

    public function getRunningSyncJobsCountDataProvider()
    {
        return [
            [
                'command' => 'first_test_command',
                'expectedCount' => 2
            ],
            [
                'command' => 'second_test_command',
                'expectedCount' => 1
            ],
            [
                'command' => 'third_test_command',
                'expectedCount' => 2,
                'integration' => 'oro_integration:foo_integration',
            ]
        ];
    }

    /**
     * @dataProvider getGetFirstRunningSyncJobDataProvider
     *
     * @param string      $command
     * @param string      $expectedJob
     * @param string|null $integration
     */
    public function testGetFirstRunningSyncJob($command, $expectedJob, $integration = null)
    {
        $integration = $integration ? $this->getReference($integration)->getId() : null;

        $actual = $this->repository->getFirstRunningSyncJob($command, $integration);

        $this->assertEquals($this->getReference($expectedJob), $actual);
    }

    public function getGetFirstRunningSyncJobDataProvider()
    {
        return [
            [
                'command' => 'first_test_command',
                'expectedJob' => 'oro_integration:first_running_job'
            ],
            [
                'command' => 'second_test_command',
                'expectedJob' => 'oro_integration:third_running_job'
            ],
            [
                'command' => 'third_test_command',
                'expectedJob' => 'oro_integration:running_job_for_foo_integration',
                'integration' => 'oro_integration:foo_integration',
            ]
        ];
    }
}
