<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\EventListener\RowSelectionListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowSelectionListenerTest extends TestCase
{
    private BuildAfter&MockObject $event;
    private DatagridInterface&MockObject $datagrid;
    private OrmDatasource&MockObject $datasource;
    private DoctrineHelper&MockObject $doctrineHelper;
    private RowSelectionListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = $this->createMock(BuildAfter::class);
        $this->datagrid = $this->createMock(DatagridInterface::class);
        $this->datasource = $this->createMock(OrmDatasource::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new RowSelectionListener($this->doctrineHelper);
    }

    /**
     * @dataProvider onBuildAfterDataProvider
     */
    public function testOnBuildAfterWorks(
        array $config,
        array $expectedConfig,
        array $classMetadataArray,
        array $expectedBindParameters
    ): void {
        $config = DatagridConfiguration::create($config);

        $classMetadata = new ClassMetadata('');
        $classMetadata->identifier = $classMetadataArray['identifier'];
        $classMetadata->fieldMappings = $classMetadataArray['fieldMappings'];

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadataForClass')
            ->willReturn($classMetadata);

        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($this->datasource);

        $this->datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        if (!empty($expectedBindParameters)) {
            $this->datasource->expects($this->once())
                ->method('bindParameters')
                ->with($expectedBindParameters);
        } else {
            $this->datasource->expects($this->never())
                ->method($this->anything());
        }

        $this->listener->onBuildAfter($this->event);

        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onBuildAfterDataProvider(): array
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
                        'jsmodules' => [
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
                        'type' => Connection::PARAM_INT_ARRAY
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                        'type' => Connection::PARAM_INT_ARRAY
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
                        'jsmodules' => [
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
                        'type' => Connection::PARAM_STR_ARRAY
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [''],
                        'type' => Connection::PARAM_STR_ARRAY
                    ],
                ],
            ],
            'append frontend module' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'jsmodules' => [
                            'some-module'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'jsmodules' => [
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
                        'type' => Connection::PARAM_INT_ARRAY
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                        'type' => Connection::PARAM_INT_ARRAY
                    ],
                ],
            ],
            'frontend module exist' => [
                'config' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'jsmodules' => [
                            'orodatagrid/js/datagrid/listener/column-form-listener'
                        ],
                    ],
                ],
                'expectedConfig' => [
                    'options' => [
                        'rowSelection' => [
                            'columnName' => 'foo'
                        ],
                        'jsmodules' => [
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
                        'type' => Connection::PARAM_INT_ARRAY
                    ],
                    'data_not_in' => [
                        'name' => RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'path' => ParameterBag::ADDITIONAL_PARAMETERS . '.'
                            . RowSelectionListener::GRID_PARAM_DATA_NOT_IN,
                        'default' => [0],
                        'type' => Connection::PARAM_INT_ARRAY
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

    public function testOnBuildAfterWorksSkippedForNotApplicableDatasource(): void
    {
        $this->event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        $datasource = $this->createMock(DatasourceInterface::class);

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->willReturn($datasource);

        $this->datagrid->expects($this->never())
            ->method('getConfig')
            ->willReturn($datasource);

        $this->datasource->expects($this->never())
            ->method($this->anything());

        $this->listener->onBuildAfter($this->event);
    }
}
