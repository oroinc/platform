<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use Gaufrette\Filesystem;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\Stream\Local;
use Gaufrette\StreamMode;
use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Batch\Splitter\FileSplitterInterface;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;

abstract class FileSplitterTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param FileSplitterInterface $splitter
     * @param string                $fileName
     * @param string                $fileContent
     * @param array                 $resultFileNames
     * @param array                 $resultFileContents
     * @param bool                  $withInMemoryBuffer
     *
     * @return ChunkFile[]
     */
    protected function splitFile(
        FileSplitterInterface $splitter,
        $fileName,
        $fileContent,
        array &$resultFileNames,
        array &$resultFileContents,
        $withInMemoryBuffer = false
    ) {
        if ($withInMemoryBuffer) {
            [$srcFileManager, $destFileManager] = $this->getFileManagers(
                $fileName,
                $fileContent,
                $resultFileNames,
                $resultFileContents
            );

            return $splitter->splitFile($fileName, $srcFileManager, $destFileManager);
        }

        $tmpFile = tmpfile();
        fwrite($tmpFile, $fileContent);
        fseek($tmpFile, 0);
        try {
            [$srcFileManager, $destFileManager] = $this->getFileManagers(
                $fileName,
                $tmpFile,
                $resultFileNames,
                $resultFileContents
            );

            return $splitter->splitFile($fileName, $srcFileManager, $destFileManager);
        } finally {
            fclose($tmpFile);
        }
    }

    /**
     * @param FileSplitterInterface $splitter
     * @param string                $fileContext
     * @param string                $exceptionClass
     * @param string                $exceptionMessage
     */
    protected function splitWithException(
        FileSplitterInterface $splitter,
        $fileContext,
        $exceptionClass,
        $exceptionMessage
    ) {
        $fileName = 'temporaryFileName';

        $tmpFile = tmpfile();
        fwrite($tmpFile, $fileContext);
        fseek($tmpFile, 0);

        $resultFileNames = [];
        $resultFileContents = [];
        [$srcFileManager, $destFileManager] = $this->getFileManagers(
            $fileName,
            $tmpFile,
            $resultFileNames,
            $resultFileContents
        );

        $actualException = null;
        try {
            $splitter->splitFile($fileName, $srcFileManager, $destFileManager);
        } catch (\Exception $e) {
            $actualException = $e;
        } finally {
            fclose($tmpFile);
        }

        $this->assertSplitterException($actualException, $exceptionClass, $exceptionMessage);
    }

    /**
     * @param string      $expectedFileName
     * @param int         $expectedFileIndex
     * @param int         $expectedFirstRecordOffset
     * @param string|null $expectedSectionName
     * @param ChunkFile   $file
     */
    protected function assertChunkFile(
        $expectedFileName,
        $expectedFileIndex,
        $expectedFirstRecordOffset,
        $expectedSectionName,
        ChunkFile $file
    ) {
        self::assertEquals($expectedFileName, $file->getFileName(), 'Assert file name.');
        self::assertSame($expectedFileIndex, $file->getFileIndex(), 'Assert file index.');
        self::assertSame($expectedFirstRecordOffset, $file->getFirstRecordOffset(), 'Assert first record offset.');
        self::assertSame($expectedSectionName, $file->getSectionName(), 'Assert section name.');
    }

    /**
     * @param array  $expectedContent
     * @param string $fileContent
     */
    protected function assertChunkContent($expectedContent, $fileContent)
    {
        $actualContent = JsonUtil::decode($fileContent);
        self::assertNotNull($actualContent, 'Error in input JSON fixture OR during split operation.');
        self::assertEquals($expectedContent, $actualContent, 'Assert content.');
    }

    /**
     * @param \Exception|null $exception
     * @param string          $innerExceptionClass
     * @param string          $innerExceptionMessage
     */
    protected function assertSplitterException($exception, $innerExceptionClass, $innerExceptionMessage)
    {
        self::assertThat(
            $exception,
            new \PHPUnit\Framework\Constraint\Exception(FileSplitterException::class)
        );
        self::assertThat(
            $exception->getPrevious(),
            new \PHPUnit\Framework\Constraint\Exception($innerExceptionClass),
            'Failed asserting the inner exception.'
        );
        self::assertThat(
            $exception->getPrevious(),
            new \PHPUnit\Framework\Constraint\ExceptionMessage($innerExceptionMessage),
            'Failed asserting the inner exception.'
        );
    }

    /**
     * @param resource|string $tmpFile
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Local
     */
    private function getTmpFileStreamMock($tmpFile)
    {
        if (is_string($tmpFile)) {
            $fs = $this->createMock(Filesystem::class);
            $fs->expects(self::any())
                ->method('has')
                ->willReturn(true);
            $fs->expects(self::any())
                ->method('read')
                ->willReturn($tmpFile);
            $stream = new InMemoryBuffer($fs, 'test');
        } else {
            $stream = $this->createMock(Local::class);
            $stream->expects(self::once())
                ->method('cast')
                ->willReturn($tmpFile);
            $stream->expects(self::once())
                ->method('open')
                ->with(new StreamMode('r'))
                ->willReturnSelf();
        }

        return $stream;
    }

    /**
     * @param string          $fileName
     * @param resource|string $tmpFile
     * @param array           $resultFileNames
     * @param array           $resultFileContents
     *
     * @return array [srcFileManager, destFileManager]
     */
    private function getFileManagers(
        $fileName,
        $tmpFile,
        array &$resultFileNames,
        array &$resultFileContents
    ): array {
        $srcFileManager = $this->createMock(FileManager::class);
        $destFileManager = $this->createMock(FileManager::class);
        $srcFileManager->expects(self::once())
            ->method('getStream')
            ->with($fileName)
            ->willReturn($this->getTmpFileStreamMock($tmpFile));
        $destFileManager->expects(self::any())
            ->method('writeToStorage')
            ->withAnyParameters()
            ->willReturnCallback(
                function ($fileContent, $fileName) use (&$resultFileNames, &$resultFileContents) {
                    $resultFileNames[] = $fileName;
                    $resultFileContents[] = $fileContent;
                }
            );

        return [$srcFileManager, $destFileManager];
    }
}
