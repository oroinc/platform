<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Datagrid;

use Knp\Menu\MenuFactory;
use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\NavigationBundle\Datagrid\MenuUpdateDatasource;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;

class MenuUpdateDatasourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuUpdateDatasource */
    protected $datasource;

    /** @var BuilderChainProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $chainProvider;

    /** @var MenuManipulator|\PHPUnit_Framework_MockObject_MockObject */
    protected $menuManipulator;

    /** @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var string */
    protected $scopeType = 'default';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->chainProvider = $this->createMock(BuilderChainProvider::class);
        $this->menuManipulator = $this->createMock(MenuManipulator::class);
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datasource = new MenuUpdateDatasource(
            $this->chainProvider,
            $this->menuManipulator,
            $this->configurationProvider,
            $this->scopeType
        );
    }

    public function testProcess()
    {
        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $grid */
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->once())
            ->method('setDatasource')
            ->with($this->datasource);

        $this->datasource->process($grid, []);
    }

    /**
     * @dataProvider menuConfigurationProvider
     *
     * @param array $menu
     * @param int $resultCount
     */
    public function testGetResults(array $menu, $resultCount)
    {
        $this->configurationProvider
            ->expects($this->once())
            ->method('getConfiguration')
            ->with(ConfigurationProvider::MENU_CONFIG_KEY)
            ->willReturn(['tree' => [$menu['name'] => $menu]]);

        $factory = new MenuFactory();
        $menuItem = $factory->createItem($menu['name'], $menu);

        $this->chainProvider
            ->expects($this->once())
            ->method('get')
            ->with($menu['name'])
            ->will($this->returnValue($menuItem));

        if ($resultCount) {
            $this->menuManipulator
                ->expects($this->once())
                ->method('toArray')
                ->with($menuItem)
                ->will($this->returnValue([]));
        }

        $this->assertEquals($resultCount, count($this->datasource->getResults()));
    }

    /**
     * @return array
     */
    public function menuConfigurationProvider()
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
