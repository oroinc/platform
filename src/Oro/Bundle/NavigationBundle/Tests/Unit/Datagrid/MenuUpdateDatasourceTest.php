<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Datagrid;

use Knp\Menu\MenuFactory;
use Knp\Menu\Util\MenuManipulator;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\NavigationBundle\Config\MenuConfiguration;
use Oro\Bundle\NavigationBundle\Datagrid\MenuUpdateDatasource;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;

class MenuUpdateDatasourceTest extends \PHPUnit\Framework\TestCase
{
    /** @var MenuUpdateDatasource */
    protected $datasource;

    /** @var BuilderChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $chainProvider;

    /** @var MenuManipulator|\PHPUnit\Framework\MockObject\MockObject */
    protected $menuManipulator;

    /** @var string */
    protected $scopeType = 'default';

    /** @var MenuConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    protected $menuConfiguration;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->chainProvider = $this->createMock(BuilderChainProvider::class);
        $this->menuManipulator = $this->createMock(MenuManipulator::class);
        $this->menuConfiguration = $this->getMockBuilder(MenuConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->datasource = new MenuUpdateDatasource(
            $this->chainProvider,
            $this->menuManipulator,
            $this->scopeType,
            $this->menuConfiguration
        );
    }

    public function testProcess()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $grid */
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
        $this->menuConfiguration->expects(self::once())
            ->method('getTree')
            ->willReturn([$menu['name'] => $menu]);

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
