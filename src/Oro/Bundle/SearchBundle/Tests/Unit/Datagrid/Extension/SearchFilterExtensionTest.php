<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Extension\SearchFilterExtension;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\IndexerQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchFilterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var OrmFilterExtension */
    protected $extension;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $configurationProvider = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Provider\ConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new SearchFilterExtension($configurationProvider, $this->translator);
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->extension->setParameters(new ParameterBag($input));

        self::assertEquals($expected, $this->extension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return [
            'empty'    => [
                'input'    => [],
                'expected' => [],
            ],
            'regular'  => [
                'input'    => [
                    AbstractFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ],
                'expected' => [
                    AbstractFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ]
            ],
            'minified' => [
                'input'    => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            'firstName' => ['value' => 'John'],
                        ],
                    ]
                ],
                'expected' => [
                    ParameterBag::MINIFIED_PARAMETERS          => [
                        OrmFilterExtension::MINIFIED_FILTER_PARAM => [
                            'firstName' => ['value' => 'John'],
                        ],
                    ],
                    AbstractFilterExtension::FILTER_ROOT_PARAM => [
                        'firstName' => ['value' => 'John'],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $gridConfig
     * @param array $parameters
     * @param mixed $expected
     *
     * @dataProvider valuesDataProvider
     */
    public function testGetValuesToApply(array $gridConfig, array $parameters, $expected)
    {
        $gridConfig = DatagridConfiguration::create($gridConfig);

        $innerQuery = $this->getMock(Query::class);

        $query = $this->getMockBuilder(IndexerQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuery'])
            ->getMock();

        $query->method('getQuery')->willReturn($innerQuery);

        $dataSource = $this
            ->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource->method('getQuery')
            ->will($this->returnValue($query));

        $filter = $this->getMock('Oro\Bundle\FilterBundle\Filter\FilterInterface');
        $filter
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('filter'));

        $form = $this
            ->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $form
            ->expects($this->any())
            ->method('isSubmitted')
            ->will($this->returnValue(false));

        if (is_array($expected)) {
            $form
                ->expects($this->once())
                ->method('submit')
                ->with($this->equalTo($expected));
        } else {
            $form
                ->expects($this->never())
                ->method('submit');
        }

        $filter
            ->expects($this->any())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->extension->addFilter('string', $filter);
        $this->extension->setParameters(new ParameterBag($parameters));
        $this->extension->visitDatasource($gridConfig, $dataSource);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function valuesDataProvider()
    {
        return [
            'default_filter_no_parameters_modified_grid' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    PagerInterface::PAGER_ROOT_PARAM => [
                        PagerInterface::DISABLED_PARAM => true
                    ]
                ],
                ['value' => 'filter-value']
            ],
            'default_filter_no_parameters_new_grid' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [],
                ['value' => 'filter-value']
            ],
            'default_filter_no_parameters_grid_with_default_view' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    ParameterBag::ADDITIONAL_PARAMETERS => [
                        GridViewsExtension::VIEWS_PARAM_KEY => GridViewsExtension::DEFAULT_VIEW_ID
                    ]
                ],
                ['value' => 'filter-value']
            ],
            'parametrized_without_default_filters' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => [
                            'value' => 'filter-value'
                        ]
                    ]
                ],
                ['value' => 'filter-value']
            ],
            'override_default_filters' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => [
                            'value' => 'override-value'
                        ]
                    ]
                ],
                ['value' => 'override-value']
            ],
            'empty_parametrized_overrides_default_filter' => [
                [
                    'filters' => [
                        'columns' => [
                            'filter' => [
                                'type' => 'string'
                            ],
                        ],
                        'default' => [
                            'filter' => [
                                'value' => 'filter-value'
                            ]
                        ]
                    ]
                ],
                [
                    OrmFilterExtension::FILTER_ROOT_PARAM => [
                        'filter' => []
                    ]
                ],
                []
            ]
        ];
    }
}
