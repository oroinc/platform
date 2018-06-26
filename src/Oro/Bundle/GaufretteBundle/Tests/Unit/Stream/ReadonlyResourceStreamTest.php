<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\Stream;

use Gaufrette\StreamMode;
use Oro\Bundle\GaufretteBundle\Stream\ReadonlyResourceStream;

class ReadonlyResourceStreamTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReadonlyResourceStream */
    private $stream;

    protected function setUp()
    {
        $resource = fopen(__DIR__ . '/../Fixtures/test.txt', 'rb');
        $this->stream = new ReadonlyResourceStream($resource);
    }

    public function testOpenRead()
    {
        $this->stream->open(new StreamMode('r'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The ReadonlyResourceStream does not allow write.
     */
    public function testOpenReadAndCreate()
    {
        $this->stream->open(new StreamMode('r+'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The ReadonlyResourceStream does not allow write.
     */
    public function testOpenWrite()
    {
        $this->stream->open(new StreamMode('w'));
    }

    public function testReadEof()
    {
        $this->assertFalse($this->stream->eof());
        $this->assertEquals('Test data', $this->stream->read(100));
        $this->assertTrue($this->stream->eof());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The ReadonlyResourceStream does not allow write.
     */
    public function testWrite()
    {
        $this->stream->write('test');
    }

    public function testClose()
    {
        $this->stream->close();

        // call is_resource on closed resource returns false
        $this->assertFalse(is_resource($this->stream->cast(1)));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The ReadonlyResourceStream does not allow write.
     */
    public function testFlush()
    {
        $this->stream->flush();
    }

    public function testSeek()
    {
        $this->stream->seek(2);
        $this->assertEquals(2, $this->stream->tell());


        $this->stream->seek(100);
        $this->assertEquals(100, $this->stream->tell());
    }

    public function testStat()
    {
        $stat = $this->stream->stat();
        $this->assertEquals(9, $stat['size']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The ReadonlyResourceStream does not allow unlink.
     */
    public function testUnlink()
    {
        $this->stream->unlink();
    }
}
