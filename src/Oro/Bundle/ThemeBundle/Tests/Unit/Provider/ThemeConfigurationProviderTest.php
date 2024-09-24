<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThemeConfigurationProviderTest extends TestCase
{
    private ConfigManager|MockObject $configManager;
    private EntityManagerInterface|MockObject $em;
    private ThemeConfigurationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ThemeConfiguration::class)
            ->willReturn($this->em);

        $this->provider = new ThemeConfigurationProvider($this->configManager, $doctrine);
    }

    private function expectsLoadValue(int $themeConfigurationId, string $fieldName, array $rows): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('from')
            ->with(ThemeConfiguration::class, 'e')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('select')
            ->with('e.' . $fieldName)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('where')
            ->with('e.id = :id')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('id', $themeConfigurationId, Types::INTEGER)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($rows);
    }

    public function testGetThemeConfigurationOptionsWhenThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->em->expects(self::never())
            ->method('createQueryBuilder');

        self::assertSame([], $this->provider->getThemeConfigurationOptions());
    }

    public function testGetThemeConfigurationOptionsWhenThemeConfigurationNotExist(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->expectsLoadValue($themeConfigurationId, 'configuration', []);

        self::assertSame([], $this->provider->getThemeConfigurationOptions());
    }

    /**
     * @dataProvider getThemeConfigurationOptionsDataProvider
     */
    public function testGetThemeConfigurationOptions(?int $scopeIdentifier, array $expectedOptions): void
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

        $this->expectsLoadValue($themeConfigurationId, 'configuration', [['configuration' => $expectedOptions]]);

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

    public function testGetThemeConfigurationOptionWhenThemeConfigurationNotSet(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn(null);

        $this->em->expects(self::never())
            ->method('createQueryBuilder');

        self::assertNull($this->provider->getThemeConfigurationOption('some_option'));
    }

    public function testGetThemeConfigurationOptionWhenThemeConfigurationNotExist(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->expectsLoadValue($themeConfigurationId, 'configuration', []);

        self::assertNull($this->provider->getThemeConfigurationOption('some_option'));
    }

    public function testGetThemeConfigurationOptionForEmptyThemeConfiguration(): void
    {
        $themeConfigurationId = 1;

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION))
            ->willReturn($themeConfigurationId);

        $this->expectsLoadValue($themeConfigurationId, 'configuration', [['configuration' => []]]);

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

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->expectsLoadValue($themeConfigurationId, 'configuration', [['configuration' => $options]]);

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

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                false,
                false,
                $scopeIdentifier
            )
            ->willReturn($themeConfigurationId);

        $this->expectsLoadValue($themeConfigurationId, 'configuration', [['configuration' => $options]]);

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

        $this->em->expects(self::never())
            ->method('createQueryBuilder');

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

        $this->expectsLoadValue($themeConfigurationId, 'theme', [['theme' => $expectedName]]);

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
}
