<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;

    private ObjectRepository|MockObject $repository;

    private ThemeConfigurationProvider $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getRepository')
            ->with(ThemeConfiguration::class)
            ->willReturn($this->repository);

        $this->provider = new ThemeConfigurationProvider($this->configManager, $registry);
    }

    public function testGetThemeConfigurationOptionThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->repository->expects(self::never())
            ->method('find')
            ->withAnyParameters();

        $actualOptionValue = $this->provider->getThemeConfigurationOption('some_option');

        self::assertNull($actualOptionValue);
    }

    public function testGetThemeConfigurationOptionThemeConfigurationNotExisted(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('find')
            ->with($themeConfigurationId)
            ->willReturn(null);

        $actualOptionValue = $this->provider->getThemeConfigurationOption('some_option');

        self::assertNull($actualOptionValue);
    }

    public function testGetThemeConfigurationOptionEmptyThemeConfiguration(): void
    {
        $themeConfigurationId = 1;
        $themeConfiguration = new ThemeConfiguration();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('find')
            ->with($themeConfigurationId)
            ->willReturn($themeConfiguration);

        $actualOptionValue = $this->provider->getThemeConfigurationOption('some_option');

        self::assertNull($actualOptionValue);
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeConfigurationOption(?int $scopeIdentifier, string $option, $expectedOptionValue): void
    {
        $themeConfigurationId = 1;
        $themeConfiguration = (new ThemeConfiguration())
            ->setConfiguration([
                'null' => null,
                'string' => 'some_option_value',
                'int' => 123,
                'float' => 123.321,
                'bool' => false,
                'array' => ['foo' => 'bar'],
                'object' => new \stdClass(),
            ]);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('find')
            ->with($themeConfigurationId)
            ->willReturn($themeConfiguration);

        $actualOptionValue = $this->provider->getThemeConfigurationOption($option, $scopeIdentifier);

        self::assertEquals($expectedOptionValue, $actualOptionValue);
    }

    public function getThemeConfigurationOptionDataProvider(): array
    {
        $noScopeIdentifier = null;
        $idScopeIdentifier = 123;

        return [
            // no scope
            [$noScopeIdentifier, 'not_existed_option', null],
            [$noScopeIdentifier, 'null', null],
            [$noScopeIdentifier, 'string', 'some_option_value'],
            [$noScopeIdentifier, 'int', 123],
            [$noScopeIdentifier, 'float', 123.321],
            [$noScopeIdentifier, 'bool', false],
            [$noScopeIdentifier, 'array', ['foo' => 'bar']],
            [$noScopeIdentifier, 'object', new \stdClass()],
            // scope identifier as id
            [$idScopeIdentifier, 'not_existed_option', null],
            [$idScopeIdentifier, 'null', null],
            [$idScopeIdentifier, 'string', 'some_option_value'],
            [$idScopeIdentifier, 'int', 123],
            [$idScopeIdentifier, 'float', 123.321],
            [$idScopeIdentifier, 'bool', false],
            [$idScopeIdentifier, 'array', ['foo' => 'bar']],
            [$idScopeIdentifier, 'object', new \stdClass()],
        ];
    }

    /**
     * @dataProvider getHasThemeConfigurationOptionDataProvider
     */
    public function testHasThemeConfigurationOption(?int $scopeIdentifier, string $option, $expectedResult): void
    {
        $themeConfigurationId = 1;
        $themeConfiguration = (new ThemeConfiguration())
            ->setConfiguration([
                'null' => null,
                'string' => 'some_option_value',
            ]);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('find')
            ->with($themeConfigurationId)
            ->willReturn($themeConfiguration);

        $isOptionExists = $this->provider->hasThemeConfigurationOption($option, $scopeIdentifier);

        self::assertEquals($expectedResult, $isOptionExists);
    }

    public function getHasThemeConfigurationOptionDataProvider(): array
    {
        $noScopeIdentifier = null;
        $idScopeIdentifier = 123;

        return [
            // no scope
            [$noScopeIdentifier, 'not_existed_option', false],
            [$noScopeIdentifier, 'null', true],
            [$noScopeIdentifier, 'string', true],
            // scope identifier as id
            [$idScopeIdentifier, 'not_existed_option', false],
            [$idScopeIdentifier, 'null', true],
            [$idScopeIdentifier, 'string', true],
        ];
    }
}
