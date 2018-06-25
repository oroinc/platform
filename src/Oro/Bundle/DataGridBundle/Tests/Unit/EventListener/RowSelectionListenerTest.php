<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\EventListener\RowSelectionListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class RowSelectionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BuildAfter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $datagrid;

    /**
     * @var OrmDatasource|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $datasource;

    /**
     * @var RowSelectionListener
     */
    protected $listener;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->event = $this->getMockBuilder(BuildAfter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->listener = new RowSelectionListener($this->doctrineHelper);
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     *
     * @param array $config
     * @param array $expectedConfig
     * @param array $classMetadataArray
     * @param array $expectedBindParameters
     */
    public function testOnBuildAfterWorks(
        array $config,
        array $expectedConfig,
        array $classMetadataArray,
        array $expectedBindParameters
    ) {
        $config = DatagridConfiguration::create($config);

        $classMetadata = new ClassMetadata('');
        $classMetadata->identifier = $classMetadataArray['identifier'];
        $classMetadata->fieldMappings = $classMetadataArray['fieldMappings'];

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadataForClass')
            ->willReturn($classMetadata);

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        if (!empty($expectedBindParameters)) {
            $this->datasource->expects($this->once())
                ->method('bindParameters')
                ->with($expectedBindParameters);
        } else {
            $this->datasource->expects($this->never())->method($this->anything());
        }

        $this->listener->onBuildAfter($this->event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onBuildAfterDataProvider()
    {
        return [
            'applicable config' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'classMetadata' => [
                    'identifier' => ['id'],
                    'fieldMappings' => [
                        'id' => [
                            'type' => 'integer',
                        ]
                    ]
                ],
                'expectedBindParameters' => [
                    'data_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . RowSelectionListener::GRID_PARAM_DATA_IN,
                        'default' => [0],
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                    ],
                ],
            ],
            'string based parameters' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ]
                    ],
                    'source' => ['query' => ['from' => [['table' => 'Entity1']]]],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                    'source' => ['query' => ['from' => [['table' => 'Entity1']]]],
                ],
                'classMetadata' => [
                    'identifier' => ['code'],
                    'fieldMappings' => [
                        'code' => [
                            'type' => 'string',
                        ]
                    ]
                ],
                'expectedBindParameters' => [
                    'data_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . RowSelectionListener::GRID_PARAM_DATA_IN,
                        'default' => [''],
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [''],
                    ],
                ],
            ],
            'append frontend module' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'some-module'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'some-module',
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'classMetadata' => [
                    'identifier' => [],
                    'fieldMappings' => [],
                ],
                'expectedBindParameters' => [
                    'data_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . RowSelectionListener::GRID_PARAM_DATA_IN,
                        'default' => [0],
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                    ],
                ],
            ],
            'frontend module exist' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'requireJSModules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'classMetadata' => [
                    'identifier' => [],
                    'fieldMappings' => [],
                ],
                'expectedBindParameters' => [
                    'data_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.' . RowSelectionListener::GRID_PARAM_DATA_IN,
                        'default' => [0],
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                    ],
                ],
            ],
            'not applicable config' => [
                'config' => [
                    'options' => [],
                ],
                'expectedConfig' => [
                    'options' => [],
                ],
                'classMetadata' => [
                    'identifier' => [],
                    'fieldMappings' => [],
                ],
                'expectedBindParameters' => [],
            ],
        ];
    }

    public function testOnBuildAfterWorksSkippedForNotApplicableDatasource()
    {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->will($this->returnValue($this->datagrid));

        $datasource = $this->createMock(DatasourceInterface::class);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($datasource));

        $this->datagrid->expects($this->never())
            ->method('getConfig')
            ->will($this->returnValue($datasource));

        $this->datasource->expects($this->never())->method($this->anything());

        $this->listener->onBuildAfter($this->event);
    }
}
