<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Datagrid\EventListener;

use Oro\Bundle\CurrencyBundle\Datagrid\EventListener\ColumnConfigListener;
use Oro\Bundle\CurrencyBundle\Datagrid\InlineEditing\InlineEditColumnOptions\MultiCurrencyGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class ColumnConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY = 'Test:Entity';

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var ColumnConfigListener */
    private $columnListener;

    protected function setUp(): void
    {
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->columnListener = new ColumnConfigListener($this->entityClassResolver);
    }

    /**
     * @dataProvider buildBeforeEventDataProvider
     */
    public function testBuildBeforeEvent(array $inputConfig, array $expectedConfig)
    {
        $this->entityClassResolver->expects(self::once())
            ->method('getEntityClass')
            ->with(self::ENTITY)
            ->willReturn(self::ENTITY);
        $event = $this->createBuildBeforeEvent($inputConfig);
        $this->columnListener->onBuildBefore($event);
        $config = $event->getConfig()->toArray();
        $this->assertEquals($config, $expectedConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildBeforeEventDataProvider(): array
    {
        return [
            'Not applicable column type' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => 'some type'
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => 'some type'
                        ]
                    ],
                ]
            ],
            'Fully configured multi-currency options' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'value_field' => 'testValueField',
                                'currency_field' => 'testCurrencyField'
                            ]
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'value_field' => 'testValueField',
                                'currency_field' => 'testCurrencyField'
                            ],
                            'params' => [
                                'value' => 'testOriginalField',
                                'currency' => 'testCurrencyField'
                            ]
                        ]
                    ],
                ]
            ],
            'Not configured multi-currency options' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'test',
                                'value_field' => 'testValue',
                                'currency_field' => 'testCurrency'
                            ],
                            'params' => [
                                'value' => 'test',
                                'currency' => 'testCurrency'
                            ]
                        ]
                    ],
                ]
            ],
            'Not configured `original_field`' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'value_field' => 'testValueField',
                                'currency_field' => 'testCurrencyField'
                            ]
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'test',
                                'value_field' => 'testValueField',
                                'currency_field' => 'testCurrencyField'
                            ],
                            'params' => [
                                'value' => 'test',
                                'currency' => 'testCurrencyField'
                            ]
                        ]
                    ],
                ]
            ],
            'Not configured `value_field`' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'currency_field' => 'testCurrencyField'
                            ],
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'value_field'    => 'testValue',
                                'currency_field' => 'testCurrencyField'
                            ],
                            'params' => [
                                'value' => 'testOriginalField',
                                'currency' => 'testCurrencyField'
                            ]
                        ]
                    ],
                ]
            ],
            'Not configured `currency_field`' => [
                'inputConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'value_field' => 'testValueField'
                            ]
                        ]
                    ],
                ],
                'expectedConfig' => [
                    'source' => [
                        'type' => OrmDatasource::TYPE,
                        'query' => [
                            'from' => [['table' => self::ENTITY, 'alias' => self::ENTITY . 'Alias']]
                        ]
                    ],
                    'columns' => [
                        'test' => [
                            PropertyInterface::FRONTEND_TYPE_KEY => MultiCurrencyGuesser::MULTI_CURRENCY_TYPE,
                            MultiCurrencyGuesser::MULTI_CURRENCY_CONFIG => [
                                'original_field' => 'testOriginalField',
                                'value_field' => 'testValueField',
                                'currency_field' => 'testCurrency'
                            ],
                            'params' => [
                                'value' => 'testOriginalField',
                                'currency' => 'testCurrency'
                            ]
                        ]
                    ],
                ]
            ]
        ];
    }

    private function createBuildBeforeEvent(array $configuration): BuildBefore
    {
        $event = $this->createMock(BuildBefore::class);
        $event->expects($this->any())
            ->method('getConfig')
            ->willReturn(DatagridConfiguration::create($configuration));

        return $event;
    }
}
