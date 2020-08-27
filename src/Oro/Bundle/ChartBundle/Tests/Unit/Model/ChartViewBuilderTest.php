<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Exception\BadMethodCallException;
use Oro\Bundle\ChartBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ChartBundle\Model\ChartView;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataGridData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerFactory;
use Oro\Bundle\ChartBundle\Model\Data\Transformer\TransformerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChartViewBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TEMPLATE = 'template.twig.html';

    /** @var ConfigProvider|MockObject */
    protected $configProvider;

    /** @var TransformerFactory|MockObject */
    protected $transformerFactory;

    /** @var Environment|MockObject */
    protected $twig;

    /** @var ChartViewBuilder */
    protected $builder;

    protected function setUp(): void
    {
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)->disableOriginalConstructor()->getMock();
        $this->transformerFactory = $this
            ->getMockBuilder(TransformerFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->twig = $this->createMock(Environment::class);

        $this->builder = new class(
            $this->configProvider,
            $this->transformerFactory,
            $this->twig
        ) extends ChartViewBuilder {
            public function xgetData(): DataInterface
            {
                return $this->data;
            }

            public function xgetOptions(): array
            {
                return $this->options;
            }

            public function xgetDatagridColumnsDefinition(): array
            {
                return $this->datagridColumnsDefinition;
            }

            public function xgetDataMapping(): ?array
            {
                return $this->dataMapping;
            }
        };
    }

    public function testSetData()
    {
        $data = $this->createMock(DataInterface::class);

        static::assertSame($this->builder, $this->builder->setData($data));
        static::assertEquals($data, $this->builder->xgetData());
    }

    public function testSetArrayData()
    {
        $result = $this->builder->setArrayData(['foo' => 'bar']);

        static::assertSame($this->builder, $result);
        static::assertInstanceOf(ArrayData::class, $this->builder->xgetData());
        static::assertEquals(new ArrayData(['foo' => 'bar']), $this->builder->xgetData());
    }

    public function testSetDataGrid()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $columnsDefinition = ['foo' => ['foo' => 'bar']];
        $config = DatagridConfiguration::create(['columns' => $columnsDefinition]);

        $datagrid->expects(static::once())->method('getConfig')->willReturn($config);

        $result = $this->builder->setDataGrid($datagrid);

        static::assertSame($this->builder, $result);
        static::assertInstanceOf(DataGridData::class, $this->builder->xgetData());
        static::assertEquals(new DataGridData($datagrid), $this->builder->xgetData());
        static::assertEquals($columnsDefinition, $this->builder->xgetDatagridColumnsDefinition());
    }

    public function testSetDataMapping()
    {
        $dataMapping = ['foo' => 'bar'];

        $result = $this->builder->setDataMapping($dataMapping);

        static::assertSame($this->builder, $result);
        static::assertEquals($dataMapping, $this->builder->xgetDataMapping());
    }

    public function testSetDataMappingIgnored()
    {
        $dataMapping = ['foo' => 'foo'];

        $result = $this->builder->setDataMapping($dataMapping);

        static::assertSame($this->builder, $result);
        static::assertEmpty($this->builder->xgetDataMapping());
    }

    public function testSetOptions()
    {
        $options = [
            'name' => 'foo',
            'data_schema' => ['foo' => ['field_name' => 'foo', 'type' => 'integer']]
        ];
        $expectedOptions = $options;
        $expectedOptions['settings'] = [];

        $result = $this->builder->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals($expectedOptions, $this->builder->xgetOptions());
    }

    public function testSetOptionsWithDataGridColumnsDefinitionMerge()
    {
        $columnsDefinition = ['bar' => ['name' => 'bar', 'label' => 'Foo label', 'frontend_type' => 'int']];

        $config = DatagridConfiguration::create(['columns' => $columnsDefinition]);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(static::once())->method('getConfig')->willReturn($config);

        $options = ['name' => 'foo', 'data_schema' => ['foo' => 'bar'], 'settings' => []];
        $expectedOptions = $options;
        $expectedOptions['data_schema'] = [
            'foo' => [
                'field_name' => 'bar',
                'label' => 'Foo label',
                'type' => 'int',
            ]
        ];

        $result = $this->builder->setDataGrid($datagrid)->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals($expectedOptions, $this->builder->xgetOptions());
    }

    public function testSetOptionsWithoutName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have "name" key.');

        $options = ['foo' => 'bar'];

        $result = $this->builder->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals($options, $this->builder->xgetOptions());
    }

    public function testSetOptionsWithoutDataSchema()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have "data_schema" key with array.');

        $options = ['name' => 'foo', 'data_schema' => 'foo'];

        $result = $this->builder->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals($options, $this->builder->xgetOptions());
    }

    public function testSetOptionsWithDataMapping()
    {
        $options = [
            'name' => 'foo',
            'data_schema' => [
                'label' => ['field_name' => 'foo', 'label' => 'Foo Label', 'type' => 'integer'],
                'value' => ['label' => 'Bar Label', 'type' => 'string']
            ],
            'data_mapping' => ['foo' => 'bar'],
        ];

        $result = $this->builder->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals(['foo' => 'bar'], $this->builder->xgetDataMapping());
    }

    public function testSetOptionsWithDataMappingFromDataSchema()
    {
        $options = [
            'name' => 'foo',
            'data_schema' => [
                'label' => ['field_name' => 'foo', 'label' => 'Foo Label', 'type' => 'integer'],
                'value' => ['label' => 'Bar Label', 'type' => 'integer']
            ]
        ];

        $result = $this->builder->setOptions($options);

        static::assertSame($this->builder, $result);
        static::assertEquals(['label' => 'foo', 'value' => 'value'], $this->builder->xgetDataMapping());
    }

    public function testGetView()
    {
        $chartName = 'chart_name';
        $chartTemplate = 'template.html.twig';

        $data = new ArrayData([]);

        $chartConfig = [
            'template' => $chartTemplate,
            'default_settings' => ['bar' => 'baz'],
        ];

        $options = [
            'name' => $chartName,
            'settings' => ['foo' => 'bar'],
        ];

        $expectedVars = [
            'options' => [
                'name' => $chartName,
                'settings' => ['foo' => 'bar', 'bar' => 'baz'],
            ],
            'config' => $chartConfig
        ];

        $this->configProvider->expects(static::once())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartConfig);

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        // assertions
        static::assertInstanceOf(ChartView::class, $chartView);

        $this->twig->expects(static::once())
            ->method('render')
            ->with(
                static::equalTo($chartTemplate),
                static::equalTo(\array_merge($expectedVars, ['data' => $data->toArray()]))
            );

        $chartView->render();
    }

    public function testGetViewWithDataTransformer()
    {
        $chartName = 'chart_name';
        $chartTemplate = 'template.html.twig';

        $data = new ArrayData([]);
        $dataTransformer = $this->createMock(TransformerInterface::class);
        $dataTransformerServiceId = 'data_transformer';

        $chartConfig = [
            'data_transformer' => $dataTransformerServiceId,
            'template' => $chartTemplate,
            'default_settings' => [],
        ];

        $options = ['name' => $chartName, 'settings' => ['foo' => 'bar']];

        $this->configProvider->expects(static::once())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartConfig);

        $this->transformerFactory->expects(static::once())
            ->method('createTransformer')
            ->willReturn($dataTransformer);

        $dataTransformer->expects(static::once())
            ->method('transform')
            ->with($data, $options)
            ->willReturn(new ArrayData([]));

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        // assertions
        static::assertInstanceOf(ChartView::class, $chartView);

        $this->twig->expects(static::once())
            ->method('render')
            ->with(
                static::anything(),
                static::equalTo(['options' => $options, 'config' => $chartConfig, 'data' => $data->toArray()])
            );

        $chartView->render();
    }

    public function testGetViewFailsWhenConfigDontHaveTemplate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config of chart "chart_name" must have "template" key.');

        $this->configProvider->expects(static::once())->method('getChartConfig')->with('chart_name')->willReturn([]);

        $this->builder->setOptions(['name' => 'chart_name'])->setData(new ArrayData([]))->getView();
    }

    public function testGetViewFailsWhenOptionsNotSet()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't build result when setOptions() was not called.");

        $this->builder->getView();
    }

    public function testGetViewFailsWhenDataNotSet()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage("Can't build result when setData() was not called.");

        $this->configProvider->expects(ChartViewBuilderTest::once())
            ->method('getChartConfig')
            ->with('chart_name')
            ->willReturn([
                'template' => 'foo.html.twig',
                'default_settings' => ['bar' => 'baz'],
            ]);
        $this->builder->setOptions(['name' => 'chart_name'])->getView();
    }
}
