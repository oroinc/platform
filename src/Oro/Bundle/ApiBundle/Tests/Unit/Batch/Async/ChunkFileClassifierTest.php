<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Async\ChunkFileClassifier;
use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;

class ChunkFileClassifierTest extends \PHPUnit\Framework\TestCase
{
    public function testIsPrimaryData()
    {
        $primaryDataChunkFile = new ChunkFile('file1', 0, 0, 'data');
        $anotherChunkFile = new ChunkFile('file1', 0, 0, 'another');

        $classifier = new ChunkFileClassifier('data');
        self::assertTrue($classifier->isPrimaryData($primaryDataChunkFile));
        self::assertFalse($classifier->isPrimaryData($anotherChunkFile));
    }

    public function testIsIncludedData()
    {
        $includedDataChunkFile = new ChunkFile('file1', 0, 0, 'included');
        $anotherChunkFile = new ChunkFile('file1', 0, 0, 'another');

        $classifier = new ChunkFileClassifier('data', 'included');
        self::assertTrue($classifier->isIncludedData($includedDataChunkFile));
        self::assertFalse($classifier->isIncludedData($anotherChunkFile));
    }

    public function testIsIncludedDataWhenClassifierIsNotConfiguredToDetermineIncludedData()
    {
        $chunkFile = new ChunkFile('file1', 0, 0, 'included');

        $classifier = new ChunkFileClassifier('data');
        self::assertFalse($classifier->isIncludedData($chunkFile));
    }
}
