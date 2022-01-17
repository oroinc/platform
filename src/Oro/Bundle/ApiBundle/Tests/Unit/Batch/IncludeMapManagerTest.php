<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Gaufrette\File;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\JsonApiIncludeAccessor;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\GaufretteBundle\FileManager;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IncludeMapManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ItemKeyBuilder */
    private $itemKeyBuilder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileLockManager */
    private $fileLockManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var IncludeMapManager */
    private $includeMapManager;

    protected function setUp(): void
    {
        $this->itemKeyBuilder = new ItemKeyBuilder();
        $this->fileManager = $this->createMock(FileManager::class);
        $this->fileLockManager = $this->createMock(FileLockManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->includeMapManager = new IncludeMapManager(
            $this->itemKeyBuilder,
            new FileNameProvider(),
            $this->fileLockManager,
            $this->logger
        );
    }

    public function testUpdateIncludedChunkIndexWhenIndexDoesNotExist()
    {
        $file1 = new ChunkFile('file1', 0, 0, 'included');
        $file2 = new ChunkFile('file2', 1, 2, 'included');

        $file1Content = '{"included":['
            . '{"type":"accounts","id":"a1","attributes":{"name":"a1"}},'
            . '{"type":"accounts","id":"a2","attributes":{"name":"a2"}}'
            . ']}';
        $file2Content = '{"included":['
            . '{"type":"accounts","id":"a3","attributes":{"name":"a3"}},'
            . '{"type":"accounts","id":"a4","attributes":{"name":"a4"}}'
            . ']}';
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_include_index', false)
            ->willReturn(null);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content]
            ]);

        $dataToSave = '{'
            . '"files":[["file1","included",0],["file2","included",2]],'
            . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1],"accounts|a3":[1,0],"accounts|a4":[1,1]}'
            . '}';
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($dataToSave, 'api_123_include_index');

        $errors = $this->includeMapManager->updateIncludedChunkIndex(
            $this->fileManager,
            123,
            new JsonApiIncludeAccessor($this->itemKeyBuilder),
            [$file1, $file2]
        );
        self::assertSame([], $errors);
    }

    public function testUpdateIncludedChunkIndexWhenIndexExists()
    {
        $file2 = new ChunkFile('file2', 1, 2, 'included');

        $file2Content = '{"included":['
            . '{"type":"accounts","id":"a3","attributes":{"name":"a3"}},'
            . '{"type":"accounts","id":"a4","attributes":{"name":"a4"}}'
            . ']}';
        $dataToLoad = '{'
            . '"files":[["file1","included",0]],'
            . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1]}'
            . '}';
        $indexFile = $this->createMock(File::class);
        $indexFile->expects(self::once())
            ->method('getContent')
            ->willReturn($dataToLoad);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_include_index', false)
            ->willReturn($indexFile);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('file2', true)
            ->willReturn($file2Content);

        $dataToSave = '{'
            . '"files":[["file1","included",0],["file2","included",2]],'
            . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1],"accounts|a3":[1,0],"accounts|a4":[1,1]}'
            . '}';
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($dataToSave, 'api_123_include_index');

        $errors = $this->includeMapManager->updateIncludedChunkIndex(
            $this->fileManager,
            123,
            new JsonApiIncludeAccessor($this->itemKeyBuilder),
            [$file2]
        );
        self::assertSame([], $errors);
    }

    public function testUpdateIncludedChunkIndexWhenSomeIncludedItemsHaveErrors()
    {
        $file1 = new ChunkFile('file1', 0, 0, 'included');
        $file2 = new ChunkFile('file2', 1, 2, 'included');
        $file3 = new ChunkFile('file3', 2, 4, 'included');

        $file1Content = '{"included":['
            . '{"type":"accounts","id":"a1","attributes":{"name":"a1"}},'
            . '{"type":"accounts","attributes":{"name":"a2"}}'
            . ']}';
        $file2Content = '{"included":['
            . '{"type":"accounts","id":null,"attributes":{"name":"a3"}},'
            . '{"type":"accounts","id":"a4","attributes":{"name":"a4"}}'
            . ']}';
        $file3Content = '{"included":['
            . '{"type":"accounts","id":"a1","attributes":{"name":"a1 (duplicate)"}}'
            . ']}';
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_include_index', false)
            ->willReturn(null);
        $this->fileManager->expects(self::exactly(3))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content],
                ['file3', true, $file3Content]
            ]);

        $dataToSave = '{'
            . '"files":[["file1","included",0],["file2","included",2],["file3","included",4]],'
            . '"items":{"accounts|a1":[0,0],"accounts|a4":[1,1]}'
            . '}';
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($dataToSave, 'api_123_include_index');

        $errors = $this->includeMapManager->updateIncludedChunkIndex(
            $this->fileManager,
            123,
            new JsonApiIncludeAccessor($this->itemKeyBuilder),
            [$file1, $file2, $file3]
        );
        self::assertSame(
            [
                ['included', 1, "The 'id' property is required"],
                ['included', 2, "The 'id' property should not be null"],
                ['included', 4, 'The item duplicates the item with the index 0']
            ],
            $errors
        );
    }

    public function testGetIncludedItemsWhenDataDoesNotHaveRelationships()
    {
        $data = [
            ['data' => ['type' => 'accounts']],
            ['data' => ['type' => 'accounts']]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);

        $this->fileLockManager->expects(self::never())
            ->method('acquireLock');
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData($this->itemKeyBuilder, $includeAccessor, $this->fileLockManager),
            $includedData
        );
    }

    public function testGetIncludedItemsWhenIncludeIndexLockCannotBeAcquired()
    {
        $includeIndexLockAttemptLimit = 3;
        $includeIndexLockLockWaitBetweenAttempts = 2;
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
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with(
                'api_123_include_index.lock',
                $includeIndexLockAttemptLimit,
                $includeIndexLockLockWaitBetweenAttempts
            )
            ->willReturn(false);
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');
        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Not possible to get included items now'
                . ' because the lock cannot be acquired for the "api_123_include_index" file.'
            );

        $this->includeMapManager->setReadLockAttemptLimit($includeIndexLockAttemptLimit);
        $this->includeMapManager->setReadLockWaitBetweenAttempts($includeIndexLockLockWaitBetweenAttempts);
        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertNull($includedData);
    }

    public function testGetIncludedItemsWhenLoadIndexDataFailed()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with('api_123_include_index.lock')
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_include_index')
            ->willThrowException(new \Exception('some error'));
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with('api_123_include_index.lock');

        $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
    }

    public function testGetIncludedItemsWhenIncludedItemsNotFound()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0]],'
                . '"items":{"contacts|c2":[0,0]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($includeIndexLockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile]
            ]);
        $this->fileManager->expects(self::never())
            ->method('getFileContent')
            ->with('file1', true);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData($this->itemKeyBuilder, $includeAccessor, $this->fileLockManager),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenDataHaveNotIntersectedRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
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
                                ['type' => 'contacts', 'id' => 'c2'],
                                ['type' => 'contacts', 'id' => 'c3']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0],["file2","included",3],["file3","included",6]],'
                . '"items":{'
                . '"users|u2":[0,0],"contacts|c1":[0,1],"contacts|c2":[0,2],'
                . '"contacts|c3":[1,0],"contacts|c4":[1,1],"contacts|c5":[1,2],'
                . '"contacts|c6":[2,0]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');
        $file1Content = '{"included":['
            . '{"type":"users","id":"u2"},'
            . '{"type":"contacts","id":"c1"},'
            . '{"type":"contacts","id":"c2"}'
            . ']}';
        $file2Content = '{"included":['
            . '{"type":"contacts","id":"c3"},'
            . '{"type":"contacts","id":"c4"},'
            . '{"type":"contacts","id":"c5"}'
            . ']}';
        $file1LockFileName = 'file1.lock';
        $file2LockFileName = 'file2.lock';
        $expectedIncludedItems = [
            'contacts|c1' => [['type' => 'contacts', 'id' => 'c1'], 1, 'included'],
            'contacts|c2' => [['type' => 'contacts', 'id' => 'c2'], 2, 'included'],
            'contacts|c3' => [['type' => 'contacts', 'id' => 'c3'], 3, 'included']
        ];
        $expectedProcessedItems = [];

        $this->fileLockManager->expects(self::exactly(3))
            ->method('acquireLock')
            ->withConsecutive([$includeIndexLockFileName], [$file1LockFileName], [$file2LockFileName])
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('["contacts|c1","contacts|c2","contacts|c3"]', 'api_123_include_index_linked');
        $this->fileManager->expects(self::exactly(3))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile],
                ['api_123_include_index_linked', false, null]
            ]);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content]
            ]);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData(
                $this->itemKeyBuilder,
                $includeAccessor,
                $this->fileLockManager,
                [$file1LockFileName, $file2LockFileName],
                $expectedIncludedItems,
                $expectedProcessedItems
            ),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenDataHaveIntersectedRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
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
                                ['type' => 'contacts', 'id' => 'c2'],
                                ['type' => 'contacts', 'id' => 'c1']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0]],'
                . '"items":{"users|u2":[0,0],"contacts|c1":[0,1],"contacts|c2":[0,2],"contacts|c3":[0,3]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');
        $linkedIndexDataFile = $this->createMock(File::class);
        $linkedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('["contacts|c1","contacts|c2","contacts|c3"]');
        $file1Content = '{"included":['
            . '{"type":"users","id":"u2"},'
            . '{"type":"contacts","id":"c1"},'
            . '{"type":"contacts","id":"c2"},'
            . '{"type":"contacts","id":"c3"}'
            . ']}';
        $file1LockFileName = 'file1.lock';
        $expectedIncludedItems = [
            'contacts|c1' => [['type' => 'contacts', 'id' => 'c1'], 1, 'included'],
            'contacts|c2' => [['type' => 'contacts', 'id' => 'c2'], 2, 'included']
        ];
        $expectedProcessedItems = [];

        $this->fileLockManager->expects(self::exactly(2))
            ->method('acquireLock')
            ->withConsecutive([$includeIndexLockFileName], [$file1LockFileName])
            ->willReturn(true);
        $this->fileManager->expects(self::exactly(3))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile],
                ['api_123_include_index_linked', false, $linkedIndexDataFile]
            ]);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('file1', true)
            ->willReturn($file1Content);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData(
                $this->itemKeyBuilder,
                $includeAccessor,
                $this->fileLockManager,
                [$file1LockFileName],
                $expectedIncludedItems,
                $expectedProcessedItems
            ),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenIncludedDataHaveRelationships()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
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
                                ['type' => 'contacts', 'id' => 'c2'],
                                ['type' => 'contacts', 'id' => 'c3']
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
                            'data' => ['type' => 'users', 'id' => 'u1']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c4']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c5']
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0]],'
                . '"items":{"contacts|c1":[0,0],"contacts|c2":[0,1],"contacts|c3":[0,2],'
                . '"users|u2":[0,3],"users|u5":[0,4],"contacts|c4":[0,5],"contacts|c5":[0,6]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');
        $file1Content = '{"included":['
            . '{"type":"contacts","id":"c1","relationships":{"user":{"data":{"type":"users","id":"u1"}}}},'
            . '{"type":"contacts","id":"c2","relationships":{"user":{"data":{"type":"users","id":"u2"}}}},'
            . '{"type":"contacts","id":"c3","relationships":{"user":{"data":{"type":"users","id":"u3"}}}},'
            . '{"type":"users","id":"u2","relationships":{"organization":{"data":{"type":"organizations","id":"o1"}}}},'
            . '{"type":"users","id":"u5"},'
            . '{"type":"contacts","id":"c4","relationships":{"user":{"data":{"type":"users","id":"u2"}}}},'
            . '{"type":"contacts","id":"c5","relationships":{"user":{"data":{"type":"users","id":"u1"}}}}'
            . ']}';
        $file1LockFileName = 'file1.lock';
        $expectedIncludedItems = [
            'contacts|c1' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c1',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u1']]]
                ],
                0,
                'included'
            ],
            'contacts|c2' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c2',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u2']]]
                ],
                1,
                'included'
            ],
            'contacts|c3' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c3',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u3']]]
                ],
                2,
                'included'
            ],
            'users|u2'    => [
                [
                    'type'          => 'users',
                    'id'            => 'u2',
                    'relationships' => ['organization' => ['data' => ['type' => 'organizations', 'id' => 'o1']]]
                ],
                3,
                'included'
            ],
            'contacts|c4' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c4',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u2']]]
                ],
                5,
                'included'
            ],
            'contacts|c5' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c5',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u1']]]
                ],
                6,
                'included'
            ]
        ];
        $expectedProcessedItems = [];

        $this->fileLockManager->expects(self::exactly(2))
            ->method('acquireLock')
            ->withConsecutive([$includeIndexLockFileName], [$file1LockFileName])
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with(
                '["contacts|c1","contacts|c2","contacts|c3","contacts|c4","contacts|c5","users|u2"]',
                'api_123_include_index_linked'
            );
        $this->fileManager->expects(self::exactly(3))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile],
                ['api_123_include_index_linked', false, null]
            ]);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('file1', true)
            ->willReturn($file1Content);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData(
                $this->itemKeyBuilder,
                $includeAccessor,
                $this->fileLockManager,
                [$file1LockFileName],
                $expectedIncludedItems,
                $expectedProcessedItems
            ),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenThereAreAlreadyProcessedIncludedItems()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
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
                                ['type' => 'contacts', 'id' => 'c2'],
                                ['type' => 'contacts', 'id' => 'c3']
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
                            'data' => ['type' => 'users', 'id' => 'u1']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c4']
                        ]
                    ]
                ]
            ],
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c5']
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0]],'
                . '"items":{"contacts|c1":[0,0],"contacts|c2":[0,1],"contacts|c3":[0,2],'
                . '"users|u2":[0,3],"users|u5":[0,4],"contacts|c4":[0,5],"contacts|c5":[0,6]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{"contacts|c2":12,"users|u1":21,"users|u2":22,"users|u5":25}');
        $linkedIndexDataFile = $this->createMock(File::class);
        $linkedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('["contacts|c2","contacts|c3"]');
        $file1Content = '{"included":['
            . '{"type":"contacts","id":"c1","relationships":{"user":{"data":{"type":"users","id":"u1"}}}},'
            . 'null,'
            . '{"type":"contacts","id":"c3","relationships":{"user":{"data":{"type":"users","id":"u3"}}}},'
            . 'null,'
            . '{"type":"users","id":"u5"},'
            . '{"type":"contacts","id":"c4","relationships":{"user":{"data":{"type":"users","id":"u2"}}}},'
            . '{"type":"contacts","id":"c5","relationships":{"user":{"data":{"type":"users","id":"u1"}}}}'
            . ']}';
        $file1LockFileName = 'file1.lock';
        $expectedIncludedItems = [
            'contacts|c1' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c1',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u1']]]
                ],
                0,
                'included'
            ],
            'contacts|c3' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c3',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u3']]]
                ],
                2,
                'included'
            ],
            'contacts|c4' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c4',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u2']]]
                ],
                5,
                'included'
            ],
            'contacts|c5' => [
                [
                    'type'          => 'contacts',
                    'id'            => 'c5',
                    'relationships' => ['user' => ['data' => ['type' => 'users', 'id' => 'u1']]]
                ],
                6,
                'included'
            ]
        ];
        $expectedProcessedItems = [
            'contacts|c2' => 12,
            'users|u1'    => 21,
            'users|u2'    => 22
        ];

        $this->fileLockManager->expects(self::exactly(2))
            ->method('acquireLock')
            ->withConsecutive([$includeIndexLockFileName], [$file1LockFileName])
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with(
                '["contacts|c2","contacts|c3","contacts|c1","contacts|c4","contacts|c5"]',
                'api_123_include_index_linked'
            );
        $this->fileManager->expects(self::exactly(3))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile],
                ['api_123_include_index_linked', false, $linkedIndexDataFile]
            ]);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('file1', true)
            ->willReturn($file1Content);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData(
                $this->itemKeyBuilder,
                $includeAccessor,
                $this->fileLockManager,
                [$file1LockFileName],
                $expectedIncludedItems,
                $expectedProcessedItems
            ),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenThereAreAlreadyProcessedIncludedItemsAndNoIncludedItems()
    {
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'c2']
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
                            'data' => ['type' => 'users', 'id' => 'u1']
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0]],'
                . '"items":{"contacts|c1":[0,0],"contacts|c2":[0,1],"contacts|c3":[0,2],'
                . '"users|u2":[0,3],"users|u5":[0,4],"contacts|c4":[0,5],"contacts|c5":[0,6]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{"contacts|c2":12,"users|u1":21,"users|u2":22,"users|u5":25}');
        $file1Content = '{"included":['
            . '{"type":"contacts","id":"c1","relationships":{"user":{"data":{"type":"users","id":"u1"}}}},'
            . 'null,'
            . '{"type":"contacts","id":"c3","relationships":{"user":{"data":{"type":"users","id":"u3"}}}},'
            . 'null,'
            . '{"type":"users","id":"u5"},'
            . '{"type":"contacts","id":"c4","relationships":{"user":{"data":{"type":"users","id":"u2"}}}},'
            . '{"type":"contacts","id":"c5","relationships":{"user":{"data":{"type":"users","id":"u1"}}}}'
            . ']}';
        $expectedProcessedItems = [
            'contacts|c2' => 12,
            'users|u1'    => 21
        ];

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($includeIndexLockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile]
            ]);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('file1', true)
            ->willReturn($file1Content);
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertEquals(
            new IncludedData(
                $this->itemKeyBuilder,
                $includeAccessor,
                $this->fileLockManager,
                null,
                [],
                $expectedProcessedItems
            ),
            $includedData
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetIncludedItemsWhenNotPossibleToAcquireLockForOneOfChunkFile()
    {
        $includeIndexLockAttemptLimit = 3;
        $includeIndexLockWaitBetweenAttempts = 2;
        $data = [
            [
                'data' => [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact' => [
                            'data' => ['type' => 'contacts', 'id' => 'c1']
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
                                ['type' => 'contacts', 'id' => 'c2'],
                                ['type' => 'contacts', 'id' => 'c3'],
                                ['type' => 'contacts', 'id' => 'c7']
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $operationId = 123;
        $includeAccessor = new JsonApiIncludeAccessor($this->itemKeyBuilder);
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":['
                . '["file1","included",0],'
                . '["file2","included",3],'
                . '["file3","included",6],'
                . '["file4","included",9]'
                . '],'
                . '"items":{'
                . '"users|u2":[0,0],"contacts|c1":[0,1],"contacts|c2":[0,2],'
                . '"contacts|c3":[1,0],"contacts|c4":[1,1],"contacts|c5":[1,2],'
                . '"contacts|c6":[2,0],"contacts|c7":[2,1],"contacts|c8":[2,2],'
                . '"contacts|c6":[3,0]}'
                . '}');
        $processedIndexDataFile = $this->createMock(File::class);
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');
        $file1Content = '{"included":['
            . '{"type":"users","id":"u2"},'
            . '{"type":"contacts","id":"c1"},'
            . '{"type":"contacts","id":"c2"}'
            . ']}';
        $file2Content = '{"included":['
            . '{"type":"contacts","id":"c3"},'
            . '{"type":"contacts","id":"c4"},'
            . '{"type":"contacts","id":"c5"}'
            . ']}';
        $file3Content = '{"included":['
            . '{"type":"contacts","id":"c6"},'
            . '{"type":"contacts","id":"c7"},'
            . '{"type":"contacts","id":"c8"}'
            . ']}';
        $file1LockFileName = 'file1.lock';
        $file2LockFileName = 'file2.lock';

        $this->fileLockManager->expects(self::exactly(3))
            ->method('acquireLock')
            ->withConsecutive(
                [$includeIndexLockFileName, $includeIndexLockAttemptLimit, $includeIndexLockWaitBetweenAttempts],
                [$file1LockFileName, $includeIndexLockAttemptLimit, $includeIndexLockWaitBetweenAttempts],
                [$file2LockFileName, $includeIndexLockAttemptLimit, $includeIndexLockWaitBetweenAttempts]
            )
            ->willReturnOnConsecutiveCalls(true, true, false);
        $this->fileManager->expects(self::once())
            ->method('writeToStorage')
            ->with('["contacts|c1","contacts|c2","contacts|c3","contacts|c7"]', 'api_123_include_index_linked');
        $this->fileManager->expects(self::exactly(3))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile],
                ['api_123_include_index_linked', false, null]
            ]);
        $this->fileManager->expects(self::exactly(3))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content],
                ['file3', true, $file3Content]
            ]);
        $this->fileLockManager->expects(self::exactly(2))
            ->method('releaseLock')
            ->withConsecutive([$file1LockFileName], [$includeIndexLockFileName]);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Not possible to get included items now'
                . ' because the lock cannot be acquired for the "file2" chunk file.'
            );

        $this->includeMapManager->setReadLockAttemptLimit($includeIndexLockAttemptLimit);
        $this->includeMapManager->setReadLockWaitBetweenAttempts($includeIndexLockWaitBetweenAttempts);
        $includedData = $this->includeMapManager->getIncludedItems(
            $this->fileManager,
            $operationId,
            $includeAccessor,
            $data
        );
        self::assertNull($includedData);
    }

    public function testMoveToProcessed()
    {
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $file1Content = '{"included":[{"type":"accounts","id":"a1","attributes":{"name":"a1"}},'
            . '{"type":"accounts","id":"a2","attributes":{"name":"a2"}}]}';
        $file2Content = '{"included":[{"type":"accounts","id":"a3","attributes":{"name":"a3"}},'
            . '{"type":"accounts","id":"a4","attributes":{"name":"a4"}}]}';
        $processedIndexDataFile = $this->createMock(File::class);

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($includeIndexLockFileName)
            ->willReturn(true);

        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0],["file2","included",2]],'
                . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1],"accounts|a3":[1,0],"accounts|a4":[1,1]}'
                . '}');
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');

        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile]
            ]);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content]
            ]);

        $expectedChunk1UpdatedData = '{"included":[null,{"type":"accounts","id":"a2","attributes":{"name":"a2"}}]}';
        $expectedChunk2UpdatedData = '{"included":[{"type":"accounts","id":"a3","attributes":{"name":"a3"}},null]}';
        $expectedProcessedData = '{"accounts|a1":268,"accounts|a4":777}';
        $expectedIndexData = '{'
            . '"files":[["file1","included",0],["file2","included",2]],'
            . '"items":{"accounts|a2":[0,1],"accounts|a3":[1,0]}'
            . '}';
        $this->fileManager->expects(self::exactly(4))
            ->method('writeToStorage')
            ->withConsecutive(
                [$expectedChunk1UpdatedData, 'file1'],
                [$expectedChunk2UpdatedData, 'file2'],
                [$expectedProcessedData, 'api_123_include_index_processed'],
                [$expectedIndexData, 'api_123_include_index']
            );

        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $this->includeMapManager->moveToProcessed(
            $this->fileManager,
            123,
            [
                ['accounts', 'a1', 268],
                ['accounts', 'a4', 777]
            ]
        );
    }

    public function testMoveToProcessedWhenAllItemsWereRemovedFromChunkFile()
    {
        $includeIndexLockFileName = 'api_123_include_index.lock';
        $indexDataFile = $this->createMock(File::class);
        $file1Content = '{"included":[{"type":"accounts","id":"a1","attributes":{"name":"a1"}},'
            . '{"type":"accounts","id":"a2","attributes":{"name":"a2"}}]}';
        $file2Content = '{"included":[{"type":"accounts","id":"a3","attributes":{"name":"a3"}},'
            . '{"type":"accounts","id":"a4","attributes":{"name":"a4"}}]}';
        $processedIndexDataFile = $this->createMock(File::class);

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($includeIndexLockFileName)
            ->willReturn(true);

        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0],["file2","included",2]],'
                . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1],"accounts|a3":[1,0],"accounts|a4":[1,1]}'
                . '}');
        $processedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{}');

        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_processed', false, $processedIndexDataFile]
            ]);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->willReturnMap([
                ['file1', true, $file1Content],
                ['file2', true, $file2Content]
            ]);

        $expectedChunk2UpdatedData = '{"included":[{"type":"accounts","id":"a3","attributes":{"name":"a3"}},null]}';
        $expectedProcessedData = '{"accounts|a1":268,"accounts|a2":269,"accounts|a4":777}';
        $expectedIndexData = '{'
            . '"files":[["","included",0],["file2","included",2]],'
            . '"items":{"accounts|a3":[1,0]}'
            . '}';
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('file1');
        $this->fileManager->expects(self::exactly(3))
            ->method('writeToStorage')
            ->withConsecutive(
                [$expectedChunk2UpdatedData, 'file2'],
                [$expectedProcessedData, 'api_123_include_index_processed'],
                [$expectedIndexData, 'api_123_include_index']
            );

        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($includeIndexLockFileName);

        $this->includeMapManager->moveToProcessed(
            $this->fileManager,
            123,
            [
                ['accounts', 'a1', 268],
                ['accounts', 'a2', 269],
                ['accounts', 'a4', 777]
            ]
        );
    }

    public function testMoveToProcessedWhenNotPossibleToAcquireLockForIncludeIndex()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Not possible to move included items to processed'
            . ' because the lock cannot be acquired for the "api_123_include_index" file.'
        );

        $includeIndexLockFileName = 'api_123_include_index.lock';
        $includeIndexLockAttemptLimit = 3;
        $includeIndexLockWaitBetweenAttempts = 2;

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($includeIndexLockFileName, $includeIndexLockAttemptLimit, $includeIndexLockWaitBetweenAttempts)
            ->willReturn(false);
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');

        $this->fileManager->expects(self::never())
            ->method('getFile');
        $this->fileManager->expects(self::never())
            ->method('getFileContent');
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');

        $this->includeMapManager->setMoveToProcessedLockAttemptLimit($includeIndexLockAttemptLimit);
        $this->includeMapManager->setMoveToProcessedLockWaitBetweenAttempts($includeIndexLockWaitBetweenAttempts);
        $this->includeMapManager->moveToProcessed(
            $this->fileManager,
            123,
            [
                ['accounts', 'a1', 268],
                ['accounts', 'a4', 777]
            ]
        );
    }

    public function testGetNotLinkedIncludedItemIndexes()
    {
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0],["file2","included",2],["","included",4]],'
                . '"items":{"accounts|a2":[0,1],"accounts|a4":[1,1]}'
                . '}');
        $linkedIndexDataFile = $this->createMock(File::class);
        $linkedIndexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('["accounts|a1","accounts|a3"]');

        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_linked', false, $linkedIndexDataFile]
            ]);

        self::assertSame(
            ['included' => [1, 3]],
            $this->includeMapManager->getNotLinkedIncludedItemIndexes($this->fileManager, 123)
        );
    }

    public function testGetNotLinkedIncludedItemIndexesWhenIndexDataIsEmpty()
    {
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{"files":[],"items":[]}');

        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with('api_123_include_index', false)
            ->willReturn($indexDataFile);

        self::assertSame(
            [],
            $this->includeMapManager->getNotLinkedIncludedItemIndexes($this->fileManager, 123)
        );
    }

    public function testGetNotLinkedIncludedItemIndexesWhenLinkedIndexFileDoesNotExist()
    {
        $indexDataFile = $this->createMock(File::class);
        $indexDataFile->expects(self::once())
            ->method('getContent')
            ->willReturn('{'
                . '"files":[["file1","included",0],["file2","included",2]],'
                . '"items":{"accounts|a1":[0,0],"accounts|a2":[0,1],"accounts|a3":[1,0],"accounts|a4":[1,1]}'
                . '}');

        $this->fileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
                ['api_123_include_index', false, $indexDataFile],
                ['api_123_include_index_linked', false, null]
            ]);

        self::assertSame(
            ['included' => [0, 1, 2, 3]],
            $this->includeMapManager->getNotLinkedIncludedItemIndexes($this->fileManager, 123)
        );
    }
}
