<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Grid\Extension;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\FilterBundle\Filter\DateFilterUtility;
use Oro\Bundle\FilterBundle\Filter\DateTimeRangeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\EventListener\DateFilterSubscriber;
use Oro\Bundle\FilterBundle\Form\Type\DateRangeType;
use Oro\Bundle\FilterBundle\Form\Type\DateTimeRangeType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierProvider;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\CalendarFactoryInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs\OrmDatasourceExtension;
use Oro\Bundle\TestFrameworkBundle\Test\Form\MutableFormEventSubscriber;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class OrmDatasourceExtensionTest extends OrmTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    protected function setUp()
    {
        $configManager = $this->createMock(ConfigManager::class);
        $calendarFactory = $this->createMock(CalendarFactoryInterface::class);
        $localizationManager = $this->createMock(LocalizationManager::class);

        $translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));
        $localeSettings = new LocaleSettings($configManager, $calendarFactory, $localizationManager);

        $mock = $this->createMock(DateFilterSubscriber::class);

        $subscriber = new MutableFormEventSubscriber($mock);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                array(
                    new PreloadedExtension(
                        array(
                            'oro_type_text_filter'           => new TextFilterType($translator),
                            'oro_type_datetime_range_filter' =>
                                new DateTimeRangeFilterType($translator, new DateModifierProvider(), $subscriber),
                            'oro_type_date_range_filter'     =>
                                new DateRangeFilterType($translator, new DateModifierProvider(), $subscriber),
                            'oro_type_datetime_range'        => new DateTimeRangeType($localeSettings),
                            'oro_type_date_range'            => new DateRangeType($localeSettings),
                            'oro_type_filter'                => new FilterType($translator),
                        ),
                        array()
                    ),
                    new CsrfExtension(
                        $this->createMock('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')
                    )
                )
            )
            ->getFormFactory();
    }

    /**
     * @dataProvider visitDatasourceProvider
     */
    public function testVisitDatasource($source, $expected)
    {
        $qb = new QueryBuilder($this->getTestEntityManager());
        $qb->select(['user.id', 'user.name as user_name', 'user.status as user_status'])
            ->from('Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser', 'user')
            ->join('user.address', 'address');

        $manager = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())
            ->method('createFilter')
            ->will(
                $this->returnCallback(
                    function ($name, $params) {
                        return $this->createFilter($name, $params);
                    }
                )
            );

        $extension  = new OrmDatasourceExtension(new RestrictionBuilder($manager));
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $config = DatagridConfiguration::create($source);
        $config->setName('test_grid');

        $extension->visitDatasource($config, $datasource);
        $result  = $qb->getDQL();
        $counter = 0;
        $result  = preg_replace_callback(
            '/(:[a-z]+)(\d+)/',
            function ($matches) use (&$counter) {
                return $matches[1] . (++$counter);
            },
            $result
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function visitDatasourceProvider()
    {
        return [
            'test with group and simple restrictions' => [
                'source'   => [
                    'source' => [
                        'query_config' => [
                            'filters' => [
                                [
                                    'column'      => 'user_name',
                                    'filter'      => 'string',
                                    'filterData'  => [
                                        'type'  => '2',
                                        'value' => 'test_user_name'
                                    ],
                                    'columnAlias' => 'user_name'
                                ],
                                'AND',
                                [
                                    [
                                        'column'     => 'user_status',
                                        'filter'     => 'datetime',
                                        'filterData' => [
                                            'type'  => '2',
                                            'value' => [
                                                'start' => '2013-11-20 10:30',
                                                'end'   => '2013-11-25 11:30',
                                            ]
                                        ]
                                    ],
                                    'AND',
                                    [
                                        [
                                            [
                                                'column'      => 'address.country',
                                                'filter'      => 'string',
                                                'filterData'  => [
                                                    'type'  => '1',
                                                    'value' => 'test_address_country'
                                                ],
                                                'columnAlias' => 'address_country'
                                            ],
                                            'OR',
                                            [
                                                'column'     => 'address.city',
                                                'filter'     => 'string',
                                                'filterData' => [
                                                    'type'  => '1',
                                                    'value' => 'test_address_city'
                                                ]
                                            ],
                                        ],
                                        'OR',
                                        [
                                            'column'     => 'address.zip',
                                            'filter'     => 'string',
                                            'filterData' => [
                                                'type'  => '1',
                                                'value' => 'address_zip'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' =>
                    'SELECT user.id, user.name as user_name, user.status as user_status '
                    . 'FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser user '
                    . 'INNER JOIN user.address address '
                    . 'WHERE user_name NOT LIKE :string1 AND ('
                    . '(user_status < :datetime2 OR user_status >= :datetime3) '
                    . 'AND (address.country LIKE :string4 '
                    . 'OR address.city LIKE :string5 OR '
                    . 'address.zip LIKE :string6))'
            ],
            'test with OR conditions' => [
                'source'   => [
                    'source' => [
                        'query_config' => [
                            'filters' => [
                                [
                                    'column'      => 'user_name',
                                    'filter'      => 'string',
                                    'filterData'  => [
                                        'type'  => '2',
                                        'value' => 'test_user_name'
                                    ],
                                    'columnAlias' => 'user_name'
                                ],
                                'OR',
                                [
                                    [
                                        'column'     => 'user_status',
                                        'filter'     => 'datetime',
                                        'filterData' => [
                                            'type'  => '2',
                                            'value' => [
                                                'start' => '2013-11-20 10:30',
                                                'end'   => '2013-11-25 11:30',
                                            ]
                                        ]
                                    ],
                                    'OR',
                                    [
                                        'column'      => 'address.country',
                                        'filter'      => 'string',
                                        'filterData'  => [
                                            'type'  => '1',
                                            'value' => 'test_address_country'
                                        ],
                                        'columnAlias' => 'address_country'
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' =>
                    'SELECT user.id, user.name as user_name, user.status as user_status '
                    . 'FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser user '
                    . 'INNER JOIN user.address address '
                    . 'WHERE user_name NOT LIKE :string1 OR ('
                    . 'user_status < :datetime2 OR user_status >= :datetime3 '
                    . 'OR address.country LIKE :string4)'
            ],
            'test with OR filters between simple and group conditions' => [
                'source'   => [
                    'source' => [
                        'query_config' => [
                            'filters' => [
                                [
                                    'column'      => 'user_name',
                                    'filter'      => 'string',
                                    'filterData'  => [
                                        'type'  => '2',
                                        'value' => 'test_user_name'
                                    ],
                                    'columnAlias' => 'user_name'
                                ],
                                'OR',
                                [
                                    [
                                        'column'     => 'user_status',
                                        'filter'     => 'datetime',
                                        'filterData' => [
                                            'type'  => '2',
                                            'value' => [
                                                'start' => '2013-11-20 10:30',
                                                'end'   => '2013-11-25 11:30',
                                            ]
                                        ]
                                    ],
                                    'AND',
                                    [
                                        'column'      => 'address.country',
                                        'filter'      => 'string',
                                        'filterData'  => [
                                            'type'  => '1',
                                            'value' => 'test_address_country'
                                        ],
                                        'columnAlias' => 'address_country'
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' =>
                    'SELECT user.id, user.name as user_name, user.status as user_status '
                    . 'FROM Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS\CmsUser user '
                    . 'INNER JOIN user.address address '
                    . 'WHERE user_name NOT LIKE :string1 OR ('
                    . '(user_status < :datetime2 OR user_status >= :datetime3) '
                    . 'AND address.country LIKE :string4)'
            ],
        ];
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name   A filter name
     * @param array  $params An additional parameters of a new filter
     *
     * @return FilterInterface
     * @throws \Exception
     */
    public function createFilter($name, array $params = null)
    {
        $defaultParams = [
            'type' => $name
        ];
        if ($params !== null && !empty($params)) {
            $params = array_merge($defaultParams, $params);
        }

        switch ($name) {
            case 'string':
                $filter = new StringFilter($this->formFactory, new FilterUtility());
                break;
            case 'datetime':
                $localeSetting = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
                    ->disableOriginalConstructor()->getMock();
                $localeSetting->expects($this->any())->method('getTimeZone')->will($this->returnValue('UTC'));
                $compiler = $this->getMockBuilder('Oro\Bundle\FilterBundle\Expression\Date\Compiler')
                    ->disableOriginalConstructor()->getMock();

                $filter = new DateTimeRangeFilter(
                    $this->formFactory,
                    new FilterUtility(),
                    new DateFilterUtility($localeSetting, $compiler)
                );
                break;
            default:
                throw new \Exception(sprintf('Not implementer in this test filter: "%s".', $name));
        }
        $filter->init($name, $params);

        return $filter;
    }
}
