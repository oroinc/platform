<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityUrlGenerator;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EntityUrlGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private EntityConfigManager $entityConfigManager;

    private EntityUrlGenerator $generator;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);

        $urlGenerator
            ->expects(self::any())
            ->method('generate')
            ->willReturnCallback(
                static function (string $route, array $parameters, int $referenceType) {
                    return implode(':', [$route, implode('|', $parameters), $referenceType]);
                }
            );

        $entityClassNameHelper
            ->expects(self::any())
            ->method('getUrlSafeClassName')
            ->willReturnCallback(static fn (string $value) => $value . '_safe');

        $this->generator = new EntityUrlGenerator(
            $this->entityConfigManager,
            $urlGenerator,
            $entityClassNameHelper
        );
    }

    public function testGenerateWhenNoMetadataNoFallback(): void
    {
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityMetadata')
            ->with(\stdClass::class)
            ->willReturn(null);

        self::assertEquals('', $this->generator->generate(\stdClass::class, 'sample_name', [], false));
    }

    public function testGenerateWhenMetadataAndNoRoute(): void
    {
        $metadata = new EntityMetadata(\stdClass::class);
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        self::assertEquals('', $this->generator->generate(\stdClass::class, 'sample_name', [], false));
    }

    public function testGenerateWhenMetadataAndRoute(): void
    {
        $metadata = new EntityMetadata(\stdClass::class);
        $metadata->routeUpdate = 'sample_update_route';
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        self::assertEquals(
            'sample_update_route:42:1',
            $this->generator->generate(\stdClass::class, 'update', ['id' => 42], false)
        );
    }

    public function testGenerateWhenNoRouteAndFallbackAndNotOwnerCustom(): void
    {
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityMetadata')
            ->willReturn(false);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('hasConfig')
            ->with(\stdClass::class)
            ->willReturn(true);

        $configProvider = new ConfigProvider($this->entityConfigManager, 'extend', new PropertyConfigBag([]));
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', \stdClass::class)
            ->willReturn(new Config($this->createMock(ConfigIdInterface::class), []));

        self::assertEquals(
            '',
            $this->generator->generate(\stdClass::class, 'update', ['id' => 42], true)
        );
    }

    public function testGenerateWhenNoRouteAndFallbackAndOwnerCustom(): void
    {
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityMetadata')
            ->willReturn(false);

        $this->entityConfigManager
            ->expects(self::once())
            ->method('hasConfig')
            ->with(\stdClass::class)
            ->willReturn(true);

        $configProvider = new ConfigProvider($this->entityConfigManager, 'extend', new PropertyConfigBag([]));
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);

        $config = ['owner' => ExtendScope::OWNER_CUSTOM];
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getEntityConfig')
            ->with('extend', \stdClass::class)
            ->willReturn(new Config($this->createMock(ConfigIdInterface::class), $config));

        self::assertEquals(
            'oro_entity_update:stdClass_safe|42:1',
            $this->generator->generate(\stdClass::class, 'update', ['id' => 42], true)
        );
    }
}
