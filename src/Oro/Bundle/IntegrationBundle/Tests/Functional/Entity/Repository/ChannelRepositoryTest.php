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
     * @dataProvider getExistingSyncJobsCountDataProvider
     *
     * @param string      $command
     * @param int         $expectedCount
     * @param null|string $integration
     */
    public function testGetExistingSyncJobsCount($command, $expectedCount, $integration = null)
    {
        $integration = $integration ? $this->getReference($integration)->getId() : null;
        $actual      = $this->repository->getExistingSyncJobsCount($command, $integration);

        self::assertEquals($expectedCount, $actual);
    }

    public function getExistingSyncJobsCountDataProvider()
    {
        return [
            [
                'command'       => 'first_test_command',
                'expectedCount' => 3
            ],
            [
                'command'       => 'second_test_command',
                'expectedCount' => 4
            ],
            [
                'command'       => 'third_test_command',
                'expectedCount' => 3,
                'integration'   => 'oro_integration:foo_integration',
            ]
        ];
    }
}
