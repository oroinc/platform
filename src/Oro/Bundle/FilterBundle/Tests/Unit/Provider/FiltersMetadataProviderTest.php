<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\RawConfigurationProvider;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Provider\FiltersMetadataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class FiltersMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RawConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var FiltersMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(RawConfigurationProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.translated';
            });

        $this->provider = new FiltersMetadataProvider($this->configurationProvider, $translator);
    }

    public function testGetMetadataForFiltersWhenNoFilters(): void
    {
        $this->configurationProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEmpty(
            $this->provider->getMetadataForFilters([], $this->createMock(DatagridConfiguration::class))
        );
    }

    /**
     * @dataProvider getMetadataForFiltersDataProvider
     */
    public function testGetMetadataForFilters(
        array $filtersMetadata,
        array $rawConfig,
        array $expectedMetadata
    ): void {
        $gridConfig = DatagridConfiguration::createNamed('sample_grid', []);

        $this->configurationProvider->expects($this->once())
            ->method('getRawConfiguration')
            ->with($gridConfig->getName())
            ->willReturn($rawConfig);

        $filters = array_map(fn (array $metadata) => $this->createFilter($metadata), $filtersMetadata);

        $this->assertEquals(
            $expectedMetadata,
            $this->provider->getMetadataForFilters($filters, $gridConfig)
        );
    }

    public function getMetadataForFiltersDataProvider(): array
    {
        return [
            'with raw config' => [
                '$filtersMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label',
                        FilterUtility::TRANSLATABLE_KEY => true,
                    ],
                ],
                '$rawConfig' => ['sample_key' => 'sample_value'],
                '$expectedMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label.translated',
                        FilterUtility::TRANSLATABLE_KEY => true,
                        'cacheId' => null,
                    ],
                ],
            ],
            'with raw config and not lazy' => [
                '$filtersMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label',
                        FilterUtility::TRANSLATABLE_KEY => true,
                        'lazy' => false,
                    ],
                ],
                '$rawConfig' => ['sample_key' => 'sample_value'],
                '$expectedMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label.translated',
                        FilterUtility::TRANSLATABLE_KEY => true,
                        'cacheId' => null,
                        'lazy' => false,
                    ],
                ],
            ],
            'with raw config and lazy' => [
                '$filtersMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label',
                        FilterUtility::TRANSLATABLE_KEY => true,
                        'lazy' => true,
                    ],
                ],
                '$rawConfig' => ['filters' => ['columns' => ['sample_filter1' => ['options' => ['sample_options']]]]],
                '$expectedMetadata' => [
                    [
                        'name' => 'sample_filter1',
                        'label' => 'sample_filter.label.translated',
                        FilterUtility::TRANSLATABLE_KEY => true,
                        'cacheId' => 'cd5a9db4e72e346b10b7c79955ba1010',
                        'lazy' => true,
                    ],
                ],
            ],
        ];
    }

    private function createFilter(array $filterMetadata): FilterInterface
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects($this->once())
            ->method('getMetadata')
            ->willReturn($filterMetadata);

        return $filter;
    }
}
