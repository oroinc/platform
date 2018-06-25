<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class ChartViewBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TEMPLATE = 'template.twig.html';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $transformerFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $twig;

    /**
     * @var ChartViewBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ChartBundle\Model\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transformerFactory = $this
            ->getMockBuilder('Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->twig = $this->createMock('Twig_Environment');

        $this->builder = new ChartViewBuilder($this->configProvider, $this->transformerFactory, $this->twig);
    }

    public function testSetData()
    {
        $data = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

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

    public function testSetDataGrid()
    {
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $columnsDefintion = array('foo' => array('foo' => 'bar'));
        $config = DatagridConfiguration::create(array('columns' => $columnsDefintion));

        $datagrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $this->assertEquals($this->builder, $this->builder->setDataGrid($datagrid));
        $this->assertAttributeInstanceOf('Oro\Bundle\ChartBundle\Model\Data\DataGridData', 'data', $this->builder);
        $this->assertAttributeEquals(new DataGridData($datagrid), 'data', $this->builder);
        $this->assertAttributeEquals($columnsDefintion, 'datagridColumnsDefinition', $this->builder);
    }

    public function testSetDataMapping()
    {
        $dataMapping = array('foo' => 'bar');

        $this->assertEquals($this->builder, $this->builder->setDataMapping($dataMapping));
        $this->assertAttributeEquals($dataMapping, 'dataMapping', $this->builder);
    }

    public function testSetDataMappingIgnored()
    {
        $dataMapping = array('foo' => 'foo');

        $this->assertEquals($this->builder, $this->builder->setDataMapping($dataMapping));
        $this->assertAttributeEmpty('dataMapping', $this->builder);
    }

    public function testSetOptions()
    {
        $options = array(
            'name' => 'foo',
            'data_schema' => array('foo' => array('field_name' => 'foo', 'type' => 'integer'))
        );
        $expectedOptions = $options;
        $expectedOptions['settings'] = array();

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals($expectedOptions, 'options', $this->builder);
    }

    public function testSetOptionsWithDataGridColumnsDefinitionMerge()
    {
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $columnsDefintion = array('bar' => array('name' => 'bar', 'label' => 'Foo label', 'frontend_type' => 'int'));
        $config = DatagridConfiguration::create(array('columns' => $columnsDefintion));

        $datagrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $options = array('name' => 'foo', 'data_schema' => array('foo' => 'bar'), 'settings' => array());
        $expectedOptions = $options;
        $expectedOptions['data_schema'] = array(
            'foo' => array(
                'field_name' => 'bar',
                'label' => 'Foo label',
                'type' => 'int',
            )
        );

        $this->assertEquals($this->builder, $this->builder->setDataGrid($datagrid)->setOptions($options));
        $this->assertAttributeEquals($expectedOptions, 'options', $this->builder);
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have "name" key.
     */
    public function testSetOptionsWithoutName()
    {
        $options = array('foo' => 'bar');

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals($options, 'options', $this->builder);
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Options must have "data_schema" key with array.
     */
    public function testSetOptionsWithoutDataSchema()
    {
        $options = array('name' => 'foo', 'data_schema' => 'foo');

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals($options, 'options', $this->builder);
    }

    public function testSetOptionsWithDataMapping()
    {
        $options = array(
            'name' => 'foo',
            'data_schema' => array(
                'label' => array('field_name' => 'foo', 'label' => 'Foo Label', 'type' => 'integer'),
                'value' => array('label' => 'Bar Label', 'type' => 'string')
            ),
            'data_mapping' => array('foo' => 'bar'),
        );

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals(
            array('foo' => 'bar'),
            'dataMapping',
            $this->builder
        );
    }

    public function testSetOptionsWithDataMappingFromDataSchema()
    {
        $options = array(
            'name' => 'foo',
            'data_schema' => array(
                'label' => array('field_name' => 'foo', 'label' => 'Foo Label', 'type' => 'integer'),
                'value' => array('label' => 'Bar Label', 'type' => 'integer')
            )
        );

        $this->assertEquals($this->builder, $this->builder->setOptions($options));
        $this->assertAttributeEquals(
            array(
                'label' => 'foo',
                'value' => 'value',
            ),
            'dataMapping',
            $this->builder
        );
    }

    public function testGetView()
    {
        $chartName = 'chart_name';
        $chartTemplate = 'template.html.twig';

        $data = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

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

        $this->setExpectedChartConfig($chartName, $chartConfig);

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        $this->assertInstanceOf('Oro\Bundle\ChartBundle\Model\ChartView', $chartView);
        $this->assertAttributeEquals($expectedVars, 'vars', $chartView);
        $this->assertAttributeEquals($this->twig, 'twig', $chartView);
        $this->assertAttributeEquals($chartTemplate, 'template', $chartView);
        $this->assertAttributeEquals($data, 'data', $chartView);
    }

    public function testGetViewWithDataTransformer()
    {
        $chartName = 'chart_name';
        $chartTemplate = 'template.html.twig';

        $data = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $dataTransformer = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface');
        $transformedData = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $dataTransformerServiceId = 'data_transformer';

        $chartConfig = array(
            'data_transformer' => $dataTransformerServiceId,
            'template' => $chartTemplate,
            'default_settings' => array(),
        );

        $options = array('name' => $chartName, 'settings' => array('foo' => 'bar'));

        $this->setExpectedChartConfig($chartName, $chartConfig);

        $this->transformerFactory->expects($this->once())
            ->method('createTransformer')
            ->will($this->returnValue($dataTransformer));

        $dataTransformer->expects($this->once())
            ->method('transform')
            ->with($data, $options)
            ->will($this->returnValue($transformedData));

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        $this->assertInstanceOf('Oro\Bundle\ChartBundle\Model\ChartView', $chartView);
        $this->assertAttributeEquals($transformedData, 'data', $chartView);
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Config of chart "chart_name" must have "template" key.
     */
    public function testGetViewFailsWhenConfigDontHaveTemplate()
    {
        $chartName = 'chart_name';

        $data = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');

        $chartConfig = array();

        $options = array('name' => $chartName);

        $this->setExpectedChartConfig($chartName, $chartConfig);

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
        $this->builder->getView();
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\BadMethodCallException
     * @expectedExceptionMessage Can't build result when setData() was not called.
     */
    public function testGetViewFailsWhenDataNotSet()
    {
        $chartName = 'foo';

        $options = array('name' => $chartName);

        $chartConfig = array(
            'template' => 'foo.html.twig',
            'default_settings' => array('bar' => 'baz'),
        );

        $this->setExpectedChartConfig($chartName, $chartConfig);
        $this->builder->setOptions($options)->getView();
    }

    protected function setExpectedChartConfig($chartName, array $chartConfig)
    {
        $this->configProvider->expects($this->once())
            ->method('getChartConfig')
            ->with($chartName)
            ->will($this->returnValue($chartConfig));
    }
}
