<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Encoder;

use Oro\Bundle\ImportExportBundle\Serializer\Encoder\DummyEncoder;

class DummyEncoderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DummyEncoder */
    private $encoder;

    protected function setUp(): void
    {
        $this->encoder = new DummyEncoder();
    }

    public function testEncode()
    {
        $data = ['any_data' => new \stdClass()];
        $this->assertSame($data, $this->encoder->encode($data, ''));
    }

    public function testDecode()
    {
        $data = ['any_data' => new \stdClass()];
        $this->assertSame($data, $this->encoder->decode($data, ''));
    }

    public function testSupportsEncoding()
    {
        $this->assertFalse($this->encoder->supportsEncoding('json'));
        $this->assertTrue($this->encoder->supportsEncoding(null));
        $this->assertTrue($this->encoder->supportsEncoding(''));
    }

    public function testSupportsDecoding()
    {
        $this->assertFalse($this->encoder->supportsDecoding('json'));
        $this->assertTrue($this->encoder->supportsDecoding(null));
        $this->assertTrue($this->encoder->supportsDecoding(''));
    }
}
