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
    const CHANNEL_TYPE = 'channel_type_1';
    const ICON = 'bundles/icon-uri.png';

    /**
     * @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $typesRegistry;

    /**
     * @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $typesRegistryLink;

    /**
     * @var IntegrationIconProvider
     */
    private $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->typesRegistry = $this->createMock(TypesRegistry::class);
        $this->typesRegistryLink = $this->createMock(ServiceLink::class);
        $this->typesRegistryLink
            ->expects(self::once())
            ->method('getService')
            ->willReturn($this->typesRegistry);
        $this->provider = new IntegrationIconProvider($this->typesRegistryLink);
    }

    /**
     * @dataProvider getIconDataProvider
     *
     * @param string $channelType
     * @param string $expectedIconUri
     */
    public function testGetIcon($channelType, $expectedIconUri)
    {
        $channelTypes = [
            self::CHANNEL_TYPE => $this->createIconAwareIntegration(self::ICON),
        ];
        $this->typesRegistry
            ->expects(static::once())
            ->method('getRegisteredChannelTypes')
            ->willReturn($channelTypes);

        $actual = $this->provider->getIcon($this->createChannel($channelType));

        static::assertSame($expectedIconUri, $actual);
    }

    /**
     * @return array
     */
    public function getIconDataProvider()
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
        $this->typesRegistry
            ->expects(static::once())
            ->method('getRegisteredChannelTypes')
            ->willReturn($channelTypes);

        $actual = $this->provider->getIcon($this->createChannel(self::CHANNEL_TYPE));

        static::assertNull($actual);
    }

    /**
     * @param string $channelType
     *
     * @return Channel|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createChannel($channelType)
    {
        $channel = $this->createMock(Channel::class);
        $channel
            ->expects(static::atLeastOnce())
            ->method('getType')
            ->willReturn($channelType);

        return $channel;
    }

    /**
     * @param string $iconUri
     *
     * @return IntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createIconAwareIntegration($iconUri)
    {
        $integration = $this->createMock(IconAwareIntegrationInterface::class);
        $integration
            ->method('getIcon')
            ->willReturn($iconUri);

        return $integration;
    }
}
