<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Provider\SelectedFields;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsFromColumnsProvider;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;

class SelectedFieldsFromColumnsProviderTest extends AbstractSelectedFieldsProviderTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new SelectedFieldsFromColumnsProvider($this->datagridStateProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectedFieldsDataProvider(): array
    {
        return [
                'state not empty, data_name is not defined' => [
                    'state' => ['sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true]],
                    'configuration' => ['sampleColumn1' => []],
                    'expectedSelectedFields' => ['sampleColumn1'],
                ],
                'state not empty, data_name is different from name' => [
                    'state' => ['sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true]],
                    'columns' => ['sampleColumn1' => ['data_name' => 'sampleField1']],
                    'expectedSelectedFields' => ['sampleField1'],
                ],
                'state not empty, data_name is same as name' => [
                    'state' => ['sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => true]],
                    'columns' => ['sampleColumn1' => ['data_name' => 'sampleColumn1']],
                    'expectedSelectedFields' => ['sampleColumn1'],
                ],
                'column is not renderable' => [
                    'state' => [
                        'sampleColumn1' => [ColumnsStateProvider::RENDER_FIELD_NAME => false],
                    ],
                    'columns' => [
                        'sampleColumn1' => ['data_name' => 'sampleColumn1'],
                    ],
                    'expectedSelectedFields' => [],
                ],
            ] + parent::getSelectedFieldsDataProvider();
    }

    /**
     * {@inheritdoc}
     */
    protected function mockGetConfiguration(array $configuration): void
    {
        $this->datagridConfiguration
            ->expects(self::once())
            ->method('offsetGet')
            ->with(Configuration::COLUMNS_KEY)
            ->willReturn($configuration);
    }
}
