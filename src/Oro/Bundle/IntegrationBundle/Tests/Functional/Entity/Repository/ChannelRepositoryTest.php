<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadStatusData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ChannelRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStatusData::class]);
    }

    private function getRepository(): ChannelRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Channel::class);
    }

    public function testGetLastStatusForConnectorWorks()
    {
        $fooIntegration = $this->getReference('oro_integration:foo_integration');

        $this->assertSame(
            $this->getReference('oro_integration:foo_first_connector_second_status_completed'),
            $this->getRepository()->getLastStatusForConnector(
                $fooIntegration,
                'first_connector',
                Status::STATUS_COMPLETED
            )
        );

        $this->assertSame(
            $this->getReference('oro_integration:foo_first_connector_third_status_failed'),
            $this->getRepository()->getLastStatusForConnector(
                $fooIntegration,
                'first_connector',
                Status::STATUS_FAILED
            )
        );

        $barIntegration = $this->getReference('oro_integration:bar_integration');

        $this->assertSame(
            $this->getReference('oro_integration:bar_first_connector_first_status_completed'),
            $this->getRepository()->getLastStatusForConnector(
                $barIntegration,
                'first_connector',
                Status::STATUS_COMPLETED
            )
        );
    }

    public function testGetConfiguredChannelsForSync()
    {
        $channels = $this->getRepository()->getConfiguredChannelsForSync(null, false);
        $this->assertCount(3, $channels);

        $channelNames = array_map(static function (Channel $channel) {
            return $channel->getName();
        }, $channels);

        $this->assertEqualsCanonicalizing(
            ['Foo Integration', 'Bar Integration', 'Extended Bar Integration'],
            $channelNames
        );
    }

    public function testGetConfiguredChannelsForSyncByType()
    {
        $channels = $this->getRepository()->getConfiguredChannelsForSync('no_connectors');
        $this->assertCount(1, $channels);

        $channelNames = array_map(static function (Channel $channel) {
            return $channel->getName();
        }, $channels);

        $this->assertEqualsCanonicalizing(
            ['No connectors Integration'],
            $channelNames
        );
    }
}
