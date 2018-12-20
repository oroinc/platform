<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Columns;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;

class ColumnsExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $columnsStateProvider;

    /** @var ColumnsExtension */
    private $extension;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParameters;

    /** @var MetadataObject|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataObject;

    protected function setUp()
    {
        $this->columnsStateProvider = $this->createMock(DatagridStateProviderInterface::class);

        $this->extension = new ColumnsExtension($this->columnsStateProvider);

        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
        $this->metadataObject = $this->createMock(MetadataObject::class);
    }

    public function testGetPriority(): void
    {
        self::assertEquals(-10, $this->extension->getPriority());
    }

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param array $columnsConfig
     * @param bool $expectedResult
     */
    public function testIsApplicable(array $columnsConfig, $expectedResult): void
    {
        $this->datagridParameters
            ->expects(self::once())
            ->method('get')
            ->with(ParameterBag::DATAGRID_MODES_PARAMETER)
            ->willReturn([]);

        $this->datagridConfiguration
            ->expects(self::once())
            ->method('offsetGetOr', null)
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($columnsConfig);

        $this->extension->setParameters($this->datagridParameters);
        self::assertSame($expectedResult, $this->extension->isApplicable($this->datagridConfiguration));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider(): array
    {
        return [
            'columns config is empty' => [
                'columnsConfig' => [],
                'expectedResult' => false,
            ],
            'columns config is not empty' => [
                'columnsConfig' => ['sampleColumn'],
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider visitMetadataWhenNoDefaultGridViewDataProvider
     *
     * @param array $metadataGridViews
     */
    public function testVisitMetadataWhenNoDefaultGridView(array $metadataGridViews): void
    {
        $columnName = 'sampleColumn';
        $columnsState = [
            $columnName => [
                ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];

        $this->assertStateIsSet($columnsState);

        $this->assertMetadataColumnsUpdated($columnName, $columnsState);

        $this->assertInitialStateIsSet($defaultState = ['sampleDefaultState']);

        $this->metadataObject
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn($metadataGridViews);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    /**
     * @return array
     */
    public function visitMetadataWhenNoDefaultGridViewDataProvider(): array
    {
        return [
            'empty metadata grid views' => [
                'metadataGridViews' => [],
            ],
            'no default grid view in' => [
                'metadataGridViews' => [['name' => 'notDefaultGridView']],
            ],
        ];
    }

    /**
     * @param array $defaultState
     */
    private function assertInitialStateIsSet(array $defaultState): void
    {
        $this->columnsStateProvider
            ->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn($defaultState);

        $this->metadataObject
            ->expects(self::at(4))
            ->method('offsetAddToArray')
            ->with('initialState', [Configuration::COLUMNS_KEY => $defaultState]);
    }

    /**
     * @param array $state
     */
    private function assertStateIsSet(array $state): void
    {
        $this->columnsStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn($state);

        $this->metadataObject
            ->expects(self::at(0))
            ->method('offsetAddToArray')
            ->with('state', [Configuration::COLUMNS_KEY => $state]);
    }

    /**
     * @param string $columnName
     * @param array $columnsState
     */
    private function assertMetadataColumnsUpdated(string $columnName, array $columnsState): void
    {
        $this->metadataObject
            ->expects(self::once())
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn([['name' => $columnName]]);

        $this->metadataObject
            ->expects(self::at(2))
            ->method('offsetSetByPath')
            ->with(
                sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::ORDER_FIELD_NAME),
                $columnsState[$columnName][ColumnsStateProvider::ORDER_FIELD_NAME]
            );

        $this->metadataObject
            ->expects(self::at(3))
            ->method('offsetSetByPath')
            ->with(
                sprintf('[%s][%s][%s]', 'columns', 0, ColumnsStateProvider::RENDER_FIELD_NAME),
                $columnsState[$columnName][ColumnsStateProvider::RENDER_FIELD_NAME]
            );
    }

    public function testVisitMetadata(): void
    {
        $columnName = 'sampleColumn';
        $columnsState = [
            $columnName => [
                ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];

        $this->assertStateIsSet($columnsState);

        $this->assertMetadataColumnsUpdated($columnName, $columnsState);

        $this->assertInitialStateIsSet($defaultState = ['sampleDefaultState']);

        $this->assertMetadataDefaultGridViewUpdated($defaultState);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    /**
     * @param $defaultState
     */
    private function assertMetadataDefaultGridViewUpdated($defaultState): void
    {
        $this->metadataObject
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn([['name' => GridViewsExtension::DEFAULT_VIEW_ID]]);

        $this->metadataObject
            ->expects(self::at(6))
            ->method('offsetSetByPath')
            ->with(
                sprintf('[gridViews][views][%s][%s]', 0, 'columns'),
                $defaultState
            );
    }

    public function testVisitMetadataWhenNoColumnName(): void
    {
        $columnsState = [
            'sampleColumn' => [
                ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true
            ],
        ];
        $metadata = [['sampleProperty' => 'sampleValue']];

        $this->assertMetadataNotUpdated($columnsState, $metadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }

    /**
     * @param array $columnsState
     * @param array $metadata
     */
    private function assertMetadataNotUpdated(array $columnsState, array $metadata): void
    {
        $this->columnsStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration)
            ->willReturn($columnsState);

        $this->columnsStateProvider
            ->expects(self::once())
            ->method('getDefaultState')
            ->with($this->datagridConfiguration)
            ->willReturn($defaultState = ['sampleDefaultState']);

        $this->metadataObject
            ->expects(self::once())
            ->method('offsetGetOr')
            ->with('columns', [])
            ->willReturn($metadata);

        $this->metadataObject
            ->expects(self::exactly(2))
            ->method('offsetAddToArray')
            ->withConsecutive(
                ['state', [Configuration::COLUMNS_KEY => $columnsState]],
                ['initialState', [Configuration::COLUMNS_KEY => $defaultState]]
            );

        $this->metadataObject
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->with('[gridViews][views]', [])
            ->willReturn([['name' => GridViewsExtension::DEFAULT_VIEW_ID]]);

        $this->metadataObject
            ->expects(self::once())
            ->method('offsetSetByPath')
            ->with(
                sprintf('[gridViews][views][%s][%s]', 0, 'columns'),
                $defaultState
            );
    }

    public function testVisitMetadataWhenNoColumnInState(): void
    {
        $columnsState = [
            'anotherColumn' => [
                ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                ColumnsStateProvider::RENDER_FIELD_NAME => true,
            ],
        ];
        $metadata = [['name' => 'sampleColumn']];

        $this->assertMetadataNotUpdated($columnsState, $metadata);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($this->datagridConfiguration, $this->metadataObject);
    }
}
