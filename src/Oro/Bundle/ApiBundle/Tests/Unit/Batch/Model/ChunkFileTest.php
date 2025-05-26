<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use PHPUnit\Framework\TestCase;

class ChunkFileTest extends TestCase
{
    public function testGetFileName(): void
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertEquals('testFileName', $file->getFileName());
    }

    public function testGetFileIndex(): void
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertSame(1, $file->getFileIndex());
    }

    public function testGetFirstRecordOffset(): void
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertSame(2, $file->getFirstRecordOffset());
    }

    public function testEmptySectionName(): void
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertNull($file->getSectionName());
    }

    public function testSectionName(): void
    {
        $file = new ChunkFile('testFileName', 1, 2, 'testSection');

        self::assertEquals('testSection', $file->getSectionName());
    }
}
