<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\AbstractSelectedFieldsProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractSelectedFieldsProviderTestCase extends TestCase
{
    protected DatagridStateProviderInterface&MockObject $datagridStateProvider;
    protected DatagridConfiguration&MockObject $datagridConfiguration;
    protected ParameterBag&MockObject $parameterBag;
    protected AbstractSelectedFieldsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->datagridStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $this->parameterBag = $this->createMock(ParameterBag::class);
    }

    abstract protected function expectGetConfiguration(array $configuration): void;

    /**
     * @dataProvider getSelectedFieldsDataProvider
     */
    public function testGetSelectedFields(array $state, array $configuration, array $expectedSelectedFields): void
    {
        $this->datagridStateProvider->expects(self::once())
            ->method('getStateFromParameters')
            ->with($this->datagridConfiguration, $this->parameterBag)
            ->willReturn($state);

        $this->expectGetConfiguration($configuration);

        $selectedFields = $this->provider->getSelectedFields($this->datagridConfiguration, $this->parameterBag);
        self::assertEquals($expectedSelectedFields, $selectedFields);
    }

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
}
