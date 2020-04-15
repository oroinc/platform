<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Model;

use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;

class ChunkFileTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFileName()
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertEquals('testFileName', $file->getFileName());
    }

    public function testGetFileIndex()
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertSame(1, $file->getFileIndex());
    }

    public function testGetFirstRecordOffset()
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertSame(2, $file->getFirstRecordOffset());
    }

    public function testEmptySectionName()
    {
        $file = new ChunkFile('testFileName', 1, 2);

        self::assertNull($file->getSectionName());
    }

    public function testSectionName()
    {
        $file = new ChunkFile('testFileName', 1, 2, 'testSection');

        self::assertEquals('testSection', $file->getSectionName());
    }
}
