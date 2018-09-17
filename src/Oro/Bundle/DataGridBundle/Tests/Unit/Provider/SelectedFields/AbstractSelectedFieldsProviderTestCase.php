<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\AbstractSelectedFieldsProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;

abstract class AbstractSelectedFieldsProviderTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridStateProvider;

    /** @var AbstractSelectedFieldsProvider */
    protected $provider;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridConfiguration;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $parameterBag;

    protected function setUp()
    {
        $this->datagridStateProvider = $this->createMock(DatagridStateProviderInterface::class);

        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->parameterBag = $this->createMock(ParameterBag::class);
    }

    /**
     * @dataProvider getSelectedFieldsDataProvider
     *
     * @param array $state
     * @param array $configuration
     * @param array $expectedSelectedFields
     */
    public function testGetSelectedFields(array $state, array $configuration, array $expectedSelectedFields): void
    {
        $this->mockGetState($state);
        $this->mockGetConfiguration($configuration);

        $selectedFields = $this->provider->getSelectedFields($this->datagridConfiguration, $this->parameterBag);
        self::assertEquals($expectedSelectedFields, $selectedFields);
    }

    /**
     * @return array
     */
    public function getSelectedFieldsDataProvider(): array
    {
        return [
            'empty state, empty config' => [
                'state' => [],
                'configuration' => [],
                'expectedSelectedFields' => [],
            ],
            'empty state, not empty config' => [
                'state' => [],
                'configuration' => ['sampleItem1' => ['data_name' => 'sampleField1']],
                'expectedSelectedFields' => [],
            ],
            'state not empty, data_name is not defined' => [
                'state' => ['sampleItem1' => 'sampleState'],
                'configuration' => ['sampleItem1' => []],
                'expectedSelectedFields' => ['sampleItem1'],
            ],
            'state not empty, data_name is different from name' => [
                'state' => ['sampleItem1' => 'sampleState'],
                'configuration' => ['sampleItem1' => ['data_name' => 'sampleField1']],
                'expectedSelectedFields' => ['sampleField1'],
            ],
            'state not empty, data_name is same as name' => [
                'state' => ['sampleItem1' => 'ASC'],
                'configuration' => ['sampleItem1' => ['data_name' => 'sampleItem1']],
                'expectedSelectedFields' => ['sampleItem1'],
            ],
        ];
    }

    /**
     * @param array $state
     */
    protected function mockGetState(array $state): void
    {
        $this->datagridStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with($this->datagridConfiguration, $this->parameterBag)
            ->willReturn($state);
    }

    /**
     * @param array $configuration
     *
     * @return void
     */
    abstract protected function mockGetConfiguration(array $configuration): void;
}
