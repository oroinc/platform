<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Helper;

use Symfony\Component\Routing\RouterInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\DataGridBundle\Tools\FilteredDatagridRouteHelper;

class FilteredDatagridRouteHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatagridRouteHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridRouteHelper;

    /**
     * @var string $gridRouteName
     */
    protected $gridRouteName;

    /**
     * @var string $gridName
     */
    protected $gridName;

    /** @var FilteredDatagridRouteHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->datagridRouteHelper = $this->createMock(DatagridRouteHelper::class);

        $this->gridRouteName = 'route_name';
        $this->gridName = 'grid_name';
        
        $this->helper = new FilteredDatagridRouteHelper(
            $this->gridRouteName,
            $this->gridName,
            $this->datagridRouteHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->datagridRouteHelper, $this->helper);
    }

    public function testGenerate()
    {
        $this->datagridRouteHelper->expects($this->once())->method('generate')->with(
            $this->gridRouteName,
            $this->gridName,
            ['f' => ['filterName' => ['value' => '10']]],
            RouterInterface::ABSOLUTE_PATH
        )->willReturn('generatedURL');

        $this->assertEquals('generatedURL', $this->helper->generate(['filterName' => 10]));
    }
}
