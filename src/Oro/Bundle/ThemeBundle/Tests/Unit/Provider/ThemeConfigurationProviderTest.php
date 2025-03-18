<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\Repository\ThemeConfigurationRepository;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class ThemeConfigurationProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ThemeDefinitionBagInterface&MockObject $configurationProvider;
    private ThemeConfigurationRepository&MockObject $repository;

    private ThemeConfigurationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configurationProvider = $this->createMock(ThemeDefinitionBagInterface::class);
        $this->repository = $this->createMock(ThemeConfigurationRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ThemeConfiguration::class)
            ->willReturn($this->repository);

        $this->provider = new ThemeConfigurationProvider(
            $this->configManager,
            $doctrine,
            $this->configurationProvider
        );
    }

    public function testGetThemeConfigurationOptionsWhenThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->repository->expects(self::never())
            ->method('getFieldValue');

        self::assertSame([], $this->provider->getThemeConfigurationOptions());
    }

    public function testGetThemeConfigurationOptionsWhenThemeConfigurationNotExist(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturnOnConsecutiveCalls([], null);

        self::assertSame([], $this->provider->getThemeConfigurationOptions());
    }

    /**
     * @dataProvider getThemeConfigurationOptionsDataProvider
     */
    public function testGetThemeConfigurationOptions(?int $scopeIdentifier, array $expectedOptions): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn($expectedOptions, 'test_theme');

        self::assertEquals(
            $expectedOptions,
            $this->provider->getThemeConfigurationOptions($scopeIdentifier)
        );
        // test memory cache
        self::assertEquals(
            $expectedOptions,
            $this->provider->getThemeConfigurationOptions($scopeIdentifier)
        );
    }

    public function getThemeConfigurationOptionsDataProvider(): array
    {
        $noScopeIdentifier = null;
        $idScopeIdentifier = 123;

        return [
            // no scope
            [$noScopeIdentifier, []],
            [$noScopeIdentifier, ['test_option' => 'test_value']],
            // scope identifier as id
            [$idScopeIdentifier, []],
            [$idScopeIdentifier, ['test_option' => 'test_value']],
        ];
    }

    public function testGetThemeConfigurationOptionsWithDefaultOptions(): void
    {
        $themeConfigurationId = 1;
        $expectedOptions = [
            'test_option' => 'test_value',
            LayoutThemeConfiguration::buildOptionKey('header', 'show_title') => true,
            LayoutThemeConfiguration::buildOptionKey('header', 'language') => 'en',
            LayoutThemeConfiguration::buildOptionKey('main', 'show_datagrid') => false,
        ];

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                null
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn(['test_option' => 'test_value'], 'test_theme');

        $this->configurationProvider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('test_theme')
            ->willReturn($this->getThemeDefinition());

        self::assertEquals(
            $expectedOptions,
            $this->provider->getThemeConfigurationOptions()
        );
        // test memory cache
        self::assertEquals(
            $expectedOptions,
            $this->provider->getThemeConfigurationOptions()
        );
    }

    public function testGetThemeConfigurationOptionWhenThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->repository->expects(self::never())
            ->method('getFieldValue');

        self::assertNull($this->provider->getThemeConfigurationOption('some_option'));
    }

    public function testGetThemeConfigurationOptionWhenThemeConfigurationNotExist(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn([], 'test_theme');

        self::assertNull($this->provider->getThemeConfigurationOption('some_option'));
    }

    public function testGetThemeConfigurationOptionForEmptyThemeConfiguration(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn([], 'test_theme');

        self::assertNull($this->provider->getThemeConfigurationOption('some_option'));
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeConfigurationOption(
        ?int $scopeIdentifier,
        string $option,
        mixed $expectedOptionValue
    ): void {
        $themeConfigurationId = 1;
        $options = [
            'null' => null,
            'string' => 'some_option_value',
            'int' => 123,
            'float' => 123.321,
            'bool' => false,
            'array' => ['foo' => 'bar'],
            'object' => new \stdClass(),
        ];

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn($options, 'test_theme');

        self::assertEquals(
            $expectedOptionValue,
            $this->provider->getThemeConfigurationOption($option, $scopeIdentifier)
        );
        // test memory cache
        self::assertEquals(
            $expectedOptionValue,
            $this->provider->getThemeConfigurationOption($option, $scopeIdentifier)
        );
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
        $options = [
            'null' => null,
            'string' => 'some_option_value',
        ];

        $this->configManager->expects(self::exactly(3))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$themeConfigurationId, 'configuration'],
                [$themeConfigurationId, 'theme']
            )
            ->willReturn($options, 'test_theme');

        self::assertEquals(
            $expectedResult,
            $this->provider->hasThemeConfigurationOption($option, $scopeIdentifier)
        );
        // test memory cache
        self::assertEquals(
            $expectedResult,
            $this->provider->hasThemeConfigurationOption($option, $scopeIdentifier)
        );
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

    public function testGetThemeNameWhenThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->repository->expects(self::never())
            ->method('getFieldValue');

        self::assertNull($this->provider->getThemeName());
    }

    /**
     * @dataProvider getThemeNameDataProvider
     */
    public function testGetThemeName(?int $scopeIdentifier, ?string $expectedName): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('getFieldValue')
            ->with($themeConfigurationId, 'theme')
            ->willReturn($expectedName);

        self::assertEquals(
            $expectedName,
            $this->provider->getThemeName($scopeIdentifier)
        );
        // test memory cache
        self::assertEquals(
            $expectedName,
            $this->provider->getThemeName($scopeIdentifier)
        );
    }

    public function getThemeNameDataProvider(): array
    {
        $noScopeIdentifier = null;
        $idScopeIdentifier = 123;

        return [
            // no scope
            [$noScopeIdentifier, null],
            [$noScopeIdentifier, 'test_name'],
            // scope identifier as id
            [$idScopeIdentifier, null],
            [$idScopeIdentifier, 'test_name'],
        ];
    }

    public function testGetThemeConfigurationOptionsNamesByTypeNoTheme(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                null
            )
            ->willReturn(null);

        self::assertEquals([], $this->provider->getThemeConfigurationOptionsNamesByType('checkbox'));
    }

    public function testGetThemeConfigurationOptionsNamesByType(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                null
            )
            ->willReturn($themeConfigurationId);

        $this->repository->expects(self::once())
            ->method('getFieldValue')
            ->with($themeConfigurationId, 'theme')
            ->willReturn('test_name');

        $config = ['configuration' => ['sections' => ['header' => ['options' => ['menu' => ['type' => 'checkbox']]]]]];
        $this->configurationProvider->expects(self::once())
            ->method('getThemeDefinition')
            ->with('test_name')
            ->willReturn($config);

        self::assertEquals(
            [LayoutThemeConfiguration::buildOptionKey('header', 'menu')],
            $this->provider->getThemeConfigurationOptionsNamesByType('checkbox')
        );
    }

    private function getThemeDefinition(): array
    {
        return [
            'configuration' => [
                'sections' => [
                    'header' => [
                        'options' => [
                            'show_title' => [
                                'type' => 'checkbox',
                                'default' => 'checked'
                            ],
                            'language' => [
                                'type' => 'select',
                                'default' => 'en',
                                'values' => ['en' => 'en', 'ua' => 'ua']
                            ]
                        ]
                    ],
                    'main' => [
                        'options' => [
                            'show_datagrid' => [
                                'type' => 'checkbox',
                                'default' => 'unchecked'
                            ],
                            'show_chart' => [
                                'type' => 'checkbox'
                            ],
                            'promo_text' => [
                                'type' => 'text'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
