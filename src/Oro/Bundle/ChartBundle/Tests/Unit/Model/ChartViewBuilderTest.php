<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataGridData;

class ChartViewBuilderTest extends \PHPUnit_Framework_TestCase
{
    const TEMPLATE = 'template.twig.html';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $twig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataGridManager;

    /**
     * @var ChartViewBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('\Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->twig = $this->getMock('Twig_Environment');
        $this->dataGridManager = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface');

        $this->builder = new ChartViewBuilder(
            $this->configProvider,
            $this->twig,
            $this->dataGridManager
        );
    }

    public function testSetData()
    {
        $data = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $this->assertEquals($this->builder, $this->builder->setData($data));
        $this->assertAttributeEquals($data, 'data', $this->builder);
    }

    public function testSetArrayData()
    {
        $arrayData = array('foo' => 'bar');

        $this->assertEquals($this->builder, $this->builder->setArrayData($arrayData));
        $this->assertAttributeInstanceOf('Oro\Bundle\ChartBundle\Model\Data\ArrayData', 'data', $this->builder);
        $this->assertAttributeEquals(new ArrayData($arrayData), 'data', $this->builder);
    }

    public function testSetDataGridName()
    {
        $gridName = 'test_grid_name';

        $this->assertEquals($this->builder, $this->builder->setDataGridName($gridName));
        $this->assertAttributeInstanceOf('Oro\Bundle\ChartBundle\Model\Data\DataGridData', 'data', $this->builder);
        $this->assertAttributeEquals(new DataGridData($this->dataGridManager, $gridName), 'data', $this->builder);
    }

    public function testSetDataMapping()
    {
        $dataMapping = array('foo' => 'bar');

        $this->assertEquals($this->builder, $this->builder->setDataMapping($dataMapping));
        $this->assertAttributeEquals($dataMapping, 'dataMapping', $this->builder);
    }

    public function testSetOptions()
    {
        $options = array('foo' => 'bar');

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals($options, 'options', $this->builder);
    }

    public function testGetView()
    {
        $chartName = 'chart_name';
        $chartTemplate = 'template.html.twig';

        $data = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $chartConfig = array(
            'template' => $chartTemplate,
            'default_settings' => array('bar' => 'baz'),
        );

        $options = array(
            'name' => $chartName,
            'settings' => array('foo' => 'bar'),
        );

        $expectedVars = array(
            'options' => array(
                'name' => $chartName,
                'settings' => array('foo' => 'bar', 'bar' => 'baz'),
            ),
            'config' => $chartConfig
        );

        $this->configProvider->expects($this->once())
            ->method('getChartConfig')
            ->with($chartName)
            ->will($this->returnValue($chartConfig));

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        $this->assertInstanceOf('Oro\Bundle\ChartBundle\Model\ChartView', $chartView);
        $this->assertAttributeEquals($expectedVars, 'vars', $chartView);
        $this->assertAttributeEquals($this->twig, 'twig', $chartView);
        $this->assertAttributeEquals($chartTemplate, 'template', $chartView);
        $this->assertAttributeEquals($data, 'data', $chartView);
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have "name" key.
     */
    public function testGetViewFailsWhenOptionsDontHaveName()
    {
        $data = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $options = array();

        $this->builder->setOptions($options)
            ->setData($data)
            ->getView();
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Config of chart "chart_name" must have "template" key.
     */
    public function testGetViewFailsWhenConfigDontHaveTemplate()
    {
        $chartName = 'chart_name';

        $data = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $chartConfig = array();

        $options = array('name' => $chartName);

        $this->configProvider->expects($this->once())
            ->method('getChartConfig')
            ->with($chartName)
            ->will($this->returnValue($chartConfig));

        $this->builder->setOptions($options)
            ->setData($data)
            ->getView();
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\BadMethodCallException
     * @expectedExceptionMessage Can't build result when setOptions() was not called.
     */
    public function testGetViewFailsWhenOptionsNotSet()
    {
        $data = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $this->builder
            ->setData($data)
            ->getView();
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\BadMethodCallException
     * @expectedExceptionMessage Can't build result when setData() was not called.
     */
    public function testGetViewFailsWhenDataNotSet()
    {
        $this->builder->getView();
    }
}
