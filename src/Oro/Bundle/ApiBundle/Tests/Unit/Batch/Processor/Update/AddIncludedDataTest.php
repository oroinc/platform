<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\JsonApiIncludeAccessor;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\AddIncludedData;

class AddIncludedDataTest extends BatchUpdateProcessorTestCase
{
    /** @var AddIncludedData */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new AddIncludedData();
    }

    private function getIncludedData(array $includedItems): IncludedData
    {
        $itemKeyBuilder = new ItemKeyBuilder();

        return new IncludedData(
            $itemKeyBuilder,
            new JsonApiIncludeAccessor($itemKeyBuilder),
            $this->createMock(FileLockManager::class),
            ['test.lock'],
            $includedItems
        );
    }

    public function testProcessWhenIncludedDataAlreadyAdded()
    {
        $this->context->setProcessed(AddIncludedData::OPERATION_NAME);
        $this->context->setIncludedData($this->getIncludedData([]));
        $this->context->setResult([['data' => ['type' => 'accounts']]]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoIncludedData()
    {
        $this->context->setResult([['data' => ['type' => 'accounts']]]);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }

    public function testProcessWhenNoData()
    {
        $this->context->setIncludedData($this->getIncludedData([]));
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }

    public function testProcessWhenDataDoesNotHaveRelationships()
    {
        $data = [
            ['data' => ['type' => 'accounts']],
            ['data' => ['type' => 'accounts']]
        ];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS
        ];

        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setIncludedData($this->getIncludedData([]));
        $this->processor->process($this->context);
        self::assertSame($data, $this->context->getResult());
        self::assertSame($processedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertTrue($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }

    public function testProcessWhenDataHaveNotIntersectedRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => '11']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '12'],
                                ['type' => 'contacts', 'id' => '13']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS
        ];
        $includedItems = [
            'contacts|11' => [['type' => 'contacts', 'id' => '11'], 100, 'included'],
            'contacts|12' => [['type' => 'contacts', 'id' => '12'], 101, 'included'],
            'contacts|13' => [['type' => 'contacts', 'id' => '13'], 102, 'included']
        ];
        $expectedData = $data;
        $expectedData[0]['included'][] = $includedItems['contacts|11'][0];
        $expectedData[1]['included'][] = $includedItems['contacts|12'][0];
        $expectedData[1]['included'][] = $includedItems['contacts|13'][0];

        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setIncludedData($this->getIncludedData($includedItems));
        $this->processor->process($this->context);
        self::assertSame($expectedData, $this->context->getResult());
        self::assertSame($processedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertTrue($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }

    public function testProcessWhenDataHaveIntersectedRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => '11']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '12'],
                                ['type' => 'contacts', 'id' => '11']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS
        ];
        $includedItems = [
            'contacts|11' => [['type' => 'contacts', 'id' => '11'], 100, 'included'],
            'contacts|12' => [['type' => 'contacts', 'id' => '12'], 101, 'included']
        ];
        $expectedData = $data;
        $expectedData[0]['included'][] = $includedItems['contacts|11'][0];
        $expectedProcessedItemStatuses = $processedItemStatuses;
        $expectedProcessedItemStatuses[1] = BatchUpdateItemStatus::HAS_ERRORS;

        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setIncludedData($this->getIncludedData($includedItems));
        $this->processor->process($this->context);
        self::assertSame($expectedData, $this->context->getResult());
        self::assertSame($expectedProcessedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertTrue($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessWhenIncludedDataHaveRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => '11']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '12'],
                                ['type' => 'contacts', 'id' => '16'],
                                ['type' => 'contacts', 'id' => '13']
                            ]
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '22']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => '14']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => '15']
                        ]
                    ]
                ]
            ]
        ];
        $processedItemStatuses = [
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS,
            BatchUpdateItemStatus::NO_ERRORS
        ];
        $includedItems = [
            'users|22'    => [
                [
                    'type'          => 'users',
                    'id'            => '22',
                    'relationships' => ['organization' => ['data' => ['type' => 'organizations', 'id' => '1000']]]
                ],
                100,
                'included'
            ],
            'contacts|11' => [
                [
                    'type'          => 'contacts',
                    'id'            => '11',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '21']]]
                ],
                101,
                'included'
            ],
            'contacts|12' => [
                [
                    'type'          => 'contacts',
                    'id'            => '12',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '22']]]
                ],
                102,
                'included'
            ],
            'contacts|13' => [
                [
                    'type'          => 'contacts',
                    'id'            => '13',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '23']]]
                ],
                103,
                'included'
            ],
            'contacts|14' => [
                [
                    'type'          => 'contacts',
                    'id'            => '14',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '22']]]
                ],
                104,
                'included'
            ],
            'contacts|15' => [
                [
                    'type'          => 'contacts',
                    'id'            => '15',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '21']]]
                ],
                105,
                'included'
            ],
            'contacts|16' => [
                [
                    'type'          => 'contacts',
                    'id'            => '16',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => '24']]]
                ],
                106,
                'included'
            ],
            'users|24'    => [
                [
                    'type'          => 'users',
                    'id'            => '24',
                    'relationships' => ['organization' => ['data' => ['type' => 'organizations', 'id' => '1000']]]
                ],
                107,
                'included'
            ]
        ];
        $expectedData = $data;
        $expectedData[0]['included'][] = $includedItems['contacts|11'][0];
        $expectedData[1]['included'][] = $includedItems['users|22'][0];
        $expectedData[1]['included'][] = $includedItems['contacts|12'][0];
        $expectedData[1]['included'][] = $includedItems['contacts|13'][0];
        $expectedData[1]['included'][] = $includedItems['contacts|16'][0];
        $expectedData[1]['included'][] = $includedItems['users|24'][0];
        $expectedData[4]['included'][] = $includedItems['contacts|15'][0];
        $expectedProcessedItemStatuses = $processedItemStatuses;
        $expectedProcessedItemStatuses[2] = BatchUpdateItemStatus::HAS_ERRORS;
        $expectedProcessedItemStatuses[3] = BatchUpdateItemStatus::HAS_ERRORS;

        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->context->setIncludedData($this->getIncludedData($includedItems));
        $this->processor->process($this->context);
        self::assertSame($expectedData, $this->context->getResult());
        self::assertSame($expectedProcessedItemStatuses, $this->context->getProcessedItemStatuses());
        self::assertTrue($this->context->isProcessed(AddIncludedData::OPERATION_NAME));
    }
}
