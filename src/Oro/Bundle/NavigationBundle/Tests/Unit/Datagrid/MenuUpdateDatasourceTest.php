<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Datagrid;

use Knp\Menu\MenuFactory;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Datagrid\MenuUpdateDatasource;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuUpdateDatasourceTest extends TestCase
{
    private BuilderChainProvider&MockObject $chainProvider;
    private MenuManipulator&MockObject $menuManipulator;
    private ConfigurationProvider&MockObject $configurationProvider;
    private MenuUpdateDatasource $datasource;

    #[\Override]
    protected function setUp(): void
    {
        $this->chainProvider = $this->createMock(BuilderChainProvider::class);
        $this->menuManipulator = $this->createMock(MenuManipulator::class);
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $this->datasource = new MenuUpdateDatasource(
            $this->chainProvider,
            $this->menuManipulator,
            'default',
            $this->configurationProvider
        );
    }

    public function testProcess(): void
    {
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->once())
            ->method('setDatasource')
            ->with($this->datasource);

        $this->datasource->process($grid, []);
    }

    /**
     * @dataProvider menuConfigurationProvider
     */
    public function testGetResults(array $menu, int $resultCount): void
    {
        $this->configurationProvider->expects(self::once())
            ->method('getMenuTree')
            ->willReturn([$menu['name'] => $menu]);

        $factory = new MenuFactory();
        $menuItem = $factory->createItem($menu['name'], $menu);

        $this->chainProvider->expects($this->once())
            ->method('get')
            ->with($menu['name'])
            ->willReturn($menuItem);

        if ($resultCount) {
            $this->menuManipulator->expects($this->once())
                ->method('toArray')
                ->with($menuItem)
                ->willReturn([]);
        }

        $this->assertCount($resultCount, $this->datasource->getResults());
    }

    public function menuConfigurationProvider(): array
    {
        return [
            [
                [
                    'name' => 'default_menu',
                    'extras' => ['scope_type' => 'default']
                ],
                'result_count' => 1
            ],
            [
                [
                    'name' => 'default_menu',
                    'extras' => ['scope_type' => 'custom']
                ],
                'result_count' => 0
            ],
        ];
    }
}
