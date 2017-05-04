<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadStatusData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
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
        $this->loadFixtures([LoadStatusData::class]);
        $this->repository = $this->getContainer()->get('doctrine')->getRepository(Channel::class);
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

    public function testFindByTypeReturnsCorrectData()
    {
        $fooIntegration = $this->getReference('oro_integration:foo_integration');

        static::assertSame([$fooIntegration], $this->repository->findByType('foo'));
    }

    public function testFindByTypeAndExclude()
    {
        $expectedIntegration = $this->getReference('oro_integration:bar_integration');
        $excludedIntegration = $this->getReference('oro_integration:extended_bar_integration');

        static::assertContains(
            $expectedIntegration,
            $this->repository->findByTypeAndExclude('bar', [$excludedIntegration])
        );
    }

    public function testFindByTypeAndExcludeById()
    {
        $expectedIntegration = $this->getReference('oro_integration:bar_integration');
        $excludedIntegration = $this->getReference('oro_integration:extended_bar_integration');

        static::assertContains(
            $expectedIntegration,
            $this->repository->findByTypeAndExclude('bar', [$excludedIntegration->getId()])
        );
    }
}
