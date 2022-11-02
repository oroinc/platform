<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mail\Processor;

use Laminas\Mail\Header\ContentType;
use Laminas\Mail\Header\GenericHeader;
use Laminas\Mail\Headers;
use Laminas\Mail\Storage\Part;
use Oro\Bundle\ImapBundle\Mail\Processor\ContentProcessor;
use Oro\Bundle\ImapBundle\Mail\Storage\Content;
use Oro\Component\Testing\ReflectionUtil;

class ContentProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Part|\PHPUnit\Framework\MockObject\MockObject */
    private $part;

    /** @var ContentProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->part = $this->createMock(Part::class);

        $this->processor = new ContentProcessor();
    }
    public function testGetPartContentType()
    {
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(true);

        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $header = new \stdClass();

        $this->part->expects($this->once())
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn($header);

        $result = ReflectionUtil::callMethod($this->processor, 'getPartContentType', [$this->part]);

        $this->assertSame($header, $result);
    }

    public function testGetPartContentTypeWithNoContentTypeHeader()
    {
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->once())
            ->method('has')
            ->with('Content-Type')
            ->willReturn(false);

        $this->part->expects($this->once())
            ->method('getHeaders')
            ->willReturn($headers);

        $result = ReflectionUtil::callMethod($this->processor, 'getPartContentType', [$this->part]);

        $this->assertNull($result);
    }

    /**
     * @dataProvider extractContentProvider
     */
    public function testExtractContent(
        $contentTransferEncoding,
        $contentType,
        $contentCharset,
        $contentValue,
        $expected,
        $decodedValue
    ) {
        // Content-Type header
        $contentTypeHeader = $this->createMock(ContentType::class);
        if ($contentType !== null) {
            $contentTypeHeader->expects($this->once())
                ->method('getType')
                ->willReturn($contentType);
            $contentTypeHeader->expects($this->once())
                ->method('getParameter')
                ->with('charset')
                ->willReturn($contentCharset);
            $contentTypeHeader->expects($this->any())
                ->method('getParameters')
                ->willReturn([]);
        }

        // Content-Transfer-Encoding header
        $contentTransferEncodingHeader = $this->createMock(GenericHeader::class);
        if ($contentTransferEncoding !== null) {
            $contentTransferEncodingHeader->expects($this->once())
                ->method('getFieldValue')
                ->willReturn($contentTransferEncoding);
        }

        // Headers object
        $headers = $this->createMock(Headers::class);
        $headers->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['Content-Type', $contentType !== null],
                ['Content-Transfer-Encoding', $contentTransferEncoding !== null],
            ]);

        // Part object
        $this->part->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);
        $this->part->expects($this->any())
            ->method('getHeader')
            ->willReturnMap([
                ['Content-Type', null, $contentTypeHeader],
                ['Content-Transfer-Encoding', null, $contentTransferEncodingHeader],
            ]);
        $this->part->expects($this->once())
            ->method('getContent')
            ->willReturn($contentValue);

        $result = ReflectionUtil::callMethod($this->processor, 'extractContent', [$this->part]);

        $this->assertEquals($expected, $result);
        $this->assertEquals($decodedValue, $result->getDecodedContent());
    }

    public static function extractContentProvider(): array
    {
        return [
            '7bit' => [
                '7Bit',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', '7Bit', 'ISO-8859-1'),
                'A value',
            ],
            '8bit' => [
                '8Bit',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', '8Bit', 'ISO-8859-1'),
                'A value',
            ],
            'binary' => [
                'Binary',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'Binary', 'ISO-8859-1'),
                'A value',
            ],
            'base64' => [
                'Base64',
                'SomeContentType',
                'ISO-8859-1',
                base64_encode('A value'),
                new Content(base64_encode('A value'), 'SomeContentType', 'Base64', 'ISO-8859-1'),
                'A value',
            ],
            'quoted-printable' => [
                'Quoted-Printable',
                'SomeContentType',
                'ISO-8859-1',
                quoted_printable_encode('A value='), // = symbol is added to test the 'quoted printable' decoding
                new Content(quoted_printable_encode('A value='), 'SomeContentType', 'Quoted-Printable', 'ISO-8859-1'),
                'A value=',
            ],
            'Unknown' => [
                'Unknown',
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'Unknown', 'ISO-8859-1'),
                'A value',
            ],
            'no charset' => [
                '8Bit',
                'SomeContentType',
                null,
                'A value',
                new Content('A value', 'SomeContentType', '8Bit', 'ASCII'),
                'A value',
            ],
            'no Content-Type' => [
                '8Bit',
                null,
                null,
                'A value',
                new Content('A value', 'text/plain', '8Bit', 'ASCII'),
                'A value',
            ],
            'no Content-Transfer-Encoding' => [
                null,
                'SomeContentType',
                'ISO-8859-1',
                'A value',
                new Content('A value', 'SomeContentType', 'BINARY', 'ISO-8859-1'),
                'A value',
            ],
        ];
    }
}
