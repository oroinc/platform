<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\JsonApiIncludeAccessor;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\UpdateRelationshipsToProcessedIncludedEntities;

class UpdateRelationshipsToProcessedIncludedEntitiesTest extends BatchUpdateProcessorTestCase
{
    /** @var UpdateRelationshipsToProcessedIncludedEntities */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new UpdateRelationshipsToProcessedIncludedEntities();
    }

    private function getIncludedData(array $items, array $processedItems): IncludedData
    {
        $itemKeyBuilder = new ItemKeyBuilder();

        return new IncludedData(
            $itemKeyBuilder,
            new JsonApiIncludeAccessor($itemKeyBuilder),
            $this->createMock(FileLockManager::class),
            ['test.lock'],
            $items,
            $processedItems
        );
    }

    public function testProcessWhenRelationshipsAlreadyUpdated()
    {
        $data = [
            [
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'contact_1'],
                                ['type' => 'contacts', 'id' => 'contact_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'contacts',
                        'id'            => 'contact_1',
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'users', 'id' => 'user_1'],
                                    ['type' => 'users', 'id' => 'user_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $items = [
            'users|user_1' => [['type' => 'users', 'id' => 'user_1'], 100, 'included']
        ];
        $processedItems = [
            'contacts|contact_2' => 12,
            'users|user_2'       => 22
        ];
        $processedItemStatuses = [BatchUpdateItemStatus::NOT_PROCESSED];

        $this->context->setProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME);
        $this->context->setIncludedData($this->getIncludedData($items, $processedItems));
        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertSame($data, $this->context->getResult());
    }

    public function testProcess()
    {
        $data = [
            [
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'contact_1'],
                                ['type' => 'contacts', 'id' => 'contact_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'contacts',
                        'id'            => 'contact_1',
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'users', 'id' => 'user_1'],
                                    ['type' => 'users', 'id' => 'user_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $items = [
            'users|user_1' => [['type' => 'users', 'id' => 'user_1'], 100, 'included']
        ];
        $processedItems = [
            'contacts|contact_2' => 12,
            'users|user_2'       => 22
        ];
        $processedItemStatuses = [BatchUpdateItemStatus::NOT_PROCESSED];

        $expectedData = $data;
        $expectedData[0]['data']['relationships']['contacts']['data'][1]['id'] = '12';
        $expectedData[0]['included'][0]['relationships']['users']['data'][1]['id'] = '22';

        $this->context->setIncludedData($this->getIncludedData($items, $processedItems));
        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME));
        self::assertSame($expectedData, $this->context->getResult());
    }

    public function testProcessWhenNoProcessedItems()
    {
        $data = [
            [
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'contact_1'],
                                ['type' => 'contacts', 'id' => 'contact_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'contacts',
                        'id'            => 'contact_1',
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'users', 'id' => 'user_1'],
                                    ['type' => 'users', 'id' => 'user_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $processedItemStatuses = [BatchUpdateItemStatus::NOT_PROCESSED];

        $this->context->setIncludedData($this->getIncludedData([], []));
        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME));
        self::assertSame($data, $this->context->getResult());
    }

    public function testProcessWhenBatchItemAlreadyProcessed()
    {
        $data = [
            [
                'data'     => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'contact_1'],
                                ['type' => 'contacts', 'id' => 'contact_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'contacts',
                        'id'            => 'contact_1',
                        'relationships' => [
                            'users' => [
                                'data' => [
                                    ['type' => 'users', 'id' => 'user_1'],
                                    ['type' => 'users', 'id' => 'user_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $items = [
            'users|user_1' => [['type' => 'users', 'id' => 'user_1'], 100, 'included']
        ];
        $processedItems = [
            'contacts|contact_2' => 12,
            'users|user_2'       => 22
        ];
        $processedItemStatuses = [BatchUpdateItemStatus::NO_ERRORS];

        $this->context->setIncludedData($this->getIncludedData($items, $processedItems));
        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertTrue($this->context->isProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME));
        self::assertSame($data, $this->context->getResult());
    }

    public function testProcessWithoutIncludedData()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'contact_1'],
                                ['type' => 'contacts', 'id' => 'contact_2']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $processedItemStatuses = [BatchUpdateItemStatus::NOT_PROCESSED];

        $this->context->setResult($data);
        $this->context->setProcessedItemStatuses($processedItemStatuses);
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME));
        self::assertSame($data, $this->context->getResult());
    }

    public function testProcessWhenDataNotLoaded()
    {
        $this->context->setIncludedData($this->getIncludedData([], []));
        $this->processor->process($this->context);
        self::assertFalse($this->context->isProcessed(UpdateRelationshipsToProcessedIncludedEntities::OPERATION_NAME));
    }
}
