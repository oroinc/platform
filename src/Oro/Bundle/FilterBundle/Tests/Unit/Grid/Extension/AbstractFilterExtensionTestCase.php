<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractFilterExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    protected const DATAGRID_NAME = 'sampleDatagridName';
    protected const FILTER_NAME = 'sampleFilter1';
    protected const FILTER_TYPE = 'sampleFilterType1';
    protected const FILTER_LABEL = 'SampleFilterLabel1';
    protected const TRANSLATED_FILTER_LABEL = 'TranslatedFilterLabel1';

    /** @var ConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configurationProvider;

    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $filtersStateProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var AbstractFilterExtension */
    protected $extension;

    /** @var ParameterBag|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridParameters;

    protected function setUp()
    {
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);
        $this->filtersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->datagridParameters = $this->createMock(ParameterBag::class);
    }

    public function testProcessConfigs(): void
    {
        $datagridConfiguration = $this->createDatagridConfig([
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        FilterUtility::TYPE_KEY => 'sampleFilterType1',
                        FilterUtility::DATA_NAME_KEY => 'sampleDataName',
                    ],
                ],
            ],
        ]);

        $this->extension->addFilter('sampleFilterType1', $this->createMock(FilterInterface::class));
        $this->extension->processConfigs($datagridConfiguration);

        $filtersNormalized = [
            'columns' => [
                self::FILTER_NAME => [
                    FilterUtility::TYPE_KEY => 'sampleFilterType1',
                    FilterUtility::DATA_NAME_KEY => 'sampleDataName',
                    FilterUtility::ENABLED_KEY => true,
                    FilterUtility::VISIBLE_KEY => true,
                    FilterUtility::TRANSLATABLE_KEY => true,
                    FilterUtility::FORCE_LIKE_KEY => false,
                    FilterUtility::CASE_INSENSITIVE_KEY => true,
                    FilterUtility::MIN_LENGTH_KEY => 0,
                    FilterUtility::MAX_LENGTH_KEY => PHP_INT_MAX,
                ],
            ],
            'default' => [],
        ];

        self::assertEquals(
            $filtersNormalized,
            $datagridConfiguration->offsetGetByPath(Configuration::FILTERS_PATH)
        );
    }

    public function testVisitMetadataRequiredModuleIsAdded(): void
    {
        $datagridConfig = $this->createDatagridConfig(['name' => self::DATAGRID_NAME]);

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));

        self::assertArraySubset(
            [MetadataObject::REQUIRED_MODULES_KEY => ['orofilter/js/datafilter-builder']],
            $metadata->toArray()
        );
    }

    public function testVisitMetadataWhenFilterIsDisabled(): void
    {
        $datagridConfig = $this->createDatagridConfig([
            'name' => self::DATAGRID_NAME,
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                        PropertyInterface::DISABLED_KEY => 1,
                    ]
                ]
            ],
        ]);

        $filter = $this->createMock(FilterInterface::class);
        $filter
            ->expects(self::never())
            ->method('init');

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));
    }

    /**
     * @dataProvider visitMetadataForFilterLabelDataProvider
     *
     * @param array $datagridConfigArray
     * @param array $expectedFilterConfig
     */
    public function testVisitMetadataFilterLabelIsSet(
        array $datagridConfigArray,
        array $expectedFilterConfig
    ): void {
        $datagridConfig = $this->createDatagridConfig($datagridConfigArray);

        $filter = $this->createMock(FilterInterface::class);
        $filter
            ->expects(self::once())
            ->method('init')
            ->with(self::FILTER_NAME, $expectedFilterConfig);

        $filter
            ->expects(self::once())
            ->method('getMetadata')
            ->willReturn([]);

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $this->createMetadataObject([]));
    }

    /**
     * @return array
     */
    public function visitMetadataForFilterLabelDataProvider(): array
    {
        return [
            'filter label is set' => [
                'datagridConfigArray' => [
                    'name' => self::DATAGRID_NAME,
                    FormatterConfiguration::COLUMNS_KEY => [
                        self::FILTER_NAME => [
                            'label' => 'SampleColumnLabel1'
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FILTER_NAME => [
                                'label' => self::FILTER_LABEL,
                                FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                            ],
                        ],
                    ],
                ],
                'expectedFilterConfig' => [
                    'label' => self::FILTER_LABEL,
                    FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                ],
            ],
            'filter label is inherited from column label' => [
                'datagridConfigArray' => [
                    'name' => self::DATAGRID_NAME,
                    FormatterConfiguration::COLUMNS_KEY => [
                        self::FILTER_NAME => [
                            'label' => 'SampleColumnLabel1'
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FILTER_NAME => [FilterUtility::TYPE_KEY => self::FILTER_TYPE],
                        ],
                    ],
                ],
                'expectedFilterConfig' => [
                    'label' => 'SampleColumnLabel1',
                    FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                ],
            ],
        ];
    }

    public function testVisitMetadataStateIsSetWhenNoFilters(): void
    {
        $datagridConfig = $this->createDatagridConfig(['name' => self::DATAGRID_NAME]);

        $this->mockStateProviders(
            $filtersState = [self::FILTER_NAME => ['value' => 'sampleValue1']],
            $defaultFiltersState = [self::FILTER_NAME => ['value' => 'sampleValue0']]
        );

        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));

        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    /**
     * @param array $filtersState
     * @param array $defaultFiltersState
     */
    protected function mockStateProviders(array $filtersState, array $defaultFiltersState): void
    {
        $this->filtersStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with(self::isInstanceOf(DatagridConfiguration::class), $this->datagridParameters)
            ->willReturn($filtersState);

        $this->filtersStateProvider
            ->expects(self::once())
            ->method('getDefaultState')
            ->with(self::isInstanceOf(DatagridConfiguration::class))
            ->willReturn($defaultFiltersState);
    }

    public function testVisitMetadataFilterOptionsAreResolved(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $metadata = $this->createMetadataObject([MetadataObject::LAZY_KEY => false]);
        $filter = $this->assertFilterInitialized();

        $filter
            ->expects(self::once())
            ->method('resolveOptions');

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::exactly(2))
            ->method('isValid')
            ->willReturn(true);

        $this->mockStateProviders(
            $filtersState = [self::FILTER_NAME => ['value' => 'sampleValue1']],
            $defaultFiltersState = [self::FILTER_NAME => ['value' => 'sampleValue0']]
        );

        $filter
            ->expects(self::once())
            ->method('getMetadata')
            ->willReturn([]);

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata);

        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    /**
     * @param FilterInterface|\PHPUnit\Framework\MockObject\MockObject $filter
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    protected function mockFilterForm(FilterInterface $filter)
    {
        $filter
            ->expects(self::atLeastOnce())
            ->method('getForm')
            ->willReturn($filterForm = $this->createMock(FormInterface::class));

        $filterForm
            ->expects(self::atLeastOnce())
            ->method('submit');

        return $filterForm;
    }

    /**
     * @return DatagridConfiguration
     */
    protected function createCommonDatagridConfig(): DatagridConfiguration
    {
        $datagridConfig = $this->createDatagridConfig([
            'name' => self::DATAGRID_NAME,
            'filters' => [
                'columns' => [
                    self::FILTER_NAME => [
                        'label' => self::FILTER_LABEL,
                        FilterUtility::TYPE_KEY => self::FILTER_TYPE,
                    ],
                ],
            ],
        ]);

        return $datagridConfig;
    }

    /**
     * @return FilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function assertFilterInitialized(): FilterInterface
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter
            ->expects(self::once())
            ->method('init')
            ->with(self::FILTER_NAME, ['label' => self::FILTER_LABEL, FilterUtility::TYPE_KEY => self::FILTER_TYPE]);

        $filter
            ->method('getName')
            ->willReturn(self::FILTER_NAME);

        return $filter;
    }

    public function testVisitMetadataNoStates(): void
    {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $filter
            ->expects(self::never())
            ->method('resolveOptions');

        $filter
            ->expects(self::never())
            ->method('setFilterState');

        $this->mockStateProviders($filtersState = [], $defaultFiltersState = []);

        $filter
            ->expects(self::once())
            ->method('getMetadata')
            ->willReturn([]);

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));

        self::assertEquals($filtersState, $metadata->offsetGetByPath('[state][filters]'));
        self::assertEquals($defaultFiltersState, $metadata->offsetGetByPath('[initialState][filters]'));
    }

    /**
     * @dataProvider visitMetadataNoStateDataProvider
     *
     * @param array $filtersState
     * @param array $defaultFiltersState
     * @param array $isValid
     * @param array $expectedMetadata
     */
    public function testVisitMetadataStatesAreValidated(
        array $filtersState,
        array $defaultFiltersState,
        array $isValid,
        array $expectedMetadata
    ): void {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $filter
            ->expects(self::never())
            ->method('resolveOptions');

        $filterForm = $this->mockFilterForm($filter);

        $filterForm
            ->expects(self::exactly(count($isValid)))
            ->method('isValid')
            ->willReturnOnConsecutiveCalls(...$isValid);

        $filter
            ->expects(self::once())
            ->method('setFilterState')
            ->with($filtersState[self::FILTER_NAME] ?? null);

        $this->mockStateProviders($filtersState, $defaultFiltersState);

        $filter
            ->expects(self::once())
            ->method('getMetadata')
            ->willReturn([]);

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));

        self::assertArraySubset($expectedMetadata, $metadata->toArray());
    }

    /**
     * @return array
     */
    public function visitMetadataNoStateDataProvider(): array
    {
        return [
            'default filter state is valid, filter state is not valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [true, false],
                'expectedMetadata' => [
                    'initialState' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue0']]],
                    'state' => ['filters' => []],
                ],
            ],
            'default filter state is not valid, filter state is valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [false, true],
                'expectedMetadata' => [
                    'initialState' => ['filters' => []],
                    'state' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue1']]],
                ],
            ],
            'default filter state and filter state are not valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [false, false],
                'expectedMetadata' => [
                    'initialState' => ['filters' => []],
                    'state' => ['filters' => []],
                ],
            ],
            'default filter state and filter state are valid' => [
                'filtersState' => [self::FILTER_NAME => ['value' => 'sampleValue1']],
                'defaultFiltersState' => [self::FILTER_NAME => ['value' => 'sampleValue0']],
                'isValid' => [true, true],
                'expectedMetadata' => [
                    'initialState' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue0']]],
                    'state' => ['filters' => [self::FILTER_NAME => ['value' => 'sampleValue1']]],
                ],
            ],
        ];
    }

    /**
     * @dataProvider visitMetadataFiltersMetadataUpdatedDataProvider
     *
     * @param array $filterMetadata
     * @param array $rawDatagridConfig
     * @param array $expectedMetadata
     */
    public function testVisitMetadataFiltersMetadataUpdated(
        array $filterMetadata,
        array $rawDatagridConfig,
        array $expectedMetadata
    ): void {
        $datagridConfig = $this->createCommonDatagridConfig();
        $filter = $this->assertFilterInitialized();

        $this->configurationProvider
            ->expects(self::once())
            ->method('isApplicable')
            ->with(self::DATAGRID_NAME)
            ->willReturn((bool)$rawDatagridConfig);

        $this->configurationProvider
            ->method('getRawConfiguration')
            ->with(self::DATAGRID_NAME)
            ->willReturn($rawDatagridConfig);

        $this->translator
            ->method('trans')
            ->willReturn(self::TRANSLATED_FILTER_LABEL);

        $filter
            ->expects(self::once())
            ->method('getMetadata')
            ->willReturn($filterMetadata);

        $this->mockStateProviders([], []);

        $this->extension->addFilter(self::FILTER_TYPE, $filter);
        $this->extension->setParameters($this->datagridParameters);
        $this->extension->visitMetadata($datagridConfig, $metadata = $this->createMetadataObject([]));

        self::assertArraySubset($expectedMetadata, $metadata->toArray());
    }

    /**
     * @return array
     */
    public function visitMetadataFiltersMetadataUpdatedDataProvider(): array
    {
        return [
            'cachedId is empty when filter is not lazy' => [
                'filterMetadata' => ['lazy' => false],
                'rawDatagridConfig' => [],
                'expectedMetadata' => [
                    'filters' => [
                        [
                            'lazy' => false,
                            'label' => '',
                            'cacheId' => null,
                        ]
                    ]
                ],
            ],
            'cachedId is not empty when filter is lazy' => [
                'filterMetadata' => ['lazy' => true, 'name' => self::FILTER_NAME],
                'rawDatagridConfig' => [
                    'filters' => [
                        'columns' => [
                            self::FILTER_NAME => [
                                'options' => ['sampleOption1' => 'sampleValue1'],
                            ],
                        ],
                    ],
                ],
                'expectedMetadata' => [
                    'filters' => [
                        [
                            'lazy' => true,
                            'cacheId' => '49562a7117e315def0e023a9008f844c',
                        ]
                    ]
                ],
            ],
            'label is translated when translatable is true' => [
                'filterMetadata' => [
                    'lazy' => false,
                    'label' => self::FILTER_LABEL,
                    FilterUtility::TRANSLATABLE_KEY => true,
                ],
                'rawDatagridConfig' => [],
                'expectedMetadata' => [
                    'filters' => [
                        [
                            'lazy' => false,
                            'label' => self::TRANSLATED_FILTER_LABEL,
                            FilterUtility::TRANSLATABLE_KEY => true,
                        ]
                    ]
                ],
            ],
            'label is not translated when translatable is false' => [
                'filterMetadata' => [
                    'lazy' => false,
                    'label' => self::FILTER_LABEL,
                    FilterUtility::TRANSLATABLE_KEY => false,
                ],
                'rawDatagridConfig' => [],
                'expectedMetadata' => [
                    'filters' => [
                        [
                            'lazy' => false,
                            'label' => self::FILTER_LABEL,
                            FilterUtility::TRANSLATABLE_KEY => false,
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @param array $datagridConfigArray
     *
     * @return DatagridConfiguration
     */
    protected function createDatagridConfig(array $datagridConfigArray): DatagridConfiguration
    {
        return DatagridConfiguration::create($datagridConfigArray);
    }

    /**
     * @param array $metadataArray
     *
     * @return MetadataObject
     */
    protected function createMetadataObject(array $metadataArray): MetadataObject
    {
        return MetadataObject::create($metadataArray);
    }
}
