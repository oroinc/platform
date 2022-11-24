<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Columns;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;

class ColumnsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $columnsStateProvider;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParameters;

    /** @var MetadataObject|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataObject;

    /** @var ColumnsExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->columnsStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
        $this->metadataObject = $this->createMock(MetadataObject::class);

        $this->extension = new ColumnsExtension($this->columnsStateProvider);
    }

    public function testGetPriority(): void
    {
        self::assertEquals(-10, $this->extension->getPriority());
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $columnsConfig, bool $expectedResult): void
    {
        $this->datagridParameters->expects(self::once())
            ->method('get')
            ->with(ParameterBag::DATAGRID_MODES_PARAMETER)
            ->willReturn([]);

        $this->datagridConfiguration->expects(self::once())
            ->method('offsetGetOr')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($columnsConfig);

        $this->extension->setParameters($this->datagridParameters);
        self::assertSame($expectedResult, $this->extension->isApplicable($this->datagridConfiguration));
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'columns config is empty'     => [
                'columnsConfig'  => [],
                'expectedResult' => false,
            ],
            'columns config is not empty' => [
                'columnsConfig'  => ['sampleColumn'],
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider visitMetadataWhenNoDefaultGridViewDataProvider
     */
    public function testVisitMetadataWhenNoDefaultGridView(array $metadataGridViews): void
    {
        $columnName = 'sampleColumn';
        $columnsState = [
            $columnName => [
                ColumnsStateProvider::ORDER_FIELD_NAME  => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];
        $defaultState = ['sampleDefaultState'];

        $this->columnsStateProvider->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn($columnsState);
        $this->columnsStateProvider->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn($defaultState);

        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetAddToArray')
            ->withConsecutive(
                ['state', [Configuration::COLUMNS_KEY => $columnsState]],
                ['initialState', [Configuration::COLUMNS_KEY => $defaultState]]
            );
        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn([['name' => $columnName]]);
        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetSetByPath')
            ->withConsecutive(
                [
                    sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::ORDER_FIELD_NAME),
                    $columnsState[$columnName][ColumnsStateProvider::ORDER_FIELD_NAME]
                ],
                [
                    sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::RENDER_FIELD_NAME),
                    $columnsState[$columnName][ColumnsStateProvider::RENDER_FIELD_NAME]
                ]
            );
        $this->metadataObject->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn($metadataGridViews);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    public function visitMetadataWhenNoDefaultGridViewDataProvider(): array
    {
        return [
            'empty metadata grid views' => [
                'metadataGridViews' => [],
            ],
            'no default grid view in'   => [
                'metadataGridViews' => [['name' => 'notDefaultGridView']],
            ],
        ];
    }

    public function testVisitMetadata(): void
    {
        $columnName = 'sampleColumn';
        $columnsState = [
            $columnName => [
                ColumnsStateProvider::ORDER_FIELD_NAME  => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];
        $defaultState = ['sampleDefaultState'];

        $this->columnsStateProvider->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn($columnsState);
        $this->columnsStateProvider->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn($defaultState);

        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetAddToArray')
            ->withConsecutive(
                ['state', [Configuration::COLUMNS_KEY => $columnsState]],
                ['initialState', [Configuration::COLUMNS_KEY => $defaultState]]
            );
        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn([['name' => $columnName]]);
        $this->metadataObject->expects(self::exactly(3))
            ->method('offsetSetByPath')
            ->withConsecutive(
                [
                    sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::ORDER_FIELD_NAME),
                    $columnsState[$columnName][ColumnsStateProvider::ORDER_FIELD_NAME]
                ],
                [
                    sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::RENDER_FIELD_NAME),
                    $columnsState[$columnName][ColumnsStateProvider::RENDER_FIELD_NAME]
                ],
                [
                    sprintf('[gridViews][views][%s][%s]', 0, 'columns'),
                    $defaultState
                ]
            );
        $this->metadataObject->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn([['name' => GridViewsExtension::DEFAULT_VIEW_ID]]);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    public function testVisitMetadataWhenNoColumnName(): void
    {
        $columnsState = [
            'sampleColumn' => [
                ColumnsStateProvider::ORDER_FIELD_NAME  => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true
            ],
        ];
        $metadata = [['sampleProperty' => 'sampleValue']];

        $this->assertMetadataNotUpdated($columnsState, $metadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    public function testVisitMetadataWhenNoColumnInState(): void
    {
        $columnsState = [
            'anotherColumn' => [
                ColumnsStateProvider::ORDER_FIELD_NAME  => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];
        $metadata = [['name' => 'sampleColumn']];

        $this->assertMetadataNotUpdated($columnsState, $metadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    private function assertMetadataNotUpdated(array $columnsState, array $metadata): void
    {
        $defaultState = ['sampleDefaultState'];

        $this->columnsStateProvider->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn($columnsState);
        $this->columnsStateProvider->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn($defaultState);

        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn($metadata);
        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetAddToArray')
            ->withConsecutive(
                ['state', [Configuration::COLUMNS_KEY => $columnsState]],
                ['initialState', [Configuration::COLUMNS_KEY => $defaultState]]
            );
        $this->metadataObject->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn([['name' => GridViewsExtension::DEFAULT_VIEW_ID]]);
        $this->metadataObject->expects(self::once())
            ->method('offsetSetByPath')
            ->with(
                sprintf('[gridViews][views][%s][%s]', 0, 'columns'),
                $defaultState
            );
    }

    public function testVisitMetadataDisabledProvider(): array
    {
        return [
            'default behavior' => [
                'configColumns' => [
                    'column1' => [
                        'label'      => 'column1.label',
                    ],
                    'column2' => [
                        'label'    => 'column2.label',
                        'disabled' => false,
                    ],
                ],
                'expectedColumns' => [
                    'column1' => [
                        'label'      => 'column1.label',
                    ],
                    'column2' => [
                        'label'    => 'column2.label',
                        'disabled' => false,
                    ],
                ],
            ],
            'disabled column' => [
                'configColumns' => [
                    'column1' => [
                        'label'      => 'column1.label',
                    ],
                    'column2' => [
                        'label'    => 'column2.label',
                        'disabled' => true,
                    ],
                ],
                'expectedColumns' => [
                    'column1' => [
                        'label'      => 'column1.label',
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider testVisitMetadataDisabledProvider
     */
    public function testVisitMetadataDisabled(array $configColumns, array $expectedColumns)
    {
        $this->columnsStateProvider->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn([]);
        $this->columnsStateProvider->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn(['sampleDefaultState']);
        $this->metadataObject->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn(['columnConfig' => []]);

        $this->metadataObject->expects(self::exactly(2))
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn($configColumns);

        $this->metadataObject->expects(self::once())
            ->method('offsetSet')
            ->with(Configuration::COLUMNS_KEY, $expectedColumns);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }
}
