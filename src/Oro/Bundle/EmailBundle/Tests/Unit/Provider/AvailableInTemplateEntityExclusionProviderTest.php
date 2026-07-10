<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\AvailableInTemplateEntityExclusionProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AvailableInTemplateEntityExclusionProviderTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;

    private AvailableInTemplateEntityExclusionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->provider = new AvailableInTemplateEntityExclusionProvider(
            $this->configProvider
        );
    }

    public function testIsIgnoredEntityReturnsTrueWhenAvailableInTemplateIsFalse(): void
    {
        $className = 'Some\Entity\Class';

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(false);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $result = $this->provider->isIgnoredEntity($className);

        self::assertTrue($result);
    }

    public function testIsIgnoredEntityReturnsFalseWhenAvailableInTemplateIsTrue(): void
    {
        $className = 'Some\Entity\Class';

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $result = $this->provider->isIgnoredEntity($className);

        self::assertFalse($result);
    }

    public function testIsIgnoredEntityReturnsTrueWhenAvailableInTemplateIsNull(): void
    {
        $className = 'Some\Entity\Class';

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(null);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $result = $this->provider->isIgnoredEntity($className);

        self::assertTrue($result);
    }

    public function testIsIgnoredEntityReturnsFalseWhenAvailableInTemplateIsInteger(): void
    {
        $className = 'Some\Entity\Class';

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(1);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $result = $this->provider->isIgnoredEntity($className);

        self::assertFalse($result);
    }

    public function testIsIgnoredEntityCallsConfigProviderWithCorrectClassName(): void
    {
        $className = 'Specific\Class\Name';

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects(self::once())
            ->method('get')
            ->with('available_in_template')
            ->willReturn(true);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $this->provider->isIgnoredEntity($className);
    }
}
