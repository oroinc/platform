<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\NavigationBundle\Provider\MenuNamesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuNamesProviderTest extends TestCase
{
    private ConfigurationProvider|MockObject $configurationProvider;

    private MenuNamesProvider $provider;

    public function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $this->provider = new MenuNamesProvider($this->configurationProvider);
    }

    /**
     * @dataProvider getMenuNamesDataProvider
     */
    public function testGetMenuNames(array $menusTree, string $scopeType, array $expected): void
    {
        $this->configurationProvider
            ->expects(self::once())
            ->method('getMenuTree')
            ->willReturn($menusTree);

        self::assertEquals($expected, $this->provider->getMenuNames($scopeType));
    }

    public function getMenuNamesDataProvider(): array
    {
        return [
            'empty menus tree' => [
                'menusTree' => [],
                'scopeType' => '',
                'expected' => [],
            ],
            'returns all menu names when empty scopeType' => [
                'menusTree' => [
                    'menu1' => [],
                    'menu2' => [],
                ],
                'scopeType' => '',
                'expected' => ['menu1', 'menu2'],
            ],
            'treats menus without scope type as with default scope type' => [
                'menusTree' => [
                    'menu1' => [],
                    'menu2' => ['scope_type' => 'sample_scope'],
                ],
                'scopeType' => ConfigurationBuilder::DEFAULT_SCOPE_TYPE,
                'expected' => ['menu1'],
            ],
            'returns menu names for specified scope' => [
                'menusTree' => [
                    'menu1' => [],
                    'menu2' => ['scope_type' => 'sample_scope'],
                ],
                'scopeType' => 'sample_scope',
                'expected' => ['menu2'],
            ],
            'returns empty array when there are no menus with specified scope type' => [
                'menusTree' => [
                    'menu1' => [],
                    'menu2' => ['scope_type' => 'sample_scope'],
                ],
                'scopeType' => 'missing_scope',
                'expected' => [],
            ],
        ];
    }
}
