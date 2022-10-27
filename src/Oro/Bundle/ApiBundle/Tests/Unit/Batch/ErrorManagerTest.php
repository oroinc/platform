<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch;

use Gaufrette\File;
use Oro\Bundle\ApiBundle\Batch\ErrorManager;
use Oro\Bundle\ApiBundle\Batch\FileLockManager;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\GaufretteBundle\FileManager;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ErrorManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FileLockManager */
    private $fileLockManager;

    /** @var ErrorManager */
    private $errorManager;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->fileLockManager = $this->createMock(FileLockManager::class);

        $this->errorManager = new ErrorManager(new FileNameProvider(), $this->fileLockManager, $this->logger);
    }

    private function getChunkFile(string $fileName, int $fileIndex): ChunkFile
    {
        return new ChunkFile($fileName, $fileIndex, 0);
    }

    public function testGetTotalErrorCount()
    {
        $operationId = 100;
        $indexContent = file_get_contents('Fixtures/error_manager/read_index.json', true);

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with($indexFileName)
            ->willReturn($indexContent);

        $count = $this->errorManager->getTotalErrorCount($this->fileManager, $operationId);
        self::assertSame(16, $count);
    }

    public function testGetTotalErrorCountWhenErrorIndexFileDoesNotExist()
    {
        $operationId = 100;

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(false);
        $this->fileManager->expects(self::never())
            ->method('getFileContent');
        $this->logger->expects(self::never())
            ->method('error');

        $count = $this->errorManager->getTotalErrorCount($this->fileManager, $operationId);
        self::assertSame(0, $count);
    }

    public function testGetTotalErrorCountWhenErrorIndexFileLoadingFailed()
    {
        $operationId = 100;

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $exception = new \Exception('some error');
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with($indexFileName)
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to read the errors index file "%s".', $indexFileName),
                ['exception' => $exception]
            );

        $count = $this->errorManager->getTotalErrorCount($this->fileManager, $operationId);
        self::assertSame(0, $count);
    }

    /**
     * @dataProvider readErrorsDataProvider
     */
    public function testReadErrors(int $offset, int $limit, int $operationId, int $indexesInvolved, array $result)
    {
        $indexContent = file_get_contents('Fixtures/error_manager/read_index.json', true);

        $errorFiles = [];
        $errorData = Yaml::parse(file_get_contents('Fixtures/error_manager/read_errors.yml', true));
        foreach ($errorData as $fileName => $errors) {
            $errorFiles[$fileName] = JsonUtil::encode($errors);
        }

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::exactly($indexesInvolved + 1))
            ->method('getFileContent')
            ->willReturnCallback(function ($fileName) use ($errorFiles, $indexContent, $indexFileName) {
                if (array_key_exists($fileName, $errorFiles)) {
                    return $errorFiles[$fileName];
                }
                if ($fileName === $indexFileName) {
                    return $indexContent;
                }
                throw new \InvalidArgumentException(sprintf('Unexpected file: %s.', $fileName));
            });

        $errors = $this->errorManager->readErrors($this->fileManager, $operationId, $offset, $limit);
        $errors = array_map(
            function (BatchError $error) {
                return $error->getId() . ': ' . $error->getTitle();
            },
            $errors
        );

        self::assertSame($result, $errors);
    }

    public function readErrorsDataProvider(): array
    {
        return [
            'first 4 records'                    => [
                'offset'          => 0,
                'limit'           => 4,
                'operationId'     => 100,
                'indexesInvolved' => 1,
                'result'          => [
                    '100-1-1: Test Error 1',
                    '100-1-2: Test Error 2',
                    '100-1-3: Test Error 3',
                    '100-1-4: Test Error 4'
                ]
            ],
            '2 records start from 2'             => [
                'offset'          => 2,
                'limit'           => 2,
                'operationId'     => 100,
                'indexesInvolved' => 1,
                'result'          => [
                    '100-1-3: Test Error 3',
                    '100-1-4: Test Error 4'
                ]
            ],
            '3 records from 2 different indexes' => [
                'offset'          => 3,
                'limit'           => 3,
                'operationId'     => 100,
                'indexesInvolved' => 2,
                'result'          => [
                    '100-1-4: Test Error 4',
                    '100-1-5: Test Error 5',
                    '100-2-2: Test Error 11'
                ]
            ],
            '10 record form index 2, 3 and 4'    => [
                'offset'          => 6,
                'limit'           => 10,
                'operationId'     => 100,
                'indexesInvolved' => 3,
                'result'          => [
                    '100-2-3: Test Error 12',
                    '100-2-4: Test Error 13',
                    '100-3-3: Test Error 21',
                    '100-3-4: Test Error 22',
                    '100-3-5: Test Error 23',
                    '100-3-6: Test Error 24',
                    '100-3-7: Test Error 25',
                    '100-3-8: Test Error 26',
                    '100-3-9: Test Error 27',
                    '100-4-4: Test Error 31'
                ]
            ],
            'offset too big'                     => [
                'offset'          => 30,
                'limit'           => 3,
                'operationId'     => 100,
                'indexesInvolved' => 0,
                'result'          => []
            ]
        ];
    }

    public function testReadErrorsWhenErrorIndexFileDoesNotExist()
    {
        $operationId = 100;

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(false);
        $this->fileManager->expects(self::never())
            ->method('getFileContent');
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame([], $this->errorManager->readErrors($this->fileManager, $operationId, 0, 10));
    }

    public function testReadErrorsWhenErrorIndexFileLoadingFailed()
    {
        $operationId = 100;

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $exception = new \Exception('some error');
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with($indexFileName)
            ->willThrowException($exception);
        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to read the errors index file "%s".', $indexFileName),
                ['exception' => $exception]
            );

        self::assertSame([], $this->errorManager->readErrors($this->fileManager, $operationId, 0, 10));
    }

    public function testWriteErrorsWhenErrorsCollectionIsEmpty()
    {
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');

        $this->errorManager->writeErrors(
            $this->fileManager,
            100,
            [],
            $this->getChunkFile('api_1_chunk_', 0)
        );
    }

    /**
     * @dataProvider writeErrorsDataProvider
     */
    public function testWriteErrors(
        int $operationId,
        string $chunkFileName,
        int $chunkFileIndex,
        array $errors,
        array $data
    ) {
        foreach ($data as &$testFile) {
            $testFile = rtrim(file_get_contents(__DIR__ . '/Fixtures/error_manager/' . $testFile), "\n\n");
        }
        unset($testFile);

        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $lockFileName = $indexFileName . '.lock';

        $file = null;
        if (isset($data['inputIndex'])) {
            $file = $this->createMock(File::class);
            $file->expects(self::once())
                ->method('getContent')
                ->willReturn($data['inputIndex']);
        }

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($lockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with($indexFileName, false)
            ->willReturn($file);
        $this->fileManager->expects(self::exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                [$data['expectedErrors'], sprintf('%s_errors', $chunkFileName)],
                [$data['expectedIndex'], $indexFileName]
            );
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($lockFileName);

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            $this->getChunkFile($chunkFileName, $chunkFileIndex)
        );
    }

    public function writeErrorsDataProvider(): array
    {
        return [
            'no index file'   => [
                'operationId'    => 100,
                'chunkFileName'  => 'api_chunk_file_name_1',
                'chunkFileIndex' => 1,
                'errors'         => [
                    BatchError::create('Test Error 1'),
                    BatchError::create('Test Error 2'),
                    BatchError::create('Test Error 3'),
                    BatchError::create('Test Error 4')
                ],
                'data'           => [
                    'expectedIndex'  => 'write_no_index_expected_index.json',
                    'expectedErrors' => 'write_expected_errors.json'
                ]
            ],
            'with index file' => [
                'operationId'    => 200,
                'chunkFileName'  => 'api_chunk_file_name_2',
                'chunkFileIndex' => 2,
                'errors'         => [
                    BatchError::create('Test Error 1'),
                    BatchError::create('Test Error 2'),
                    BatchError::create('Test Error 3'),
                    BatchError::create('Test Error 4')
                ],
                'data'           => [
                    'inputIndex'     => 'write_with_index_input_index.json',
                    'expectedIndex'  => 'write_with_index_expected_index.json',
                    'expectedErrors' => 'write_expected_errors.json'
                ]
            ]
        ];
    }

    public function testWriteErrorsWhenNewErrorsAreAddedToExistingChunkErrorsFile()
    {
        $operationId = 123;
        $chunkFileName = 'api_data_file';
        $chunkFileIndex = -1;
        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $lockFileName = $indexFileName . '.lock';

        /** @var BatchError[] $errors */
        $errors = [
            BatchError::createValidationError('Test Error 2', 'Test Error 2 Detail')
                ->setId('123-2-1')
                ->setItemIndex(1)
        ];
        $existingSerializedErrors = '[{"i":1,"c":400,"t":"Test Error 1","d":"Test Error 1 Detail"}]';
        $serializedErrors = '['
            . '{"i":1,"c":400,"t":"Test Error 1","d":"Test Error 1 Detail"},'
            . '{"i":1,"c":400,"t":"Test Error 2","d":"Test Error 2 Detail"}'
            . ']';

        $indexFile = $this->createMock(File::class);
        $indexFile->expects(self::once())
            ->method('getContent')
            ->willReturn('[["api_data_file_errors",-1,1],["api_chunk_file_name_1_errors",1,1]]');
        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($lockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with($indexFileName, false)
            ->willReturn($indexFile);
        $this->fileManager->expects(self::once())
            ->method('getFileContent')
            ->with('api_data_file_errors')
            ->willReturn($existingSerializedErrors);
        $this->fileManager->expects(self::exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                [$serializedErrors, 'api_data_file_errors'],
                ['[["api_data_file_errors",-1,2],["api_chunk_file_name_1_errors",1,1]]', $indexFileName]
            );
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($lockFileName);

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            $this->getChunkFile($chunkFileName, $chunkFileIndex)
        );
    }

    public function testWriteErrorsWhenUpdateErrorIndexFailed()
    {
        $operationId = 123;
        $chunkFileName = 'api_chunk_file_name_1';
        $chunkFileIndex = 1;
        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $lockFileName = $indexFileName . '.lock';

        $errorSource = new ErrorSource();
        $errorSource->setPointer('pointer');
        $errorSource->setPropertyPath('property_path');
        $errorSource->setParameter('parameter');
        /** @var BatchError[] $errors */
        $errors = [
            BatchError::create('Test Error 1', 'Test Error 1 Detail')
                ->setId('123-2-1')
                ->setItemIndex(1)
                ->setStatusCode(400)
                ->setCode('code1')
                ->setInnerException(new \Exception('some error'))
                ->setSource($errorSource)
        ];
        $serializedErrors = '[{'
            . '"i":1,'
            . '"c":400,'
            . '"e":"code1",'
            . '"t":"Test Error 1",'
            . '"d":"Test Error 1 Detail",'
            . '"s":{"p":"pointer","pp":"property_path","pr":"parameter"}'
            . '}]';
        $exception = new \Exception('updateErrorIndex error');

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($lockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with($indexFileName, false)
            ->willReturn(null);
        $this->fileManager->expects(self::exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                [$serializedErrors, sprintf('%s_errors', $chunkFileName)],
                ['[["api_chunk_file_name_1_errors",1,1]]', $indexFileName]
            )
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () {
                }),
                new ReturnCallback(function () use ($exception) {
                    throw $exception;
                })
            );
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($lockFileName);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                sprintf('Failed to update the errors index file for the "%s" chunk file.', $chunkFileName),
                ['exception' => $exception]
            );

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            $this->getChunkFile($chunkFileName, $chunkFileIndex)
        );
    }

    public function testWriteErrorsWhenUpdateErrorIndexFailedBecauseLockCannotBeAcquired()
    {
        $operationId = 123;
        $chunkFileName = 'api_chunk_file_name_1';
        $chunkFileIndex = 1;
        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $lockFileName = $indexFileName . '.lock';

        $errorSource = new ErrorSource();
        $errorSource->setPointer('pointer');
        $errorSource->setPropertyPath('property_path');
        $errorSource->setParameter('parameter');
        /** @var BatchError[] $errors */
        $errors = [
            BatchError::create('Test Error 1', 'Test Error 1 Detail')
                ->setId('123-2-1')
                ->setItemIndex(1)
                ->setStatusCode(400)
                ->setCode('code1')
                ->setInnerException(new \Exception('some error'))
                ->setSource($errorSource)
        ];

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($lockFileName)
            ->willReturn(false);
        $this->fileManager->expects(self::never())
            ->method('writeToStorage');
        $this->fileLockManager->expects(self::never())
            ->method('releaseLock');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(sprintf(
                'Failed to update the errors index file for the "%s" chunk file because the lock cannot be acquired.',
                $chunkFileName
            ));

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            $this->getChunkFile($chunkFileName, $chunkFileIndex)
        );
    }

    public function testSerializeAndDeserializeError()
    {
        $operationId = 123;
        $chunkFileName = 'api_chunk_file_name_1';
        $chunkFileIndex = 1;
        $indexFileName = sprintf('api_%d_error_index', $operationId);
        $lockFileName = $indexFileName . '.lock';

        $errorSource = new ErrorSource();
        $errorSource->setPointer('pointer');
        $errorSource->setPropertyPath('property_path');
        $errorSource->setParameter('parameter');
        /** @var BatchError[] $errors */
        $errors = [
            BatchError::create('Test Error 1', 'Test Error 1 Detail')
                ->setId('123-2-1')
                ->setItemIndex(1)
                ->setStatusCode(400)
                ->setCode('code1')
                ->setInnerException(new \Exception('some error'))
                ->setSource($errorSource)
        ];
        $serializedErrors = '[{'
            . '"i":1,'
            . '"c":400,'
            . '"e":"code1",'
            . '"t":"Test Error 1",'
            . '"d":"Test Error 1 Detail",'
            . '"s":{"p":"pointer","pp":"property_path","pr":"parameter"}'
            . '}]';

        $this->fileLockManager->expects(self::once())
            ->method('acquireLock')
            ->with($lockFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('hasFile')
            ->with($indexFileName)
            ->willReturn(true);
        $this->fileManager->expects(self::once())
            ->method('getFile')
            ->with($indexFileName, false)
            ->willReturn(null);
        $this->fileManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->withConsecutive([$indexFileName], [sprintf('%s_errors', $chunkFileName)])
            ->willReturnOnConsecutiveCalls(
                '[["api_chunk_file_name_1_errors",1,1]]',
                $serializedErrors
            );
        $this->fileManager->expects(self::exactly(2))
            ->method('writeToStorage')
            ->withConsecutive(
                [$serializedErrors, sprintf('%s_errors', $chunkFileName)],
                ['[["api_chunk_file_name_1_errors",1,1]]', $indexFileName]
            );
        $this->fileLockManager->expects(self::once())
            ->method('releaseLock')
            ->with($lockFileName);

        $this->errorManager->writeErrors(
            $this->fileManager,
            $operationId,
            $errors,
            $this->getChunkFile($chunkFileName, $chunkFileIndex)
        );

        $errors[0]->setInnerException(null);
        self::assertEquals(
            $errors,
            $this->errorManager->readErrors($this->fileManager, $operationId, 0, 10)
        );
    }
}
