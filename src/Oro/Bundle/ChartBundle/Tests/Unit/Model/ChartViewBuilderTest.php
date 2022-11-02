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
use Oro\Component\Testing\ReflectionUtil;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChartViewBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var TransformerFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $transformerFactory;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var ChartViewBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->transformerFactory = $this->createMock(TransformerFactory::class);
        $this->twig = $this->createMock(Environment::class);

        $this->builder = new ChartViewBuilder(
            $this->configProvider,
            $this->transformerFactory,
            $this->twig
        );
    }

    private function getData(ChartViewBuilder $builder): DataInterface
    {
        return ReflectionUtil::getPropertyValue($builder, 'data');
    }

    private function getOptions(ChartViewBuilder $builder): array
    {
        return ReflectionUtil::getPropertyValue($builder, 'options');
    }

    private function getDatagridColumnsDefinition(ChartViewBuilder $builder): array
    {
        return ReflectionUtil::getPropertyValue($builder, 'datagridColumnsDefinition');
    }

    private function getDataMapping(ChartViewBuilder $builder): ?array
    {
        return ReflectionUtil::getPropertyValue($builder, 'dataMapping');
    }

    public function testSetData()
    {
        $data = $this->createMock(DataInterface::class);

        self::assertSame($this->builder, $this->builder->setData($data));
        self::assertEquals($data, $this->getData($this->builder));
    }

    public function testSetArrayData()
    {
        $result = $this->builder->setArrayData(['foo' => 'bar']);

        self::assertSame($this->builder, $result);
        self::assertInstanceOf(ArrayData::class, $this->getData($this->builder));
        self::assertEquals(new ArrayData(['foo' => 'bar']), $this->getData($this->builder));
    }

    public function testSetDataGrid()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $columnsDefinition = ['foo' => ['foo' => 'bar']];
        $config = DatagridConfiguration::create(['columns' => $columnsDefinition]);

        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $result = $this->builder->setDataGrid($datagrid);

        self::assertSame($this->builder, $result);
        self::assertInstanceOf(DataGridData::class, $this->getData($this->builder));
        self::assertEquals(new DataGridData($datagrid), $this->getData($this->builder));
        self::assertEquals($columnsDefinition, $this->getDatagridColumnsDefinition($this->builder));
    }

    public function testSetDataMapping()
    {
        $dataMapping = ['foo' => 'bar'];

        $result = $this->builder->setDataMapping($dataMapping);

        self::assertSame($this->builder, $result);
        self::assertEquals($dataMapping, $this->getDataMapping($this->builder));
    }

    public function testSetDataMappingIgnored()
    {
        $dataMapping = ['foo' => 'foo'];

        $result = $this->builder->setDataMapping($dataMapping);

        self::assertSame($this->builder, $result);
        self::assertEmpty($this->getDataMapping($this->builder));
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

        self::assertSame($this->builder, $result);
        self::assertEquals($expectedOptions, $this->getOptions($this->builder));
    }

    public function testSetOptionsWithDataGridColumnsDefinitionMerge()
    {
        $columnsDefinition = ['bar' => ['name' => 'bar', 'label' => 'Foo label', 'frontend_type' => 'int']];

        $config = DatagridConfiguration::create(['columns' => $columnsDefinition]);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

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

        self::assertSame($this->builder, $result);
        self::assertEquals($expectedOptions, $this->getOptions($this->builder));
    }

    public function testSetOptionsWithoutName()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have "name" key.');

        $options = ['foo' => 'bar'];

        $result = $this->builder->setOptions($options);

        self::assertSame($this->builder, $result);
        self::assertEquals($options, $this->getOptions($this->builder));
    }

    public function testSetOptionsWithoutDataSchema()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have "data_schema" key with array.');

        $options = ['name' => 'foo', 'data_schema' => 'foo'];

        $result = $this->builder->setOptions($options);

        self::assertSame($this->builder, $result);
        self::assertEquals($options, $this->getOptions($this->builder));
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

        self::assertSame($this->builder, $result);
        self::assertEquals(['foo' => 'bar'], $this->getDataMapping($this->builder));
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

        self::assertSame($this->builder, $result);
        self::assertEquals(['label' => 'foo', 'value' => 'value'], $this->getDataMapping($this->builder));
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

        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartConfig);

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        // assertions
        self::assertInstanceOf(ChartView::class, $chartView);

        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                $chartTemplate,
                array_merge($expectedVars, ['data' => $data->toArray()])
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

        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartConfig);

        $this->transformerFactory->expects(self::once())
            ->method('createTransformer')
            ->willReturn($dataTransformer);

        $dataTransformer->expects(self::once())
            ->method('transform')
            ->with($data, $options)
            ->willReturn(new ArrayData([]));

        $chartView = $this->builder->setOptions($options)
            ->setData($data)
            ->getView();

        // assertions
        self::assertInstanceOf(ChartView::class, $chartView);

        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                self::anything(),
                ['options' => $options, 'config' => $chartConfig, 'data' => $data->toArray()]
            );

        $chartView->render();
    }

    public function testGetViewFailsWhenConfigDontHaveTemplate()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Config of chart "chart_name" must have "template" key.');

        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with('chart_name')
            ->willReturn([]);

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

        $this->configProvider->expects(self::once())
            ->method('getChartConfig')
            ->with('chart_name')
            ->willReturn([
                'template' => 'foo.html.twig',
                'default_settings' => ['bar' => 'baz'],
            ]);
        $this->builder->setOptions(['name' => 'chart_name'])->getView();
    }
}
