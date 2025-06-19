<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifier;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use PHPUnit\Framework\TestCase;

class ChunkFileClassifierTest extends TestCase
{
    public function testIsPrimaryData(): void
    {
        $primaryDataChunkFile = new ChunkFile('file1', 0, 0, 'data');
        $anotherChunkFile = new ChunkFile('file1', 0, 0, 'another');

        $classifier = new ChunkFileClassifier('data');
        self::assertTrue($classifier->isPrimaryData($primaryDataChunkFile));
        self::assertFalse($classifier->isPrimaryData($anotherChunkFile));
    }

    public function testIsIncludedData(): void
    {
        $includedDataChunkFile = new ChunkFile('file1', 0, 0, 'included');
        $anotherChunkFile = new ChunkFile('file1', 0, 0, 'another');

        $classifier = new ChunkFileClassifier('data', 'included');
        self::assertTrue($classifier->isIncludedData($includedDataChunkFile));
        self::assertFalse($classifier->isIncludedData($anotherChunkFile));
    }

    public function testIsIncludedDataWhenClassifierIsNotConfiguredToDetermineIncludedData(): void
    {
        $chunkFile = new ChunkFile('file1', 0, 0, 'included');

        $classifier = new ChunkFileClassifier('data');
        self::assertFalse($classifier->isIncludedData($chunkFile));
    }
}
