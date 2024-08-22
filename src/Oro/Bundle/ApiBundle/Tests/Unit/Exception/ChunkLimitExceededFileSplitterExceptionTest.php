<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Exception;

use Oro\Bundle\ApiBundle\Exception\ChunkLimitExceededFileSplitterException;

class ChunkLimitExceededFileSplitterExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorWhenNoSectionName(): void
    {
        $exception = new ChunkLimitExceededFileSplitterException(null);
        self::assertEquals(
            'The limit for the maximum number of chunks exceeded.',
            $exception->getMessage()
        );
        self::assertNull($exception->getSectionName());
    }

    public function testConstructorWhenSectionNameExists(): void
    {
        $exception = new ChunkLimitExceededFileSplitterException('test_section');
        self::assertEquals(
            'The limit for the maximum number of chunks exceeded for the section "test_section".',
            $exception->getMessage()
        );
        self::assertEquals('test_section', $exception->getSectionName());
    }
}
