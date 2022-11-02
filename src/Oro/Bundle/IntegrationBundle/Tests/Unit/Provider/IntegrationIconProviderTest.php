<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface as IntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProvider;
use Oro\Component\DependencyInjection\ServiceLink;

class IntegrationIconProviderTest extends \PHPUnit\Framework\TestCase
{
    private const CHANNEL_TYPE = 'channel_type_1';
    private const ICON = 'bundles/icon-uri.png';

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $typesRegistry;

    /** @var IntegrationIconProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->typesRegistry = $this->createMock(TypesRegistry::class);

        $typesRegistryLink = $this->createMock(ServiceLink::class);
        $typesRegistryLink->expects(self::once())
            ->method('getService')
            ->willReturn($this->typesRegistry);

        $this->provider = new IntegrationIconProvider($typesRegistryLink);
    }

    /**
     * @dataProvider getIconDataProvider
     */
    public function testGetIcon(string $channelType, ?string $expectedIconUri)
    {
        $channelTypes = [
            self::CHANNEL_TYPE => $this->createIconAwareIntegration(self::ICON),
        ];
        $this->typesRegistry->expects(self::once())
            ->method('getRegisteredChannelTypes')
            ->willReturn($channelTypes);

        $actual = $this->provider->getIcon($this->createChannel($channelType));

        self::assertSame($expectedIconUri, $actual);
    }

    public function getIconDataProvider(): array
    {
        return [
            [
                'channelType' => self::CHANNEL_TYPE,
                'expectedIconUri' => self::ICON,
            ],
            [
                'channelType' => 'unknownChannelType',
                'expectedIconUri' => null
            ],
        ];
    }

    public function testGetIconIfNotIconAware()
    {
        $channelTypes = [
            self::CHANNEL_TYPE => $this->createMock(IntegrationInterface::class),
        ];
        $this->typesRegistry->expects(self::once())
            ->method('getRegisteredChannelTypes')
            ->willReturn($channelTypes);

        $actual = $this->provider->getIcon($this->createChannel(self::CHANNEL_TYPE));

        self::assertNull($actual);
    }

    private function createChannel(string $channelType): Channel
    {
        $channel = $this->createMock(Channel::class);
        $channel->expects(self::atLeastOnce())
            ->method('getType')
            ->willReturn($channelType);

        return $channel;
    }

    private function createIconAwareIntegration(string $iconUri): IconAwareIntegrationInterface
    {
        $integration = $this->createMock(IconAwareIntegrationInterface::class);
        $integration->expects(self::any())
            ->method('getIcon')
            ->willReturn($iconUri);

        return $integration;
    }
}
