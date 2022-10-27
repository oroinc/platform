<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Storage;

use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Header\HeaderInterface;
use Laminas\Mail\Headers;
use Laminas\Mail\Storage\Part;
use Oro\Bundle\ImapBundle\Mail\Storage\Body;
use Oro\Bundle\ImapBundle\Mail\Storage\Content;

class BodyTest extends \PHPUnit\Framework\TestCase
{
    /** @var Part|\PHPUnit\Framework\MockObject\MockObject */
    private $part;

    /** @var Body */
    private $body;

    protected function setUp(): void
    {
        $this->part = $this->createMock(Part::class);

        $this->body = new Body($this->part);
    }

    public function testGetHeaders()
    {
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Type', true],
                ['Content-Transfer-Encoding', true]
            ]);

        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $result = $this->body->getHeaders();

        $this->assertSame($headers, $result);
    }

    public function testGetHeader()
    {
        $header = $this->createMock(Headers::class);
        $header->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Type', true],
                ['Content-Transfer-Encoding', true]
            ]);

        $this->part->expects($this->once())
            ->method('getHeader')
            ->with('SomeHeader', 'string')
            ->willReturn($header);

        $result = $this->body->getHeader('SomeHeader', 'string');

        $this->assertSame($header, $result);
    }

    public function testGetContentSinglePartText()
    {
        $contentValue = 'testContent';
        $contentType = 'type/testContentType';
        $contentTransferEncoding = 'testContentTransferEncoding';
        $contentEncoding = 'testEncoding';

        $this->part->expects($this->once())
            ->method('isMultipart')
            ->willReturn(false);

        $this->preparePartMock($this->part, $contentValue, $contentType, $contentTransferEncoding, $contentEncoding);
        $result = $this->body->getContent(Body::FORMAT_TEXT);
        $expected = new Content($contentValue, $contentType, $contentTransferEncoding, $contentEncoding);

        $this->assertEquals($expected, $result);
    }

    public function testGetContentSinglePartHtml()
    {
        $contentValue = '<p>testContent</p>';
        $contentType = 'type/testContentType';
        $contentTransferEncoding = 'testContentTransferEncoding';
        $contentEncoding = 'testEncoding';

        $this->part->expects($this->once())
            ->method('isMultipart')
            ->willReturn(false);

        $this->preparePartMock($this->part, $contentValue, $contentType, $contentTransferEncoding, $contentEncoding);
        $result = $this->body->getContent(Body::FORMAT_HTML);
        $expected = new Content($contentValue, $contentType, $contentTransferEncoding, $contentEncoding);

        $this->assertEquals($expected, $result);
    }

    public function testGetContentMultipartText()
    {
        $this->part->expects($this->any())
            ->method('isMultipart')
            ->willReturn(true);

        $part1 = $this->createMock(Part::class);
        $part2 = $this->createMock(Part::class);

        $part1->expects($this->any())
            ->method('isMultipart')
            ->willReturn(false);
        $part2->expects($this->any())
            ->method('isMultipart')
            ->willReturn(false);

        $this->mockIterator($this->part, $part1, $part2);
        $this->preparePartMock($part1, 'part1Content', 'text/plain', '8Bit', 'ISO-8859-1');
        $this->preparePartMock($part2, 'part2Content', 'text/html', 'Base64', 'ISO-8859-1');

        // Test to TEXT body
        $result = $this->body->getContent(Body::FORMAT_TEXT);
        $this->assertEquals(
            new Content('part1Content', 'text/plain', '8Bit', 'ISO-8859-1'),
            $result
        );

        // Test to HTML body
        $result = $this->body->getContent(Body::FORMAT_HTML);
        $this->assertEquals(
            new Content('part2Content', 'text/html', 'Base64', 'ISO-8859-1'),
            $result
        );
    }

    private function mockIterator(\PHPUnit\Framework\MockObject\MockObject $obj, $iterationResult1, $iterationResult2)
    {
        $obj->expects($this->exactly(3))
            ->method('current')
            ->willReturnOnConsecutiveCalls($iterationResult1, $iterationResult1, $iterationResult2);
        $obj->expects($this->any())
            ->method('next');
        $obj->expects($this->any())
            ->method('rewind');
        $obj->expects($this->exactly(3))
            ->method('valid')
            ->willReturnOnConsecutiveCalls(true, true, true);
    }

    private function preparePartMock(
        \PHPUnit\Framework\MockObject\MockObject $obj,
        $contentValue,
        $contentType,
        $contentTransferEncoding,
        $contentEncoding
    ) {
        $headers = $this->createMock(Headers::class);

        $headers->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Type', true],
                ['Content-Transfer-Encoding', true]
            ]);

        $obj->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);
        $obj->expects($this->once())
            ->method('getContent')
            ->willReturn($contentValue);

        $contentTypeHeader = $this->createMock(ContentType::class);
        $contentTypeHeader->expects($this->any())
            ->method('getType')
            ->willReturn($contentType);
        $contentTypeHeader->expects($this->any())
            ->method('getParameter')
            ->willReturn($contentEncoding);

        $contentEncodingHeader = $this->createMock(HeaderInterface::class);
        $contentEncodingHeader->expects($this->any())
            ->method('getFieldValue')
            ->willReturn($contentTransferEncoding);

        $obj->expects($this->any())
            ->method('getHeader')
            ->willReturnMap([
                ['Content-Type', null, $contentTypeHeader],
                ['Content-Transfer-Encoding', null, $contentEncodingHeader]
            ]);
    }
}
