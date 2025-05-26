<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use PHPUnit\Framework\TestCase;

class FileSplitterExceptionTest extends TestCase
{
    public function testShouldBuildMessage(): void
    {
        $exception = new FileSplitterException('source', []);
        self::assertEquals('Failed to split the file "source".', $exception->getMessage());
    }

    public function testShouldBuildMessageWithPreviousException(): void
    {
        $previousException = new \Exception('some error.');
        $exception = new FileSplitterException('source', [], $previousException);
        self::assertEquals('Failed to split the file "source". Reason: some error.', $exception->getMessage());
    }

    public function testShouldSetTargetFileNames(): void
    {
        $targetFileNames = ['target1', 'target2'];
        $exception = new FileSplitterException('source', $targetFileNames);
        self::assertSame($targetFileNames, $exception->getTargetFileNames());
    }

    public function testShouldSetPreviousException(): void
    {
        $previous = new \Exception();
        $exception = new FileSplitterException('source', [], $previous);
        self::assertSame($previous, $exception->getPrevious());
    }
}
